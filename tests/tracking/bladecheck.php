<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$blade = app('blade.compiler');
foreach ([
 'resources/views/frontend/partials/event-layer-head.blade.php',
] as $f) {
    try { $blade->compileString(file_get_contents($f)); echo "OK   $f\n"; }
    catch (Throwable $e) { echo "FAIL $f :: ".$e->getMessage()."\n"; }
}
