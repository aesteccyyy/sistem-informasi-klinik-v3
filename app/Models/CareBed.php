<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CareBed extends Model
{
    protected $fillable = ['care_room_id', 'code', 'status', 'notes'];

    public function room(): BelongsTo { return $this->belongsTo(CareRoom::class, 'care_room_id'); }
}
