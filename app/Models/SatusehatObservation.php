<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SatusehatObservation extends Model
{
    protected $table = 'satusehat_observations';

    protected $fillable = [
        'appointment_id', 'category', 'code_system', 'code', 'display', 'value_type',
        'value_quantity', 'value_string', 'unit', 'unit_code', 'notes',
        'satusehat_id', 'satusehat_sync_status', 'satusehat_sync_error', 'satusehat_synced_at',
    ];

    protected $casts = [
        'value_quantity' => 'decimal:4',
        'satusehat_synced_at' => 'datetime',
    ];

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }
}