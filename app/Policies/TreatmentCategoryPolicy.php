<?php

namespace App\Policies;

use App\Enums\RoleEnum;
use App\Models\TreatmentCategory;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class TreatmentCategoryPolicy
{
    public function before(User $user, string $ability): bool|null
    {
        if ($user->hasRole(RoleEnum::SUPER_ADMIN->value) || $user->hasRole(RoleEnum::ADMIN->value)) {
            return true;
        }

        return null; // see the note above in Gate::before about why null must be returned here.
    }
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('view_any_treatment_category');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, TreatmentCategory $treatmentCategory): bool
    {
        return $user->hasPermission('view_treatment_category');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasRole([RoleEnum::SUPER_ADMIN->value, RoleEnum::ADMIN->value]);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, TreatmentCategory $treatmentCategory): bool
    {
        return $user->hasRole([RoleEnum::SUPER_ADMIN->value, RoleEnum::ADMIN->value]);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function deleteAny(User $user): bool
    {
        return $user->hasRole([RoleEnum::SUPER_ADMIN->value, RoleEnum::ADMIN->value]);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, TreatmentCategory $treatmentCategory): bool
    {
        return $user->hasRole([RoleEnum::SUPER_ADMIN->value, RoleEnum::ADMIN->value]);
    }
}
