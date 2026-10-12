<?php

namespace App\Models;

use App\Enums\PaymentStatusEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transaction extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::deleting(function (Transaction $transaction): void {
            $transaction->bills()->delete();
        });
    }

    protected $fillable = [
        'appointment_id',
        'patient_name',
        'patient_phone',
        'patient_age',
        'doctor_name',
        'doctor_phone',
        'appointment_date',
        'note',
        'amount_treatments',
        'amount_prescriptions',
        'amount_total',
        'payment_receipt',
        'status',
        'paid_at',
    ];

    protected $casts = [
        'status' => PaymentStatusEnum::class,
        'paid_at' => 'datetime',
        'appointment_date' => 'datetime'
    ];

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class, 'appointment_id');
    }

    public function bills(): HasMany
    {
        return $this->hasMany(Bill::class, 'transaction_id');
    }
}
