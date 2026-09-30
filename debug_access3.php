<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Check all middleware groups
echo "=== Middleware Groups ===\n";
$router = $app['router'];
foreach ($router->getMiddlewareGroups() as $name => $middleware) {
    echo "$name: " . implode(', ', $middleware) . "\n";
}

echo "\n=== Route Middleware for erkap.index ===\n";
$route = Route::getRoutes()->getByName('erkap.index');
if ($route) {
    echo "URI: " . $route->uri() . "\n";
    echo "Action: " . $route->getActionName() . "\n";
    echo "Middleware: " . implode(', ', $route->gatherMiddleware()) . "\n";
}

echo "\n=== Check if user has direct access ===\n";
$user = App\Models\User::where('email', 'erkap.ppk@test.com')->first();
echo "User: {$user->name}\n";
echo "Roles: " . $user->getRoleNames()->implode(', ') . "\n";
echo "Permissions count: " . $user->getAllPermissions()->count() . "\n";
echo "Has erkap.menu: " . ($user->hasPermissionTo('erkap.menu') ? 'YES' : 'NO') . "\n";

// Check if there's a portal.access permission needed
echo "Has portal.access: " . ($user->hasPermissionTo('portal.access') ? 'YES' : 'NO') . "\n";

// Check all permissions that start with 'portal'
$portalPerms = $user->getAllPermissions()->filter(function($p) { return str_starts_with($p->name, 'portal'); });
echo "Portal permissions: " . $portalPerms->pluck('name')->implode(', ') . "\n";
