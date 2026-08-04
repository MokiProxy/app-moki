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
            ['name' => 'ams.assets.view', 'guard_name' => 'web'],
            ['name' => 'ams.assets.create', 'guard_name' => 'web'],
            ['name' => 'ams.assets.edit', 'guard_name' => 'web'],
            ['name' => 'ams.assets.delete', 'guard_name' => 'web'],
            ['name' => 'ams.assets.import', 'guard_name' => 'web'],
            ['name' => 'ams.transactions.view', 'guard_name' => 'web'],
            ['name' => 'ams.transactions.create', 'guard_name' => 'web'],
            ['name' => 'ams.transactions.delete', 'guard_name' => 'web'],
            ['name' => 'ams.transactions.approve', 'guard_name' => 'web'],
            ['name' => 'ams.transactions.export-pdf', 'guard_name' => 'web'],
            ['name' => 'ams.employees.view', 'guard_name' => 'web'],
            ['name' => 'ams.employees.manage', 'guard_name' => 'web'],
            ['name' => 'ams.employees.import', 'guard_name' => 'web'],
            ['name' => 'ams.master-data.view', 'guard_name' => 'web'],
            ['name' => 'ams.master-data.manage', 'guard_name' => 'web'],
            ['name' => 'ams.assignment.view', 'guard_name' => 'web'],
            ['name' => 'ams.assignment.manage', 'guard_name' => 'web'],
            ['name' => 'ams.monitoring.view', 'guard_name' => 'web'],
            ['name' => 'ams.whatsapp-settings.manage', 'guard_name' => 'web'],
            ['name' => 'ams.settings.reset-password', 'guard_name' => 'web'],
            ['name' => 'ams.settings.approve', 'guard_name' => 'web'],

            // Helpdesk
            ['name' => 'helpdesk.dashboard', 'guard_name' => 'web'],
            ['name' => 'helpdesk.tickets.view', 'guard_name' => 'web'],
            ['name' => 'helpdesk.tickets.view-all', 'guard_name' => 'web'],
            ['name' => 'helpdesk.tickets.create', 'guard_name' => 'web'],
            ['name' => 'helpdesk.tickets.edit', 'guard_name' => 'web'],
            ['name' => 'helpdesk.tickets.delete', 'guard_name' => 'web'],
            ['name' => 'helpdesk.tickets.assign', 'guard_name' => 'web'],
            ['name' => 'helpdesk.tickets.resolve', 'guard_name' => 'web'],
            ['name' => 'helpdesk.tickets.approve', 'guard_name' => 'web'],
            ['name' => 'helpdesk.tickets.confirm', 'guard_name' => 'web'],
            ['name' => 'helpdesk.tickets.reopen', 'guard_name' => 'web'],
            ['name' => 'helpdesk.tickets.comment', 'guard_name' => 'web'],
            ['name' => 'helpdesk.ticket-categories.manage', 'guard_name' => 'web'],
            ['name' => 'helpdesk.ticket-priorities.manage', 'guard_name' => 'web'],
            ['name' => 'helpdesk.technicians.view', 'guard_name' => 'web'],
            ['name' => 'helpdesk.reports.view', 'guard_name' => 'web'],
            ['name' => 'helpdesk.reports.export', 'guard_name' => 'web'],

            // IT Admin
            ['name' => 'it-admin.access', 'guard_name' => 'web'],
            ['name' => 'it-admin.users.manage', 'guard_name' => 'web'],
            ['name' => 'it-admin.roles.manage', 'guard_name' => 'web'],
            ['name' => 'it-admin.permissions.manage', 'guard_name' => 'web'],

            // Dokter
            ['name' => 'dokter.menu', 'guard_name' => 'web'],
            ['name' => 'dokter.dashboard', 'guard_name' => 'web'],
            ['name' => 'dokter.vendors.view', 'guard_name' => 'web'],
            ['name' => 'dokter.vendors.create', 'guard_name' => 'web'],
            ['name' => 'dokter.vendors.edit', 'guard_name' => 'web'],
            ['name' => 'dokter.vendors.delete', 'guard_name' => 'web'],
            ['name' => 'dokter.document-types.view', 'guard_name' => 'web'],
            ['name' => 'dokter.document-types.create', 'guard_name' => 'web'],
            ['name' => 'dokter.document-types.edit', 'guard_name' => 'web'],
            ['name' => 'dokter.document-types.delete', 'guard_name' => 'web'],
            ['name' => 'dokter.file-managements.view', 'guard_name' => 'web'],
            ['name' => 'dokter.file-managements.download', 'guard_name' => 'web'],
            ['name' => 'dokter.log-file.view', 'guard_name' => 'web'],
            ['name' => 'dokter.log-file.export', 'guard_name' => 'web'],
            ['name' => 'dokter.merge-flows.view', 'guard_name' => 'web'],
            ['name' => 'dokter.merge-flows.create', 'guard_name' => 'web'],
            ['name' => 'dokter.merge-flows.edit', 'guard_name' => 'web'],
            ['name' => 'dokter.merge-flows.delete', 'guard_name' => 'web'],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate($permission);
        }

        $admin = Role::where('name', 'admin')->first();
        $approver = Role::where('name', 'approver')->first();
        $staff = Role::where('name', 'staff')->first();
        $teknisi = Role::where('name', 'teknisi')->first();

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
            'ams.dashboard', 'ams.assets.view', 'ams.assets.create', 'ams.assets.edit', 'ams.assets.delete', 'ams.assets.import',
            'ams.transactions.view', 'ams.transactions.create', 'ams.transactions.delete', 'ams.transactions.approve', 'ams.transactions.export-pdf',
            'ams.employees.view', 'ams.employees.manage', 'ams.employees.import',
            'ams.master-data.view', 'ams.master-data.manage',
            'ams.assignment.view', 'ams.assignment.manage',
            'ams.monitoring.view', 'ams.whatsapp-settings.manage',
            'ams.settings.reset-password', 'ams.settings.approve',
            // Helpdesk
            'helpdesk.dashboard', 'helpdesk.tickets.view', 'helpdesk.tickets.view-all', 'helpdesk.tickets.create',
            'helpdesk.tickets.edit', 'helpdesk.tickets.delete', 'helpdesk.tickets.assign',
            'helpdesk.tickets.confirm', 'helpdesk.tickets.reopen', 'helpdesk.tickets.comment',
            'helpdesk.ticket-categories.manage', 'helpdesk.ticket-priorities.manage',
            'helpdesk.technicians.view', 'helpdesk.reports.view', 'helpdesk.reports.export',
            // IT Admin
            'it-admin.access', 'it-admin.users.manage', 'it-admin.roles.manage', 'it-admin.permissions.manage',
            // Dokter
            'dokter.menu', 'dokter.dashboard',
            'dokter.vendors.view', 'dokter.vendors.create', 'dokter.vendors.edit', 'dokter.vendors.delete',
            'dokter.document-types.view', 'dokter.document-types.create', 'dokter.document-types.edit', 'dokter.document-types.delete',
            'dokter.file-managements.view', 'dokter.file-managements.download',
            'dokter.log-file.view', 'dokter.log-file.export',
        ]);

        $approver->givePermissionTo([
            'portal.access', 'ams.menu', 'data-pegawai.menu',
            'ams.dashboard', 'ams.assets.view',
            'ams.transactions.view', 'ams.transactions.approve', 'ams.transactions.export-pdf',
            'ams.employees.view',
            'ams.master-data.view',
            'ams.assignment.view',
            'ams.monitoring.view',
            'ams.settings.reset-password', 'ams.settings.approve',
            'helpdesk.dashboard', 'helpdesk.tickets.view', 'helpdesk.tickets.view-all',
            'helpdesk.technicians.view', 'helpdesk.reports.view',
        ]);

        $staff->givePermissionTo([
            'portal.access', 'ams.menu', 'helpdesk.menu',
            'ams.dashboard', 'ams.assets.view',
            'ams.transactions.view', 'ams.transactions.create', 'ams.transactions.export-pdf',
            'ams.employees.view',
            'ams.master-data.view',
            'ams.assignment.view',
            'ams.monitoring.view',
            'ams.settings.reset-password',
            'helpdesk.dashboard', 'helpdesk.tickets.view', 'helpdesk.tickets.create',
            'helpdesk.tickets.edit', 'helpdesk.tickets.delete',
            'helpdesk.tickets.confirm', 'helpdesk.tickets.reopen', 'helpdesk.tickets.comment',
        ]);

        $teknisi->givePermissionTo([
            'portal.access', 'helpdesk.menu',
            'ams.settings.reset-password',
            'helpdesk.dashboard', 'helpdesk.tickets.view', 'helpdesk.tickets.comment',
            'helpdesk.tickets.resolve', 'helpdesk.tickets.approve',
            'helpdesk.technicians.view',
        ]);
    }
}
