<?php

namespace App\Models;

use App\Enums\AppointmentStatusEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class Prescription extends Model
{
    use HasFactory;

    protected $fillable = [
        'drug_id', 'appointment_id', 'description', 'quantity', 'price', 'discount', 'amount', 'is_prepared',
        'dosage_instruction', 'dose_value', 'dose_unit', 'frequency', 'route', 'duration_days', 'quantity_unit', 'pharmacy_status',
        'dispensed_quantity', 'dispensed_kfa_code', 'dispensed_kfa_display', 'pharmacy_note', 'reviewed_by', 'reviewed_at', 'dispensed_by', 'dispensed_at',
        'rejection_reason', 'satusehat_medication_request_id',
        'satusehat_medication_dispense_id', 'satusehat_sync_status', 'satusehat_sync_error', 'satusehat_synced_at',
    ];

    protected $casts = [
        'quantity' => 'integer', 'price' => 'integer', 'discount' => 'integer', 'amount' => 'integer',
        'is_prepared' => 'boolean', 'dispensed_quantity' => 'integer', 'duration_days' => 'integer',
        'dose_value' => 'decimal:3',
        'reviewed_at' => 'datetime', 'dispensed_at' => 'datetime', 'satusehat_synced_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $prescription): void {
            $appointment = Appointment::query()->find($prescription->appointment_id);
            if ($appointment?->status === AppointmentStatusEnum::CONFIRMED) {
                throw ValidationException::withMessages([
                    'appointment_id' => 'Appointment yang sudah dikonfirmasi tidak dapat menerima resep baru atau perubahan resep.',
                ]);
            }
            $drug = Drug::query()->findOrFail($prescription->drug_id);
            $prescription->price = $drug->price;
            $discount = max(0, min(100, (int) $prescription->discount));
            $prescription->discount = $discount;
            $prescription->amount = (int) round($prescription->quantity * $drug->price * (100 - $discount) / 100);
        });

        static::deleting(function (self $prescription): void {
            $quantity = (int) $prescription->dispensed_quantity;
            if ($quantity < 1) {
                return;
            }

            DB::transaction(function () use ($prescription, $quantity): void {
                $drug = Drug::query()->lockForUpdate()->find($prescription->drug_id);
                if (!$drug) {
                    return;
                }
                $before = (int) $drug->stock;
                $drug->stock = $before + $quantity;
                $drug->in_stock = true;
                $drug->save();
                DB::table('drug_stock_movements')->insert([
                    'drug_id' => $drug->id, 'prescription_id' => $prescription->id, 'user_id' => auth()->id(),
                    'movement_type' => 'return', 'quantity' => $quantity, 'stock_before' => $before,
                    'stock_after' => $drug->stock, 'notes' => 'Pengembalian stok karena resep dihapus',
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            });
        });

        static::saved(function (self $prescription): void {
            $appointment = $prescription->appointment;
            if (!$appointment || !$appointment->prescriptions()->exists()) {
                return;
            }

            $allProcessed = !$appointment->prescriptions()->whereNotIn('pharmacy_status', ['dispensed', 'rejected'])->exists();
            if ($allProcessed && $appointment->status === AppointmentStatusEnum::DIAGNOSED) {
                $appointment->status = AppointmentStatusEnum::PREPARED;
                $appointment->save();
            }
        });
    }

    public function drug(): BelongsTo
    {
        return $this->belongsTo(Drug::class, 'drug_id');
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class, 'appointment_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function dispenser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dispensed_by');
    }

    public function setPrepare(): bool
    {
        if ($this->pharmacy_status !== 'ordered') {
            throw new RuntimeException('Resep ini tidak lagi menunggu pemeriksaan apotik.');
        }

        $this->forceFill([
            'pharmacy_status' => 'verified',
            'is_prepared' => true,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        return $this->save();
    }
}
