<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CareNote extends Model
{
    protected $fillable = ['care_encounter_id', 'author_id', 'note_type', 'subjective', 'objective', 'assessment', 'plan', 'observations', 'noted_at'];
    protected $casts = ['observations' => 'array', 'noted_at' => 'datetime'];

    public function encounter(): BelongsTo { return $this->belongsTo(CareEncounter::class, 'care_encounter_id'); }
    public function author(): BelongsTo { return $this->belongsTo(User::class, 'author_id'); }
}
