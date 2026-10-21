<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CareEncounter extends Model
{
    protected $fillable = [
        'clinic_id', 'patient_id', 'appointment_id', 'clinic_user_id', 'care_room_id', 'care_bed_id', 'created_by',
        'encounter_number', 'care_type', 'status', 'arrived_at', 'admission_source', 'admission_method', 'guarantor',
        'initial_diagnosis', 'admission_reason', 'triage_level', 'discharged_at', 'discharge_method',
        'discharge_diagnosis', 'discharge_instructions', 'discharge_medications', 'follow_up_instructions',
        'discharge_history_summary', 'discharge_exam_summary', 'discharge_supporting_summary', 'discharge_treatment_summary',
        'discharge_progress_summary', 'discharge_condition', 'discharge_red_flags', 'discharge_signed_by', 'discharge_signed_at',
        'voided_at', 'voided_by', 'void_reason',
    ];

    protected $casts = ['arrived_at' => 'datetime', 'discharged_at' => 'datetime', 'discharge_signed_at' => 'datetime', 'voided_at' => 'datetime'];

    public function clinic(): BelongsTo { return $this->belongsTo(Clinic::class); }
    public function patient(): BelongsTo { return $this->belongsTo(Patient::class); }
    public function appointment(): BelongsTo { return $this->belongsTo(Appointment::class); }
    public function doctor(): BelongsTo { return $this->belongsTo(ClinicUser::class, 'clinic_user_id'); }
    public function dischargeSigner(): BelongsTo { return $this->belongsTo(User::class, 'discharge_signed_by'); }
    public function room(): BelongsTo { return $this->belongsTo(CareRoom::class, 'care_room_id'); }
    public function bed(): BelongsTo { return $this->belongsTo(CareBed::class, 'care_bed_id'); }
    public function notes(): HasMany { return $this->hasMany(CareNote::class); }
    public function medications(): HasMany { return $this->hasMany(CareMedication::class); }
    public function procedures(): HasMany { return $this->hasMany(CareProcedure::class); }
}
