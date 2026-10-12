<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Enums\GenderEnum;
use App\Enums\RoleEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;


class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'nik',
        'phone',
        'password',
        'gender',
        'fee',
        'role',
        'can_manage_medication',
        'can_access_transactions',
        'satusehat_practitioner_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'gender' => GenderEnum::class,
            'role' => RoleEnum::class,
            'can_manage_medication' => 'boolean',
            'can_access_transactions' => 'boolean',
        ];
    }

    protected static function boot()
    {
        parent::boot();
        static::created(function ($user) {
        });
        static::updating(function ($user) {
            $role = $user->getRoleNames()->first() ?? RoleEnum::OPERATOR->value;
            $user->role = $role;
        });
    }

    public function hasPermission(String $permission): bool
    {
        return in_array($permission, $this->getPermissionsViaRoles()->pluck('name')->toArray());
    }

    public function canManageMedication(): bool
    {
        return $this->hasRole([RoleEnum::SUPER_ADMIN->value, RoleEnum::ADMIN->value, RoleEnum::PHARMACIST->value])
            || ($this->hasRole(RoleEnum::DOCTOR->value) && $this->can_manage_medication === true);
    }

    public function canAccessTransactions(): bool
    {
        return $this->hasRole([RoleEnum::SUPER_ADMIN->value, RoleEnum::ADMIN->value])
            || $this->can_access_transactions === true;
    }

    public function clinics(): BelongsToMany
    {
        return
            $this->belongsToMany(Clinic::class)
            ->using(ClinicUser::class)
            ->withTimestamps();
    }

    public function clinic(): BelongsToMany
    {
        return $this->belongsToMany(Clinic::class)
            ->using(ClinicUser::class);
    }
}
