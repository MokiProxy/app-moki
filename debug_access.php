<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = App\Models\User::where('email', 'erkap.ppk@test.com')->first();
echo "User: {$user->name} ({$user->email})\n";
echo "Employee ID: {$user->employee_id}\n";
echo "Roles: " . $user->getRoleNames()->implode(', ') . "\n";
echo "Has erkap.menu: " . ($user->hasPermissionTo('erkap.menu') ? 'YES' : 'NO') . "\n";
echo "Has erkap.rkap.view: " . ($user->hasPermissionTo('erkap.rkap.view') ? 'YES' : 'NO') . "\n";
echo "Has erkap.rkap.create: " . ($user->hasPermissionTo('erkap.rkap.create') ? 'YES' : 'NO') . "\n";

// Check if there are any middleware on the erkap routes
echo "\nChecking route middleware...\n";
$route = Route::getRoutes()->getByName('erkap.index');
if ($route) {
    echo "Route erkap.index found\n";
    echo "Middleware: " . implode(', ', $route->middleware()) . "\n";
    echo "Action: " . $route->getActionName() . "\n";
} else {
    echo "Route erkap.index NOT found\n";
}
