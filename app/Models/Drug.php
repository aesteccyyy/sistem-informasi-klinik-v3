<?php

namespace App\Models;

use BinaryCats\Sku\HasSku;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Drug extends Model
{
    use HasFactory, HasSku;

    protected $fillable = [
        'clinic_id',
        'drug_category_id',
        'sku',
        'name',
        'description',
        'stock',
        'price',
        'in_stock',
        'kfa_code',
        'kfa_display',
        'kfa_system',
        'dosage_form',
        'dosage_form_code',
        'dosage_form_system',
        'satusehat_medication_id',
    ];

    protected $casts = [
        'stock' => 'integer',
        'price' => 'integer',
        'in_stock' => 'boolean',
    ];

    public function drug_category(): BelongsTo
    {
        return $this->belongsTo(DrugCategory::class, 'drug_category_id');
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class, 'clinic_id');
    }
}
