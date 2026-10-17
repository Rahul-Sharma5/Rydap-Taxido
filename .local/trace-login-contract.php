<?php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$controller = new Modules\Taxido\Http\Controllers\Front\AuthController();
foreach (['frontend payload' => ['id_token' => 'diagnostic-placeholder'], 'missing token' => []] as $name => $payload) {
    $request = Illuminate\Http\Request::create('/cab/verify-firebase-otp', 'POST', $payload);
    $response = $controller->verifyFirebaseOtp($request);
    echo $name.': HTTP '.$response->getStatusCode().' '.$response->getContent().PHP_EOL;
}
$route = $app['router']->getRoutes()->match(Illuminate\Http\Request::create('/cab/verify-firebase-otp', 'POST'));
echo 'Route action: '.$route->getActionName().PHP_EOL;
echo 'Route middleware: '.implode(', ', $route->gatherMiddleware()).PHP_EOL;
echo 'Firebase contract available: '.(interface_exists(Kreait\Firebase\Contract\Auth::class) ? 'yes' : 'no').PHP_EOL;
