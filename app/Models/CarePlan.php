<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CarePlan extends Model
{
    protected $fillable = [
        'appointment_id', 'plan_type', 'status', 'intent', 'title', 'description',
        'activity_description', 'activity_status', 'scheduled_string', 'notes',
        'satusehat_id', 'satusehat_sync_status', 'satusehat_sync_error', 'satusehat_synced_at',
    ];

    protected $casts = ['satusehat_synced_at' => 'datetime'];

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }
}