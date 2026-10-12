<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Icd10Code extends Model
{
    protected $primaryKey = 'code';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'code',
        'description',
        'version',
    ];

    public function getDisplayAttribute(): string
    {
        return $this->code . ' - ' . $this->description;
    }
}