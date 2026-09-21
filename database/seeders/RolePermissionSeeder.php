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
            'teknisi',
            'admin',
            "staff",

            // IT Admin
            'it-admin',

            // Helpdesk roles
            'helpdesk-user',
            'helpdesk-technician',
            'helpdesk-admin',

            // Form IT roles
            "form-it-user",
            "form-it-approver",

            // Dokter roles
            'dokter-user',
            'dokter-admin',

            // EQTAX roles
            'eqtax-user',

            // E-RKAP roles
            'erkap-cost-owner',
            'erkap-admin',
            'erkap-ppk',
            'erkap-controller',
            'erkap-direksi-keuangan',
            'erkap-direksi',
            'erkap-komisaris',
            'erkap-accounting',
            'erkap-risk-manager',
            'erkap-auditor',
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
            ['name' => 'eqtax.menu', 'guard_name' => 'web'],

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
            ['name' => 'dokter.file-managements.validate', 'guard_name' => 'web'],
            ['name' => 'dokter.log-file.view', 'guard_name' => 'web'],
            ['name' => 'dokter.log-file.export', 'guard_name' => 'web'],
            ['name' => 'dokter.merge-flows.view', 'guard_name' => 'web'],
            ['name' => 'dokter.merge-flows.create', 'guard_name' => 'web'],
            ['name' => 'dokter.merge-flows.edit', 'guard_name' => 'web'],
            ['name' => 'dokter.merge-flows.delete', 'guard_name' => 'web'],
            ['name' => 'dokter.auditor-access.manage', 'guard_name' => 'web'],

            // EQTAX
            ['name' => 'eqtax.dashboard', 'guard_name' => 'web'],
            ['name' => 'eqtax.spt.coretax.view', 'guard_name' => 'web'],
            ['name' => 'eqtax.spt.coretax.import', 'guard_name' => 'web'],
            ['name' => 'eqtax.spt.coretax.update-field', 'guard_name' => 'web'],
            ['name' => 'eqtax.gl.view', 'guard_name' => 'web'],
            ['name' => 'eqtax.gl.import', 'guard_name' => 'web'],
            ['name' => 'eqtax.gl.update-field', 'guard_name' => 'web'],
            ['name' => 'eqtax.equalization.view', 'guard_name' => 'web'],
            ['name' => 'eqtax.equalization.process', 'guard_name' => 'web'],
            ['name' => 'eqtax.equalization.export', 'guard_name' => 'web'],
            ['name' => 'eqtax.tb.view', 'guard_name' => 'web'],
            ['name' => 'eqtax.tb.process', 'guard_name' => 'web'],
            ['name' => 'eqtax.tb.save', 'guard_name' => 'web'],

            // Form IT
            ['name' => 'form-it.dashboard', 'guard_name' => 'web'],
            ['name' => 'form-it.forms.view', 'guard_name' => 'web'],
            ['name' => 'form-it.forms.create', 'guard_name' => 'web'],
            ['name' => 'form-it.approval.view', 'guard_name' => 'web'],
            ['name' => 'form-it.approval.process', 'guard_name' => 'web'],

            // Form IT - Fixed Asset
            ['name' => 'form-it.fixed-asset.view', 'guard_name' => 'web'],
            ['name' => 'form-it.fixed-asset.create', 'guard_name' => 'web'],
            ['name' => 'form-it.fixed-asset.approve', 'guard_name' => 'web'],
        ];

        $erkapResources = [
            'cost-element-categories',
            'cost-elements',
            'chart-of-accounts',
            'risk-appetites',
            'risk-taxonomies',
            'risk-types',
            'rating-criterias',
            'risk-scales',
            'risk-probabilities',
            'risk-impacts',
            'risk-score-levels',
            'investation-types',
            'investation-criterias',
            'investattion-categories',
            'rkap',
            'company-targets',
            'department-targets',
            'risk-identifications',
            'risk-identification-reasons',
            'risk-identification-impacts',
            'risk-analysis',
            'risk-rankings',
            'department-risk-strategies',
            'work-programs',
            'routine-costs',
            'cost-centers',
            'investment-plans',
            'budget-capex',
            'revenue-plans',
            'expense-plans',
            'profit-loss',
        ];

        $erkapActions = ['view', 'create', 'edit', 'delete'];

        foreach ($erkapResources as $resource) {
            foreach ($erkapActions as $action) {
                $permissions[] = ['name' => "erkap.{$resource}.{$action}", 'guard_name' => 'web'];
            }
        }

        $permissions[] = ['name' => 'erkap.menu', 'guard_name' => 'web'];

        $approvalResourceActions = [
            'submit', 'approve', 'reject',
        ];

        $approvalResources = [
            'work-programs',
            'routine-costs',
            'investment-plans',
            'rkap',
            'budget-capex',
        ];

        foreach ($approvalResources as $resource) {
            foreach ($approvalResourceActions as $action) {
                $permissions[] = ['name' => "erkap.{$resource}.{$action}", 'guard_name' => 'web'];
            }
        }

        $permissions[] = ['name' => 'erkap.approvals.view', 'guard_name' => 'web'];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate($permission);
        }

        $allPermissions = Permission::all();

        $superAdmin = Role::where('name', 'super-admin')->first();
        $admin = Role::where('name', 'admin')->first();
        $approver = Role::where('name', 'approver')->first();
        $staff = Role::where('name', 'staff')->first();
        $teknisi = Role::where('name', 'teknisi')->first();

        // IT Admin
        $itAdmin = Role::where('name', 'it-admin')->first();

        // Helpdesk
        $helpdeskUser = Role::where('name', 'helpdesk-user')->first();
        $helpdeskTechnician = Role::where('name', 'helpdesk-technician')->first();
        $helpdeskAdmin = Role::where('name', 'helpdesk-admin')->first();

        // Form IT
        $formItUser = Role::where('name', 'form-it-user')->first();
        $formItApprover = Role::where('name', 'form-it-approver')->first();

        // Dokter
        $dokterUser = Role::where('name', 'dokter-user')->first();
        $dokterAdmin = Role::where('name', 'dokter-admin')->first();

        // EQTAX
        $eqtaxUser = Role::where('name', 'eqtax-user')->first();

        // E-RKAP
        $erkapCostOwner = Role::where('name', 'erkap-cost-owner')->first();
        $erkapAdmin = Role::where('name', 'erkap-admin')->first();
        $erkapPpk = Role::where('name', 'erkap-ppk')->first();
        $erkapController = Role::where('name', 'erkap-controller')->first();
        $erkapDireksiKeuangan = Role::where('name', 'erkap-direksi-keuangan')->first();
        $erkapDireksi = Role::where('name', 'erkap-direksi')->first();
        $erkapKomisaris = Role::where('name', 'erkap-komisaris')->first();
        $erkapAccounting = Role::where('name', 'erkap-accounting')->first();
        $erkapRiskManager = Role::where('name', 'erkap-risk-manager')->first();
        $erkapAuditor = Role::where('name', 'erkap-auditor')->first();

        $toEkapPermissions = function (array $resources) use ($erkapActions) {
            $names = ['erkap.menu'];

            foreach ($resources as $resource) {
                foreach ($erkapActions as $action) {
                    $names[] = "erkap.{$resource}.{$action}";
                }
            }

            return $names;
        };

        $costOwnerResources = [
            'department-targets',
            'risk-identifications',
            'risk-identification-reasons',
            'risk-identification-impacts',
            'risk-analysis',
            'risk-rankings',
            'department-risk-strategies',
            'work-programs',
            'routine-costs',
            'investment-plans',
            'revenue-plans',
            'expense-plans',
            'profit-loss',
        ];

        $costOwnerPermissions = $toEkapPermissions($costOwnerResources);
        $erkapAdminPermissions = $toEkapPermissions($erkapResources);

        $approvalPermissions = [
            'erkap.approvals.view',
            'erkap.work-programs.submit',
            'erkap.work-programs.approve',
            'erkap.work-programs.reject',
            'erkap.routine-costs.submit',
            'erkap.routine-costs.approve',
            'erkap.routine-costs.reject',
            'erkap.investment-plans.submit',
            'erkap.investment-plans.approve',
            'erkap.investment-plans.reject',
            'erkap.rkap.submit',
            'erkap.rkap.approve',
            'erkap.rkap.reject',
            'erkap.budget-capex.submit',
            'erkap.budget-capex.approve',
            'erkap.budget-capex.reject',
        ];

        $submitPermissions = function ($resources) {
            return collect($resources)->map(fn ($resource) => "erkap.{$resource}.submit")->values()->all();
        };

        $erkapCostOwner->givePermissionTo($costOwnerPermissions);
        $erkapCostOwner->revokePermissionTo([
            'erkap.company-targets.view',
            'erkap.company-targets.create',
            'erkap.company-targets.edit',
            'erkap.company-targets.delete',
        ]);
        $erkapCostOwner->givePermissionTo($submitPermissions([
            'work-programs',
            'routine-costs',
            'investment-plans',
        ]));

        $erkapAdmin->givePermissionTo($erkapAdminPermissions);
        $erkapAdmin->givePermissionTo($approvalPermissions);

        $erkapPpk->givePermissionTo([
            'erkap.approvals.view',
            'erkap.work-programs.approve',
            'erkap.work-programs.reject',
            'erkap.routine-costs.approve',
            'erkap.routine-costs.reject',
            'erkap.investment-plans.approve',
            'erkap.investment-plans.reject',
        ]);

        $erkapController->givePermissionTo($erkapPpk->permissions->pluck('name')->all());
        $erkapController->givePermissionTo([
            'erkap.rkap.approve',
            'erkap.rkap.reject',
        ]);

        $erkapDireksiKeuangan->givePermissionTo([
            'erkap.approvals.view',
            'erkap.investment-plans.approve',
            'erkap.investment-plans.reject',
        ]);

        $erkapDireksi->givePermissionTo([
            'erkap.approvals.view',
            'erkap.rkap.approve',
            'erkap.rkap.reject',
        ]);

        $erkapKomisaris->givePermissionTo([
            'erkap.approvals.view',
            'erkap.rkap.approve',
            'erkap.rkap.reject',
        ]);

        $erkapAccounting->givePermissionTo(['erkap.approvals.view']);
        $erkapRiskManager->givePermissionTo(['erkap.approvals.view']);
        $erkapAuditor->givePermissionTo(['erkap.approvals.view']);

        $eqtaxUser->givePermissionTo([
            'eqtax.menu',
            'eqtax.dashboard',
            'eqtax.spt.coretax.view',
            'eqtax.spt.coretax.import',
            'eqtax.spt.coretax.update-field',
            'eqtax.gl.view',
            'eqtax.gl.import',
            'eqtax.gl.update-field',
            'eqtax.equalization.view',
            'eqtax.equalization.process',
            'eqtax.equalization.export',
            'eqtax.tb.view',
            'eqtax.tb.process',
            'eqtax.tb.save',
        ]);

        $superAdmin->syncPermissions($allPermissions);

        $itAdmin->givePermissionTo([
            'it-admin.access',
            'it-admin.permissions.manage',
            'it-admin.roles.manage',
            'it-admin.users.manage',
        ]);

        $helpdeskAdmin->givePermissionTo([
            'helpdesk.menu',
            'helpdesk.dashboard',
            'helpdesk.reports.export',
            'helpdesk.reports.view',
            'helpdesk.technicians.view',
            'helpdesk.tickets.assign',
            'helpdesk.tickets.comment',
            'helpdesk.tickets.confirm',
            'helpdesk.tickets.view-all',
        ]);

        $helpdeskUser->givePermissionTo([
            'helpdesk.menu',
            'helpdesk.dashboard',
            'helpdesk.technicians.view',
            'helpdesk.tickets.comment',
            'helpdesk.tickets.confirm',
            'helpdesk.tickets.create',
            'helpdesk.tickets.delete',
            'helpdesk.tickets.edit',
            'helpdesk.tickets.reopen',
            'helpdesk.tickets.view',
        ]);

        $helpdeskTechnician->givePermissionTo([
            'helpdesk.menu',
            'helpdesk.dashboard',
            'helpdesk.technicians.view',
            'helpdesk.tickets.approve',
            'helpdesk.tickets.comment',
            'helpdesk.tickets.resolve',
            'helpdesk.tickets.view',
        ]);

        $formItUser->givePermissionTo([
            'form-it.dashboard',
            'form-it.fixed-asset.create',
            'form-it.fixed-asset.view',
            'form-it.forms.create',
            'form-it.forms.view',
            'form-it.menu',
        ]);

        $formItApprover->givePermissionTo([
            'form-it.approval.process',
            'form-it.approval.view',
            'form-it.dashboard',
            'form-it.fixed-asset.approve',
            'form-it.fixed-asset.create',
            'form-it.fixed-asset.view',
            'form-it.forms.create',
            'form-it.forms.view',
            'form-it.menu',
        ]);

        $dokterAdmin->givePermissionTo([]);

        $admin->givePermissionTo([
            // Portal & Menu
            'portal.access',
            'ams.menu',
            'helpdesk.menu',
            'data-pegawai.menu',
            'form-it.menu',
            'sop-it.menu',
            // AMS
            'ams.dashboard',
            'ams.assets.view',
            'ams.assets.create',
            'ams.assets.edit',
            'ams.assets.delete',
            'ams.assets.import',
            'ams.transactions.view',
            'ams.transactions.create',
            'ams.transactions.delete',
            'ams.transactions.approve',
            'ams.transactions.export-pdf',
            'ams.employees.view',
            'ams.employees.manage',
            'ams.employees.import',
            'ams.master-data.view',
            'ams.master-data.manage',
            'ams.assignment.view',
            'ams.assignment.manage',
            'ams.monitoring.view',
            'ams.whatsapp-settings.manage',
            'ams.settings.reset-password',
            'ams.settings.approve',
            // Helpdesk
            'helpdesk.dashboard',
            'helpdesk.tickets.view',
            'helpdesk.tickets.view-all',
            'helpdesk.tickets.create',
            'helpdesk.tickets.edit',
            'helpdesk.tickets.delete',
            'helpdesk.tickets.assign',
            'helpdesk.tickets.confirm',
            'helpdesk.tickets.reopen',
            'helpdesk.tickets.comment',
            'helpdesk.ticket-categories.manage',
            'helpdesk.ticket-priorities.manage',
            'helpdesk.technicians.view',
            'helpdesk.reports.view',
            'helpdesk.reports.export',
            // IT Admin
            'it-admin.access',
            'it-admin.users.manage',
            'it-admin.roles.manage',
            'it-admin.permissions.manage',
            // Dokter
            'dokter.menu',
            'dokter.dashboard',
            'dokter.vendors.view',
            'dokter.vendors.create',
            'dokter.vendors.edit',
            'dokter.vendors.delete',
            'dokter.document-types.view',
            'dokter.document-types.create',
            'dokter.document-types.edit',
            'dokter.document-types.delete',
            'dokter.file-managements.view',
            'dokter.file-managements.download',
            'dokter.file-managements.validate',
            'dokter.log-file.view',
            'dokter.log-file.export',
            'dokter.auditor-access.manage',
            // EQTAX
            'eqtax.menu',
            'eqtax.dashboard',
            'eqtax.spt.coretax.view',
            'eqtax.spt.coretax.import',
            'eqtax.spt.coretax.update-field',
            'eqtax.gl.view',
            'eqtax.gl.import',
            'eqtax.gl.update-field',
            'eqtax.equalization.view',
            'eqtax.equalization.process',
            'eqtax.equalization.export',
            'eqtax.tb.view',
            'eqtax.tb.process',
            'eqtax.tb.save',
            // Form IT
            'form-it.dashboard',
            'form-it.forms.view',
            'form-it.forms.create',
            'form-it.approval.view',
            'form-it.approval.process',
            // Form IT - Fixed Asset
            'form-it.fixed-asset.view',
            'form-it.fixed-asset.create',
            'form-it.fixed-asset.approve',
            // E-RKAP
            ...$erkapAdminPermissions,
            ...$approvalPermissions,
        ]);

        $approver->givePermissionTo([
            'portal.access',
            'ams.menu',
            'data-pegawai.menu',
            'form-it.menu',
            'ams.dashboard',
            'ams.assets.view',
            'ams.transactions.view',
            'ams.transactions.approve',
            'ams.transactions.export-pdf',
            'ams.employees.view',
            'ams.master-data.view',
            'ams.assignment.view',
            'ams.monitoring.view',
            'ams.settings.reset-password',
            'ams.settings.approve',
            'helpdesk.dashboard',
            'helpdesk.tickets.view',
            'helpdesk.tickets.view-all',
            'helpdesk.technicians.view',
            'helpdesk.reports.view',
            // Form IT
            'form-it.dashboard',
            'form-it.forms.view',
            'form-it.approval.view',
            'form-it.approval.process',
            // Form IT - Fixed Asset
            'form-it.fixed-asset.view',
            'form-it.fixed-asset.approve',
        ]);

        $staff->givePermissionTo([
            'portal.access',
            'ams.menu',
            'helpdesk.menu',
            'form-it.menu',
            'ams.dashboard',
            'ams.assets.view',
            'ams.transactions.view',
            'ams.transactions.create',
            'ams.transactions.export-pdf',
            'ams.employees.view',
            'ams.master-data.view',
            'ams.assignment.view',
            'ams.monitoring.view',
            'ams.settings.reset-password',
            'helpdesk.dashboard',
            'helpdesk.tickets.view',
            'helpdesk.tickets.create',
            'helpdesk.tickets.edit',
            'helpdesk.tickets.delete',
            'helpdesk.tickets.confirm',
            'helpdesk.tickets.reopen',
            'helpdesk.tickets.comment',
            // Dokter
            'dokter.menu',
            'dokter.dashboard',
            'dokter.file-managements.view',
            'dokter.file-managements.download',
            'dokter.file-managements.validate',
            // Form IT
            'form-it.dashboard',
            'form-it.forms.view',
            'form-it.forms.create',
            // Form IT - Fixed Asset
            'form-it.fixed-asset.view',
            'form-it.fixed-asset.create',
        ]);

        $teknisi->givePermissionTo([
            'portal.access',
            'helpdesk.menu',
            'ams.settings.reset-password',
            'helpdesk.dashboard',
            'helpdesk.tickets.view',
            'helpdesk.tickets.comment',
            'helpdesk.tickets.resolve',
            'helpdesk.tickets.approve',
            'helpdesk.technicians.view',
        ]);
    }
}
