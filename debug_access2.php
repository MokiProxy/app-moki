<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Simulate the request to see what's happening
$request = Illuminate\Http\Request::create('/erkap', 'GET');
$request->setUserResolver(function () {
    return App\Models\User::where('email', 'erkap.ppk@test.com')->first();
});

// Check if user can access
$user = $request->user();
echo "User: {$user->name}\n";
echo "Has erkap.menu: " . ($user->hasPermissionTo('erkap.menu') ? 'YES' : 'NO') . "\n";

// Check the route middleware
$route = Route::getRoutes()->getByName('erkap.index');
echo "Route middleware: " . implode(', ', $route->gatherMiddleware()) . "\n";

// Try to dispatch the request
try {
    $response = $kernel->handle($request);
    echo "Response status: " . $response->getStatusCode() . "\n";
    if ($response->isRedirect()) {
        echo "Redirect to: " . $response->getTargetUrl() . "\n";
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}
