<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$roles = ['erkap-admin', 'erkap-ppk', 'erkap-cost-owner', 'erkap-controller', 'erkap-risk-manager', 'erkap-manajemen-aset', 'erkap-direksi-keuangan', 'erkap-komisaris', 'erkap-direksi', 'erkap-auditor'];

$employees = App\Models\Employee::limit(10)->get();
echo 'Available employees (first 10):' . PHP_EOL;
foreach ($employees as $emp) {
    echo '  ID: ' . $emp->id . ' | NIP: ' . $emp->employee_id . ' | Name: ' . $emp->name . ' | Division: ' . ($emp->division_id ?? 'NULL') . PHP_EOL;
}

echo PHP_EOL . 'Updating test users with employee_id...' . PHP_EOL;
foreach ($roles as $i => $roleName) {
    $user = App\Models\User::where('email', str_replace('-', '.', $roleName) . '@test.com')->first();
    if ($user && isset($employees[$i])) {
        $user->employee_id = $employees[$i]->employee_id;
        $user->save();
        echo '  Updated ' . $user->email . ' with employee_id: ' . $user->employee_id . PHP_EOL;
    }
}
