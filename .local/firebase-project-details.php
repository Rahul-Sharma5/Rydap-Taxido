<?php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$project = config('firebase.default');
$file = config('firebase.projects.'.$project.'.credentials');
$account = is_string($file) && is_readable($file) ? json_decode(file_get_contents($file), true) : [];
echo 'Backend project ID: '.($account['project_id'] ?? '(missing)').PHP_EOL;
echo 'Frontend project ID: rydap-com'.PHP_EOL;
echo 'Credential config path: '.$file.PHP_EOL;
