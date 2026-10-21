<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CareProcedure extends Model
{
    protected $fillable = ['care_encounter_id', 'performed_by', 'name', 'notes', 'performed_at', 'category', 'attachment_path', 'attachment_name', 'attachment_mime'];
    protected $casts = ['performed_at' => 'datetime'];

    public function encounter(): BelongsTo { return $this->belongsTo(CareEncounter::class, 'care_encounter_id'); }
    public function performer(): BelongsTo { return $this->belongsTo(User::class, 'performed_by'); }
}
