<?php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$project = config('firebase.default');
$credentials = config('firebase.projects.'.$project.'.credentials');
$readable = is_string($credentials) && is_readable($credentials);
echo 'Configured Firebase credentials readable: '.($readable ? 'yes' : 'no').PHP_EOL;
if ($readable) {
    $account = json_decode(file_get_contents($credentials), true);
    echo 'Service-account project matches frontend: '.(($account['project_id'] ?? null) === 'rydap-com' ? 'yes' : 'no').PHP_EOL;
}
