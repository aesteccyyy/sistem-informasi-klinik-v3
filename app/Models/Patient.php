<?php

namespace App\Models;

use App\Enums\BloodTypeEnum;
use App\Enums\GenderEnum;
use App\Enums\MaritalStatusEnum;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Patient extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'nik',
        'birth_date',
        'multiple_birth',
        'phone',
        'gender',
        'marital_status',
        'blood_type',
        'bio',
        'address',
        'province_id',
        'regency_id',
        'district_id',
        'village_id',
        'satusehat_patient_id',
        'satusehat_sync_status',
        'satusehat_sync_error',
        'satusehat_synced_at',
    ];

    protected $casts = [
        'birth_date' => 'datetime',
        'multiple_birth' => 'boolean',
        'satusehat_synced_at' => 'datetime',
        'gender' => GenderEnum::class,
        'marital_status' => MaritalStatusEnum::class,
        'blood_type' => BloodTypeEnum::class,
        'province_id' => 'string',
        'regency_id' => 'string',
        'district_id' => 'string',
        'village_id' => 'string',
    ];

    protected static function booted()
    {
        parent::booted();
        static::saving(function ($patient) {
            $patient->age = $patient->birth_date
                ? Carbon::parse($patient->birth_date)->age
                : 0;
        });
    }

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class);
    }

    public function regency(): BelongsTo
    {
        return $this->belongsTo(Regency::class);
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function village(): BelongsTo
    {
        return $this->belongsTo(Village::class);
    }
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'patient_id');
    }

    public function familyMemberHistories(): HasMany
    {
        return $this->hasMany(FamilyMemberHistory::class);
    }

    public function allergyIntolerances(): HasMany
    {
        return $this->hasMany(AllergyIntolerance::class);
    }

    public function medicationStatements(): HasMany
    {
        return $this->hasMany(MedicationStatement::class);
    }
}
