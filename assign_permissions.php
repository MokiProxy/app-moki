<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Required permissions for ERKAP access
$erkapPermissions = [
    'erkap.menu',
    'erkap.rkap.view', 'erkap.rkap.create', 'erkap.rkap.edit', 'erkap.rkap.delete', 'erkap.rkap.submit',
    'erkap.company-targets.view', 'erkap.company-targets.create', 'erkap.company-targets.edit', 'erkap.company-targets.delete',
    'erkap.department-targets.view', 'erkap.department-targets.create', 'erkap.department-targets.edit', 'erkap.department-targets.delete',
    'erkap.risk-identifications.view', 'erkap.risk-identifications.create', 'erkap.risk-identifications.edit', 'erkap.risk-identifications.delete', 'erkap.risk-identifications.submit',
    'erkap.risk-analysis.view', 'erkap.risk-analysis.create', 'erkap.risk-analysis.edit', 'erkap.risk-analysis.delete',
    'erkap.department-risk-strategies.view', 'erkap.department-risk-strategies.create', 'erkap.department-risk-strategies.edit', 'erkap.department-risk-strategies.delete',
    'erkap.work-programs.view', 'erkap.work-programs.create', 'erkap.work-programs.edit', 'erkap.work-programs.delete', 'erkap.work-programs.submit',
    'erkap.routine-costs.view', 'erkap.routine-costs.create', 'erkap.routine-costs.edit', 'erkap.routine-costs.delete', 'erkap.routine-costs.submit',
    'erkap.investment-plans.view', 'erkap.investment-plans.create', 'erkap.investment-plans.edit', 'erkap.investment-plans.delete', 'erkap.investment-plans.submit', 'erkap.investment-plans.download',
    'erkap.investment-gates.view', 'erkap.investment-gates.review',
    'erkap.budget-capex.view', 'erkap.budget-capex.create', 'erkap.budget-capex.edit',
    'erkap.revenue-plans.view', 'erkap.revenue-plans.create', 'erkap.revenue-plans.edit', 'erkap.revenue-plans.delete',
    'erkap.expense-plans.view', 'erkap.expense-plans.create', 'erkap.expense-plans.edit', 'erkap.expense-plans.delete',
    'erkap.profit-loss.view', 'erkap.profit-loss.create',
    'erkap.budget-realizations.view', 'erkap.budget-realizations.create', 'erkap.budget-realizations.delete',
    'erkap.program-realizations.view', 'erkap.program-realizations.create', 'erkap.program-realizations.delete',
    'erkap.risk-assessments-monthly.view', 'erkap.risk-assessments-monthly.create', 'erkap.risk-assessments-monthly.edit', 'erkap.risk-assessments-monthly.delete',
    'erkap.performance-scorecards.view', 'erkap.performance-scorecards.create', 'erkap.performance-scorecards.delete',
    'erkap.zbb-reviews.view', 'erkap.zbb-reviews.create', 'erkap.zbb-reviews.edit',
    'erkap.approvals.view',
    'erkap.audit-logs.view',
    'erkap.reports.view', 'erkap.reports.generate',
    'erkap.structure.view', 'erkap.structure.edit',
    'erkap.business-units.view', 'erkap.business-units.create', 'erkap.business-units.edit', 'erkap.business-units.delete',
    'erkap.locations.view', 'erkap.locations.create', 'erkap.locations.edit', 'erkap.locations.delete',
    'erkap.management-areas.view', 'erkap.management-areas.create', 'erkap.management-areas.edit', 'erkap.management-areas.delete',
    'erkap.activities.view', 'erkap.activities.create', 'erkap.activities.edit', 'erkap.activities.delete',
    'erkap.cost-centers.view', 'erkap.cost-centers.create', 'erkap.cost-centers.edit', 'erkap.cost-centers.delete',
    'erkap.chart-of-accounts.view', 'erkap.chart-of-accounts.create', 'erkap.chart-of-accounts.edit', 'erkap.chart-of-accounts.delete',
    'erkap.cost-element-categories.view', 'erkap.cost-element-categories.create', 'erkap.cost-element-categories.edit', 'erkap.cost-element-categories.delete',
    'erkap.cost-elements.view', 'erkap.cost-elements.create', 'erkap.cost-elements.edit', 'erkap.cost-elements.delete',
    'erkap.risk-appetites.view', 'erkap.risk-appetites.create', 'erkap.risk-appetites.edit', 'erkap.risk-appetites.delete',
    'erkap.risk-taxonomies.view', 'erkap.risk-taxonomies.create', 'erkap.risk-taxonomies.edit', 'erkap.risk-taxonomies.delete',
    'erkap.risk-types.view', 'erkap.risk-types.create', 'erkap.risk-types.edit', 'erkap.risk-types.delete',
    'erkap.rating-criterias.view', 'erkap.rating-criterias.create', 'erkap.rating-criterias.edit', 'erkap.rating-criterias.delete',
    'erkap.risk-scales.view', 'erkap.risk-scales.create', 'erkap.risk-scales.edit', 'erkap.risk-scales.delete',
    'erkap.risk-probabilities.view', 'erkap.risk-probabilities.create', 'erkap.risk-probabilities.edit', 'erkap.risk-probabilities.delete',
    'erkap.risk-impacts.view', 'erkap.risk-impacts.create', 'erkap.risk-impacts.edit', 'erkap.risk-impacts.delete',
    'erkap.risk-score-levels.view', 'erkap.risk-score-levels.create', 'erkap.risk-score-levels.edit', 'erkap.risk-score-levels.delete',
    'erkap.investation-types.view', 'erkap.investation-types.create', 'erkap.investation-types.edit', 'erkap.investation-types.delete',
    'erkap.investation-criterias.view', 'erkap.investation-criterias.create', 'erkap.investation-criterias.edit', 'erkap.investation-criterias.delete',
    'erkap.investattion-categories.view', 'erkap.investattion-categories.create', 'erkap.investattion-categories.edit', 'erkap.investattion-categories.delete',
    'erkap.risk-identification-reasons.view', 'erkap.risk-identification-reasons.create', 'erkap.risk-identification-reasons.edit', 'erkap.risk-identification-reasons.delete',
    'erkap.risk-identification-impacts.view', 'erkap.risk-identification-impacts.create', 'erkap.risk-identification-impacts.edit', 'erkap.risk-identification-impacts.delete',
];

$roles = ['erkap-admin', 'erkap-ppk', 'erkap-cost-owner', 'erkap-controller', 'erkap-risk-manager', 'erkap-manajemen-aset', 'erkap-direksi-keuangan', 'erkap-komisaris', 'erkap-direksi', 'erkap-auditor'];

foreach ($roles as $roleName) {
    $role = Spatie\Permission\Models\Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
    foreach ($erkapPermissions as $permName) {
        $perm = Spatie\Permission\Models\Permission::firstOrCreate(['name' => $permName, 'guard_name' => 'web']);
        $role->givePermissionTo($perm);
    }
    echo "Assigned " . count($erkapPermissions) . " permissions to role: {$roleName}\n";
}

echo "\nDone!\n";
