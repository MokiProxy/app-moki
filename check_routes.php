<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo 'Login routes:' . PHP_EOL;
$routes = collect(Route::getRoutes())->filter(function($route) { return str_contains($route->uri(), 'login'); });
foreach ($routes as $route) { echo '  ' . $route->methods()[0] . ' ' . $route->uri() . ' -> ' . $route->getName() . PHP_EOL; }

echo PHP_EOL . 'Erkap routes (first 20):' . PHP_EOL;
$erkapRoutes = collect(Route::getRoutes())->filter(function($route) { return str_contains($route->uri(), 'erkap'); })->take(20);
foreach ($erkapRoutes as $route) { echo '  ' . $route->methods()[0] . ' ' . $route->uri() . ' -> ' . $route->getName() . PHP_EOL; }
