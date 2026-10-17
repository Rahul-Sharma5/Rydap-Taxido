<?php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$account = json_decode(file_get_contents(config('services.firebase.credentials')), true, 512, JSON_THROW_ON_ERROR);
echo 'Private key parse: '.(openssl_pkey_get_private($account['private_key']) !== false ? 'OK' : 'FAILED').PHP_EOL;
try {
    app(Kreait\Firebase\Contract\Auth::class);
    echo 'Firebase Admin Auth initialization: OK'.PHP_EOL;
} catch (Throwable $e) {
    echo 'Firebase Admin Auth initialization failed: '.get_class($e).PHP_EOL;
    exit(1);
}
