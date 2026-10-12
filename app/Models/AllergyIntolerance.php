<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AllergyIntolerance extends Model
{
    protected $fillable = [
        'patient_id', 'category', 'code_system', 'code', 'display', 'clinical_status',
        'verification_status', 'criticality', 'reaction_code_system', 'reaction_code',
        'reaction_display', 'reaction_description', 'onset_date', 'notes',
        'satusehat_id', 'satusehat_sync_status', 'satusehat_sync_error', 'satusehat_synced_at',
    ];

    protected $casts = [
        'onset_date' => 'date',
        'satusehat_synced_at' => 'datetime',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }
}