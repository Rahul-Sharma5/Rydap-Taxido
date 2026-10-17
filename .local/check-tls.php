<?php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$factory = Illuminate\Support\Facades\Http::getFacadeRoot();
$property = new ReflectionProperty($factory, 'globalOptions');
$options = $property->getValue($factory);
$bundle = $options['verify'] ?? ini_get('curl.cainfo');
echo 'CA bundle readable: '.(is_readable($bundle) ? 'yes' : 'no').PHP_EOL;
echo 'Trusted certificates: '.substr_count(file_get_contents($bundle), 'BEGIN CERTIFICATE').PHP_EOL;
echo 'TLS verification disabled: '.(($options['verify'] ?? true) === false ? 'yes' : 'no').PHP_EOL;
