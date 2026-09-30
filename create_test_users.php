<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$roles = ['erkap-admin', 'erkap-ppk', 'erkap-cost-owner', 'erkap-controller', 'erkap-risk-manager', 'erkap-manajemen-aset', 'erkap-direksi-keuangan', 'erkap-komisaris', 'erkap-direksi', 'erkap-auditor'];

foreach ($roles as $roleName) {
    $role = Spatie\Permission\Models\Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
    $user = App\Models\User::firstOrCreate(
        ['email' => str_replace('-', '.', $roleName) . '@test.com'],
        ['name' => ucfirst(str_replace('erkap-', '', $roleName)) . ' Test', 'password' => bcrypt('password123')]
    );
    $user->syncRoles([$roleName]);
    echo 'Created/Updated: ' . $user->email . ' with role ' . $roleName . PHP_EOL;
}

echo PHP_EOL . 'Summary:' . PHP_EOL;
foreach ($roles as $roleName) {
    $count = App\Models\User::role($roleName)->count();
    echo "  {$roleName}: {$count} users" . PHP_EOL;
}
