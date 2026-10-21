<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CareRoom extends Model
{
    protected $fillable = ['clinic_id', 'code', 'name', 'care_class', 'notes', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];

    public function clinic(): BelongsTo { return $this->belongsTo(Clinic::class); }
    public function beds(): HasMany { return $this->hasMany(CareBed::class); }
}
