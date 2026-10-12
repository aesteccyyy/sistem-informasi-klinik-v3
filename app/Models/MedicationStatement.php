<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MedicationStatement extends Model
{
    protected $fillable = [
        'patient_id', 'drug_id', 'medication_system', 'medication_code', 'medication_display',
        'status', 'dosage', 'effective_start', 'effective_end', 'notes',
        'satusehat_id', 'satusehat_sync_status', 'satusehat_sync_error', 'satusehat_synced_at',
    ];

    protected $casts = [
        'effective_start' => 'date',
        'effective_end' => 'date',
        'satusehat_synced_at' => 'datetime',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function drug(): BelongsTo
    {
        return $this->belongsTo(Drug::class);
    }
}