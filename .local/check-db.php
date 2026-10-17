<?php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
try { echo 'Database: '.Illuminate\Support\Facades\DB::connection()->getDatabaseName().PHP_EOL; echo 'Tables: '.count(Illuminate\Support\Facades\DB::select('SHOW TABLES')).PHP_EOL; } catch (Throwable $e) { echo $e->getMessage().PHP_EOL; exit(1); }
