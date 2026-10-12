<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClinicalImpression extends Model
{
    protected $fillable = [
        'appointment_id', 'status', 'investigation', 'investigation_system',
        'investigation_code', 'investigation_display', 'summary', 'prognosis_system',
        'prognosis_code', 'prognosis_display', 'satusehat_id', 'satusehat_sync_status',
        'satusehat_sync_error', 'satusehat_synced_at',
    ];

    protected $casts = ['satusehat_synced_at' => 'datetime'];

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }
}