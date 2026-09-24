<?php

namespace Tests\Concerns;

use App\Models\User;
use Spatie\Permission\Models\Role;

trait ActsAsSuperAdmin
{
    protected User $user;

    protected function setUpSuperAdmin(): void
    {
        $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $this->user = User::factory()->create();
        $this->user->assignRole($role);
        $this->actingAs($this->user);
    }
}