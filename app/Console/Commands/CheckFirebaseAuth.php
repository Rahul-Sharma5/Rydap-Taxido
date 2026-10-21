<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CheckFirebaseAuth extends Command
{
    protected $signature = 'firebase:check-auth {--check-network : Fetch Firebase public certificates with TLS verification enabled}';
    protected $description = 'Check browser/Admin Firebase project alignment without exposing credentials';

    public function handle(): int
    {
        try {
            $this->line('PHP runtime: ' . PHP_VERSION);
            $this->line('TLS CA configured: ' . ((ini_get('curl.cainfo') || ini_get('openssl.cafile')) ? 'yes' : 'no'));
            $project = config('firebase.default');
            $credentials = config("firebase.projects.{$project}.credentials");
            if (is_array($credentials)) {
                $account = $credentials;
            } elseif (is_string($credentials) && is_readable($credentials)) {
                $account = json_decode(file_get_contents($credentials), true, 512, JSON_THROW_ON_ERROR);
            } else {
                $this->error('Configured Firebase service-account credentials are not available.');
                return self::FAILURE;
            }
            $web = config('firebase-web');
            $this->line('Browser project: ' . $web['projectId']);
            $this->line('Admin project: ' . ($account['project_id'] ?? '(missing)'));
            $this->line('Browser API key fingerprint (SHA-256 prefix): ' . substr(hash('sha256', (string) $web['apiKey']), 0, 12));
            $this->line('Application URL: ' . config('app.url'));
            if (!$web['apiKey'] || !$web['appId'] || ($account['project_id'] ?? null) !== $web['projectId']) {
                $this->error('Browser configuration is incomplete or does not match the Admin project.');
                return self::FAILURE;
            }
            app(\Kreait\Firebase\Contract\Auth::class);
            if ($this->option('check-network')) {
                // Match the verifier's own Guzzle transport, not Laravel's HTTP facade.
                $clock = \Beste\Clock\SystemClock::create();
                $trust = ini_get('curl.cainfo') ?: ini_get('openssl.cafile') ?: true;
                if ($trust === true && PHP_OS_FAMILY === 'Windows') {
                    $trust = base_path('vendor/composer/ca-bundle/res/cacert.pem');
                    $this->line('Certificate check uses the project CA bundle with TLS verification enabled.');
                }
                $keys = new \Kreait\Firebase\JWT\GooglePublicKeys(
                    new \Kreait\Firebase\JWT\Action\FetchGooglePublicKeys\WithGuzzle(
                        new \GuzzleHttp\Client(['timeout' => 10, 'connect_timeout' => 5, 'verify' => $trust]),
                        $clock
                    ), $clock
                );
                $count = count($keys->all());
                if ($count === 0) {
                    $this->error('Firebase returned no public verification certificates.');
                    return self::FAILURE;
                }
                $this->info('Firebase public certificates fetched securely: ' . $count);
            }
            $this->info('Firebase Admin initializes and project IDs match. Live tokens and Console restrictions are not tested.');
            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Firebase configuration check failed (' . get_class($e) . ').');
            $identity = app(\App\Services\FirebasePhoneIdentity::class);
            $this->line('Failure category: ' . $identity->failureReason($e));
            if (($code = $identity->transportErrorCode($e)) !== null) {
                $this->line('cURL error code: ' . $code);
            }
            return self::FAILURE;
        }
    }
}
