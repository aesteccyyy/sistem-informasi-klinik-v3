<?php

use App\Enums\RoleEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') return;

        $roles = collect(RoleEnum::values())->map(fn (string $role) => DB::getPdo()->quote($role))->implode(',');
        DB::statement("ALTER TABLE users MODIFY role ENUM({$roles}) NOT NULL DEFAULT 'operator'");

        $now = now();
        $roleId = DB::table('roles')->where('name', RoleEnum::NURSE->value)->where('guard_name', 'web')->value('id');
        if (!$roleId) {
            $roleId = DB::table('roles')->insertGetId([
                'name' => RoleEnum::NURSE->value, 'guard_name' => 'web', 'created_at' => $now, 'updated_at' => $now,
            ]);
        }

        $permissionNames = [
            'view_any_patient', 'view_patient', 'create_patient', 'update_patient',
            'view_any_appointment', 'view_appointment', 'update_appointment',
            'view_any_drug', 'view_drug', 'view_any_prescription', 'view_prescription',
        ];
        $permissionIds = DB::table('permissions')->where('guard_name', 'web')->whereIn('name', $permissionNames)->pluck('id');
        foreach ($permissionIds as $permissionId) {
            DB::table('role_has_permissions')->insertOrIgnore([
                'permission_id' => $permissionId, 'role_id' => $roleId,
            ]);
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') return;
        $roleId = DB::table('roles')->where('name', RoleEnum::NURSE->value)->where('guard_name', 'web')->value('id');
        if (DB::table('users')->where('role', RoleEnum::NURSE->value)->exists()
            || ($roleId && DB::table('model_has_roles')->where('role_id', $roleId)->exists())) {
            throw new RuntimeException('Cannot remove the nurse role while nurse accounts still exist.');
        }

        if ($roleId) {
            DB::table('role_has_permissions')->where('role_id', $roleId)->delete();
            DB::table('roles')->where('id', $roleId)->delete();
        }

        $roles = collect(RoleEnum::values())->reject(fn (string $role) => $role === RoleEnum::NURSE->value)
            ->map(fn (string $role) => DB::getPdo()->quote($role))->implode(',');
        DB::statement("ALTER TABLE users MODIFY role ENUM({$roles}) NOT NULL DEFAULT 'operator'");
    }
};
