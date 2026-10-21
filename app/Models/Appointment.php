<?php

namespace App\Models;

use App\Enums\AppointmentStatusEnum;
use App\Enums\BillTypeEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\QueueStatusEnum;
use App\Enums\RoleEnum;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class Appointment extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'patient_id',
        'visit_number',
        'clinic_user_id',
        'clinic_id',
        'schedule_date',
        'queue_number',
        'queue_status',
        'queue_called_at',
        'height',
        'weight',
        'blood_pressure',
        'symptoms',
        'objective',
        'diagnostic',
        'plan',
        'satusehat_diagnosis_code',
        'satusehat_encounter_id',
        'satusehat_condition_id',
        'satusehat_sync_status',
        'satusehat_sync_error',
        'satusehat_synced_at',
        'voided_at',
        'voided_by',
        'void_reason',
        'status',
    ];

    protected $casts = [
        'id' => 'integer',
        'schedule_date' => 'datetime',
        'queue_called_at' => 'datetime',
        'satusehat_synced_at' => 'datetime',
        'voided_at' => 'datetime',
        'height' => 'integer',
        'weight' => 'integer',
        'status' => AppointmentStatusEnum::class,
    ];

    protected static function booted()
    {
        parent::booted();
        static::creating(function ($appointment) {
            $appointment->status = AppointmentStatusEnum::SCHEDULED;
            static::assignQueue($appointment);
        });
        static::created(function ($appointment) {
            $appointment->forceFill([
                'visit_number' => str_pad((string) $appointment->getKey(), 4, '0', STR_PAD_LEFT),
            ])->saveQuietly();

            $appointment_id = $appointment->id;
            $u = User::find($appointment->clinic_doctor()->first()->user_id);
            $fee = $u->fee;
            $treatment = Treatment::create([
                'treatment_category_id' => 1,
                'appointment_id' => $appointment_id,
                'name' => 'Doctor\'s Fee',
                'description' => '',
                'quantity' => 1,
                'price' => $fee,
                'discount' => 0,
                'amount' => $fee,
            ]);
        });
        static::updating(function ($appointment) {
            if ($appointment->isDirty(['schedule_date', 'clinic_user_id'])) {
                static::assignQueue($appointment);
            }

            $status = $appointment->status;
            if (
                $appointment->isDirty('status')
                && $status?->value === AppointmentStatusEnum::CONFIRMED->value
            ) {
                $appointment->queue_status = QueueStatusEnum::SERVED->value;
                $appointment->queue_called_at ??= now();
            }

            // dd($status->value);
            if (
                $appointment->isDirty('status')
                && $status?->value === AppointmentStatusEnum::CONFIRMED->value
                && $appointment->prescriptions()->whereNotIn('pharmacy_status', ['dispensed', 'rejected'])->exists()
            ) {
                throw ValidationException::withMessages([
                    'status' => 'Selesaikan atau tolak semua resep melalui proses apotik sebelum menutup kunjungan.',
                ]);
            }

            if (
                $appointment->isDirty('status')
                && $status?->value === AppointmentStatusEnum::CONFIRMED->value
                && !Transaction::query()->where('appointment_id', $appointment->id)->exists()
            ) {
                // Treatments
                $treatments = $appointment->treatments()->get();
                $total_treatments = 0;
                foreach ($treatments as $treatment) {
                    $total_treatments = $total_treatments + $treatment->amount;
                }
                // Prescriptions
                // $prescriptions = Prescription::where('application_id', $appointment->id)->where('is_prepared', true)->get();
                $prescriptions = $appointment->prescriptions()
                    ->where('dispensed_quantity', '>', 0)
                    ->get();
                $total_prescriptions = 0;
                foreach ($prescriptions as $prescription) {
                    $unitAmount = $prescription->quantity > 0 ? $prescription->amount / $prescription->quantity : 0;
                    $total_prescriptions += (int) round($unitAmount * $prescription->dispensed_quantity);
                }
                // Transaction Insertion
                $transaction = Transaction::create([
                    'appointment_id' => $appointment->id,
                    'patient_name' => $appointment->patient()->first()->name,
                    'patient_phone' => $appointment->patient()->first()->phone,
                    'patient_age' => $appointment->patient()->first()->age,
                    'doctor_name' => $appointment->clinic_doctor()->first()->user()->first()->name,
                    'doctor_phone' => $appointment->clinic_doctor()->first()->user()->first()->phone,
                    'appointment_date' => $appointment->schedule_date,
                    'note' => $appointment->symptoms . '\n' . $appointment->diagnostic,
                    'amount_treatments' => $total_treatments,
                    'amount_prescriptions' => $total_prescriptions,
                    'amount_total' => $total_treatments + $total_prescriptions,
                    'payment_receipt' => null,
                    'status' => PaymentStatusEnum::UNPAID->value,
                    'paid_at' => null,
                ]);
                // Bill Insertion
                foreach ($treatments as $treatment) {
                    $bill = Bill::create([
                        'transaction_id' => $transaction->id,
                        'type' => BillTypeEnum::TREATMENT->value,
                        'item_name' => $treatment->name,
                        'item_description' => $treatment->description,
                        'quantity' => $treatment->quantity,
                        'price' => $treatment->price,
                        'discount' => $treatment->discount,
                        'amount' => $treatment->amount,
                    ]);
                }
                foreach ($prescriptions as $prescription) {
                    $bill = Bill::create([
                        'transaction_id' => $transaction->id,
                        'type' => BillTypeEnum::PRESCRIPTION->value,
                        'item_name' => $prescription->drug()->first()->name,
                        'item_description' => $prescription->description,
                        'quantity' => $prescription->dispensed_quantity,
                        'price' => $prescription->price,
                        'discount' => $prescription->discount,
                        'amount' => $prescription->quantity > 0
                            ? (int) round($prescription->amount * $prescription->dispensed_quantity / $prescription->quantity)
                            : 0,
                    ]);
                }
            }
        });
    }

    private static function assignQueue(self $appointment): void
    {
        $clinicId = ClinicUser::query()->whereKey($appointment->clinic_user_id)->value('clinic_id');
        $appointment->clinic_id = $clinicId;
        $appointment->queue_called_at = null;
        $appointment->queue_status = QueueStatusEnum::WAITING->value;

        if (!$clinicId || !$appointment->schedule_date) {
            $appointment->queue_number = null;
            return;
        }

        $queueDate = Carbon::parse($appointment->schedule_date)->toDateString();
        $appointment->queue_number = DB::transaction(function () use ($clinicId, $queueDate): int {
            DB::table('appointment_queue_counters')->insertOrIgnore([
                'clinic_id' => $clinicId,
                'queue_date' => $queueDate,
                'last_number' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $counter = DB::table('appointment_queue_counters')
                ->where('clinic_id', $clinicId)
                ->where('queue_date', $queueDate)
                ->lockForUpdate()
                ->first();

            $nextNumber = (int) $counter->last_number + 1;
            DB::table('appointment_queue_counters')
                ->where('id', $counter->id)
                ->update(['last_number' => $nextNumber, 'updated_at' => now()]);

            return $nextNumber;
        });
    }

    public function getQueueCodeAttribute(): ?string
    {
        return $this->queue_number === null
            ? null
            : 'A-' . str_pad((string) $this->queue_number, 3, '0', STR_PAD_LEFT);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    public function clinic_doctor(): BelongsTo
    {
        return $this->belongsTo(ClinicUser::class, 'clinic_user_id')->whereHas('user', function ($query) {
            $query->where('role', RoleEnum::DOCTOR->value);
        });
    }

    public function clinic_user(): BelongsTo
    {
        return $this->belongsTo(ClinicUser::class, 'clinic_user_id');
    }


    public function treatments(): HasMany
    {
        return $this->hasMany(Treatment::class, 'appointment_id');
    }
    public function prescriptions(): HasMany
    {
        return $this->hasMany(Prescription::class, 'appointment_id');
    }

    public function satusehatObservations(): HasMany
    {
        return $this->hasMany(SatusehatObservation::class);
    }

    public function anamnesisConditions(): HasMany
    {
        return $this->hasMany(AnamnesisCondition::class);
    }

    public function clinicalImpressions(): HasMany
    {
        return $this->hasMany(ClinicalImpression::class);
    }

    public function careGoals(): HasMany
    {
        return $this->hasMany(CareGoal::class);
    }

    public function carePlans(): HasMany
    {
        return $this->hasMany(CarePlan::class);
    }
}
