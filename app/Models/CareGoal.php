<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CareGoal extends Model
{
    protected $fillable = [
        'appointment_id', 'lifecycle_status', 'description_system', 'description_code',
        'description_display', 'start_date', 'target_detail', 'target_due_date', 'notes',
        'satusehat_id', 'satusehat_sync_status', 'satusehat_sync_error', 'satusehat_synced_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'target_due_date' => 'date',
        'satusehat_synced_at' => 'datetime',
    ];

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }
}