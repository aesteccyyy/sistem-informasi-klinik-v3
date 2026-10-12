<?php

namespace App\Policies;

use App\Enums\RoleEnum;
use App\Models\TreatmentService;
use App\Models\User;

class TreatmentServicePolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->hasRole([RoleEnum::SUPER_ADMIN->value, RoleEnum::ADMIN->value]);
    }

    public function viewAny(User $user): bool
    {
        return false;
    }

    public function view(User $user, TreatmentService $treatmentService): bool
    {
        return false;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, TreatmentService $treatmentService): bool
    {
        return false;
    }

    public function delete(User $user, TreatmentService $treatmentService): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}