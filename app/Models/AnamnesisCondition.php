<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnamnesisCondition extends Model
{
    protected $fillable = [
        'appointment_id', 'clinical_status', 'verification_status', 'code', 'display',
        'onset_date', 'notes', 'satusehat_id', 'satusehat_sync_status',
        'satusehat_sync_error', 'satusehat_synced_at',
    ];

    protected $casts = [
        'onset_date' => 'date',
        'satusehat_synced_at' => 'datetime',
    ];

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }
}