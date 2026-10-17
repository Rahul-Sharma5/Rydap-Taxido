<?php

namespace App\Services;

use Beste\Clock\SystemClock;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Illuminate\Support\Facades\Cache;
use Kreait\Firebase\JWT\Action\FetchGooglePublicKeys;
use Kreait\Firebase\JWT\Action\FetchGooglePublicKeys\WithGuzzle;
use Kreait\Firebase\JWT\Action\FetchGooglePublicKeys\WithPsr6Cache;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\Adapter\Psr16Adapter;

class FirebaseCertificateCache
{
    public function warm(): void
    {
        $project = config('firebase.default');
        $store = config("firebase.projects.{$project}.cache_store");
        $bundle = base_path('vendor/composer/ca-bundle/res/cacert.pem');
        if (!$store || !is_readable($bundle)) {
            throw new \RuntimeException('Firebase public key fetching requires a readable CA bundle and verifier cache.');
        }
        // This is the same cache and SDK key-fetch handler used by Kreait's verifier.
        // Only the missing Windows TLS trust configuration is supplied explicitly.
        self::populate(new Client([
            'verify' => $bundle, 'timeout' => 15, 'connect_timeout' => 5,
        ]), new Psr16Adapter(Cache::store($store)));
    }

    public static function populate(ClientInterface $client, CacheItemPoolInterface $cache): void
    {
        $clock = SystemClock::create();
        $handler = new WithPsr6Cache(new WithGuzzle($client, $clock), $cache, $clock);
        $handler->handle(FetchGooglePublicKeys::fromGoogle());
    }
}
