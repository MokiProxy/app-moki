<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Check if there's a redirect middleware or something else
echo "=== Checking for redirect middleware ===\n";

// Check all middleware
$router = $app['router'];
echo "All middleware:\n";
foreach ($router->getMiddleware() as $name => $class) {
    echo "  $name => $class\n";
}

// Check if there's a custom middleware that might be redirecting
echo "\n=== Checking App\Http\Middleware ===\n";
$middlewareDir = __DIR__ . '/app/Http/Middleware';
foreach (glob($middlewareDir . '/*.php') as $file) {
    $name = basename($file, '.php');
    echo "  $name\n";
}

// Check Kernel.php for middleware groups
echo "\n=== Checking Kernel.php ===\n";
$kernelFile = __DIR__ . '/app/Http/Kernel.php';
if (file_exists($kernelFile)) {
    $content = file_get_contents($kernelFile);
    // Find middleware groups
    if (preg_match('/protected \$middlewareGroups = \[(.*?)\];/s', $content, $matches)) {
        echo "Middleware groups found in Kernel.php\n";
    }
    // Find route middleware
    if (preg_match('/protected \$routeMiddleware = \[(.*?)\];/s', $content, $matches)) {
        echo "Route middleware found in Kernel.php\n";
    }
}
