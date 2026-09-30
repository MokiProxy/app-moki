<?php

namespace Tests\Concerns;

use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Helper untuk menguji permission per role ERKAP tanpa bersandar pada
 * `ActsAsSuperAdmin`, karena matriks izin adalah hal yang paling mudah
 * terlewat bila seluruh test memakai super-admin.
 */
trait ActsAsErkapRole
{
    protected function actAsErkapRole(string $roleName): User
    {
        $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);

        // Permission dibuat eksplisit agar test gagal pada nama permission yang
        // salah, bukan diam-diam lolos karena gate tidak pernah dievaluasi.
        foreach ($this->permissionsFor($roleName) as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
            $role->givePermissionTo($permission);
        }

        $user = User::factory()->create();
        $user->assignRole($role);
        $this->actingAs($user);

        return $user;
    }

    /**
     * @return list<string>
     */
    private function permissionsFor(string $roleName): array
    {
        return match ($roleName) {
            'erkap-admin' => [
                'erkap.menu',
                'erkap.structure.view', 'erkap.structure.edit',
                'erkap.business-units.view', 'erkap.business-units.create',
                'erkap.business-units.edit', 'erkap.business-units.delete',
                'erkap.locations.view', 'erkap.locations.create',
                'erkap.locations.edit', 'erkap.locations.delete',
                'erkap.management-areas.view', 'erkap.management-areas.create',
                'erkap.management-areas.edit', 'erkap.management-areas.delete',
                'erkap.activities.view', 'erkap.activities.create',
                'erkap.activities.edit', 'erkap.activities.delete',
                'erkap.cost-centers.view', 'erkap.cost-centers.create',
                'erkap.cost-centers.edit', 'erkap.cost-centers.delete',
                'erkap.chart-of-accounts.view', 'erkap.chart-of-accounts.create',
                'erkap.chart-of-accounts.edit', 'erkap.chart-of-accounts.delete',
            ],
            'erkap-cost-owner' => [
                'erkap.menu',
                'erkap.routine-costs.view',
            ],
            'erkap-auditor' => [
                'erkap.menu',
                'erkap.structure.view',
                'erkap.business-units.view',
                'erkap.activities.view',
                'erkap.chart-of-accounts.view',
            ],
            default => ['erkap.menu'],
        };
    }
}
