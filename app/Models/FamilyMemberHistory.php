<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FamilyMemberHistory extends Model
{
    protected $fillable = [
        'patient_id', 'status', 'relationship_code', 'relationship_display',
        'condition_code', 'condition_display', 'onset_age', 'notes',
        'satusehat_id', 'satusehat_sync_status', 'satusehat_sync_error', 'satusehat_synced_at',
    ];

    protected $casts = ['satusehat_synced_at' => 'datetime'];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }
}