<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    public function run()
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $roles = [
            'super-admin',
            'approver',
            'staff',
            'teknisi',
            'admin',
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }

        $permissions = [
            // Portal & Menu
            ['name' => 'portal.access', 'guard_name' => 'web'],
            ['name' => 'ams.menu', 'guard_name' => 'web'],
            ['name' => 'helpdesk.menu', 'guard_name' => 'web'],
            ['name' => 'data-pegawai.menu', 'guard_name' => 'web'],
            ['name' => 'form-it.menu', 'guard_name' => 'web'],
            ['name' => 'sop-it.menu', 'guard_name' => 'web'],

            // AMS
            ['name' => 'ams.dashboard', 'guard_name' => 'web'],
            ['name' => 'assets.view', 'guard_name' => 'web'],
            ['name' => 'assets.create', 'guard_name' => 'web'],
            ['name' => 'assets.edit', 'guard_name' => 'web'],
            ['name' => 'assets.delete', 'guard_name' => 'web'],
            ['name' => 'assets.import', 'guard_name' => 'web'],
            ['name' => 'transactions.view', 'guard_name' => 'web'],
            ['name' => 'transactions.create', 'guard_name' => 'web'],
            ['name' => 'transactions.delete', 'guard_name' => 'web'],
            ['name' => 'transactions.approve', 'guard_name' => 'web'],
            ['name' => 'transactions.export-pdf', 'guard_name' => 'web'],
            ['name' => 'employees.view', 'guard_name' => 'web'],
            ['name' => 'employees.manage', 'guard_name' => 'web'],
            ['name' => 'employees.import', 'guard_name' => 'web'],
            ['name' => 'master-data.view', 'guard_name' => 'web'],
            ['name' => 'master-data.manage', 'guard_name' => 'web'],
            ['name' => 'assignment.view', 'guard_name' => 'web'],
            ['name' => 'assignment.manage', 'guard_name' => 'web'],
            ['name' => 'monitoring.view', 'guard_name' => 'web'],
            ['name' => 'whatsapp-settings.manage', 'guard_name' => 'web'],
            ['name' => 'settings.reset-password', 'guard_name' => 'web'],
            ['name' => 'settings.approve', 'guard_name' => 'web'],

            // Helpdesk
            ['name' => 'helpdesk.dashboard', 'guard_name' => 'web'],
            ['name' => 'tickets.view', 'guard_name' => 'web'],
            ['name' => 'tickets.view-all', 'guard_name' => 'web'],
            ['name' => 'tickets.create', 'guard_name' => 'web'],
            ['name' => 'tickets.edit', 'guard_name' => 'web'],
            ['name' => 'tickets.delete', 'guard_name' => 'web'],
            ['name' => 'tickets.assign', 'guard_name' => 'web'],
            ['name' => 'tickets.resolve', 'guard_name' => 'web'],
            ['name' => 'tickets.approve', 'guard_name' => 'web'],
            ['name' => 'tickets.confirm', 'guard_name' => 'web'],
            ['name' => 'tickets.reopen', 'guard_name' => 'web'],
            ['name' => 'tickets.comment', 'guard_name' => 'web'],
            ['name' => 'ticket-categories.manage', 'guard_name' => 'web'],
            ['name' => 'ticket-priorities.manage', 'guard_name' => 'web'],
            ['name' => 'technicians.view', 'guard_name' => 'web'],
            ['name' => 'reports.view', 'guard_name' => 'web'],
            ['name' => 'reports.export', 'guard_name' => 'web'],

            // IT Admin
            ['name' => 'it-admin.access', 'guard_name' => 'web'],
            ['name' => 'users.manage', 'guard_name' => 'web'],
            ['name' => 'roles.manage', 'guard_name' => 'web'],
            ['name' => 'permissions.manage', 'guard_name' => 'web'],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate($permission);
        }

        $admin = Role::where('name', 'admin')->first();
        $approver = Role::where('name', 'approver')->first();
        $staff = Role::where('name', 'staff')->first();
        $teknisi = Role::where('name', 'teknisi')->first();

        $admin->givePermissionTo([
            // Portal & Menu
            'portal.access', 'ams.menu', 'helpdesk.menu', 'data-pegawai.menu', 'form-it.menu', 'sop-it.menu',
            // AMS
            'ams.dashboard', 'assets.view', 'assets.create', 'assets.edit', 'assets.delete', 'assets.import',
            'transactions.view', 'transactions.create', 'transactions.delete', 'transactions.approve', 'transactions.export-pdf',
            'employees.view', 'employees.manage', 'employees.import',
            'master-data.view', 'master-data.manage',
            'assignment.view', 'assignment.manage',
            'monitoring.view', 'whatsapp-settings.manage',
            'settings.reset-password', 'settings.approve',
            // Helpdesk
            'helpdesk.dashboard', 'tickets.view', 'tickets.view-all', 'tickets.create',
            'tickets.edit', 'tickets.delete', 'tickets.assign',
            'tickets.confirm', 'tickets.reopen', 'tickets.comment',
            'ticket-categories.manage', 'ticket-priorities.manage',
            'technicians.view', 'reports.view', 'reports.export',
            // IT Admin
            'it-admin.access', 'users.manage', 'roles.manage', 'permissions.manage',
        ]);

        $approver->givePermissionTo([
            'portal.access', 'ams.menu', 'data-pegawai.menu',
            'ams.dashboard', 'assets.view',
            'transactions.view', 'transactions.approve', 'transactions.export-pdf',
            'employees.view',
            'master-data.view',
            'assignment.view',
            'monitoring.view',
            'settings.reset-password', 'settings.approve',
        ]);

        $staff->givePermissionTo([
            'portal.access', 'ams.menu', 'helpdesk.menu',
            'ams.dashboard', 'assets.view',
            'transactions.view', 'transactions.create', 'transactions.export-pdf',
            'employees.view',
            'master-data.view',
            'assignment.view',
            'monitoring.view',
            'settings.reset-password',
            'helpdesk.dashboard', 'tickets.view', 'tickets.create',
            'tickets.edit', 'tickets.delete',
            'tickets.confirm', 'tickets.reopen', 'tickets.comment',
        ]);

        $teknisi->givePermissionTo([
            'portal.access', 'helpdesk.menu',
            'settings.reset-password',
            'helpdesk.dashboard', 'tickets.view', 'tickets.comment',
            'tickets.resolve', 'tickets.approve',
        ]);
    }
}
