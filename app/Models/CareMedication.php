<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CareMedication extends Model
{
    protected $fillable = ['care_encounter_id', 'drug_id', 'ordered_by', 'administered_by', 'dosage_instruction', 'dose', 'route', 'frequency', 'duration_days', 'quantity', 'administered_quantity', 'status', 'administered_at', 'notes'];
    protected $casts = ['duration_days' => 'integer', 'quantity' => 'integer', 'administered_quantity' => 'integer', 'administered_at' => 'datetime'];

    public function encounter(): BelongsTo { return $this->belongsTo(CareEncounter::class, 'care_encounter_id'); }
    public function drug(): BelongsTo { return $this->belongsTo(Drug::class); }
}
