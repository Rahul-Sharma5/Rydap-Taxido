<?php

namespace Tests\Unit;

use Beste\Clock\FrozenClock;
use Kreait\Firebase\JWT\Action\VerifyIdToken\WithLcobucciJWT;
use Kreait\Firebase\JWT\Contract\Keys;
use Kreait\Firebase\JWT\Error\IdTokenVerificationFailed;
use Kreait\Firebase\JWT\IdTokenVerifier;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use PHPUnit\Framework\TestCase;

class FirebaseTokenValidationTest extends TestCase
{
    public function test_real_sdk_checks_signature_project_and_expiry_with_local_test_keys(): void
    {
        $configFile = tempnam(sys_get_temp_dir(), 'firebase-test-');
        file_put_contents($configFile, "[req]\ndistinguished_name=req_distinguished_name\n[req_distinguished_name]\n");
        try {
            $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA, 'config' => $configFile]);
            $this->assertNotFalse($key);
            openssl_pkey_export($key, $private, null, ['config' => $configFile]);
            $public = openssl_pkey_get_details($key)['key'];
        } finally {
            unlink($configFile);
        }
        $keys = $this->createStub(Keys::class);
        $keys->method('all')->willReturn(['local-test-key' => $public]);
        $now = new \DateTimeImmutable('2026-10-17T00:00:00Z');
        $verifier = new IdTokenVerifier(new WithLcobucciJWT('rydap-com', $keys, FrozenClock::at($now)));
        $configuration = Configuration::forAsymmetricSigner(new Sha256(), InMemory::plainText($private), InMemory::plainText($public));
        $token = function (string $audience = 'rydap-com', string $issuer = 'rydap-com', bool $expired = false) use ($configuration, $now) {
            return $configuration->builder()->withHeader('kid', 'local-test-key')
                ->issuedBy('https://securetoken.google.com/' . $issuer)->permittedFor($audience)
                ->relatedTo('local-test-user')->issuedAt($now->modify('-2 hours'))
                ->expiresAt($now->modify($expired ? '-1 hour' : '+1 hour'))
                ->withClaim('auth_time', $now->modify('-2 hours')->getTimestamp())
                ->withClaim('phone_number', '+919876543210')->withClaim('firebase', ['sign_in_provider' => 'phone'])
                ->getToken($configuration->signer(), $configuration->signingKey())->toString();
        };
        $valid = $token();
        $verifier->verifyIdToken($valid);
        $this->addToAssertionCount(1);

        // The Windows CA fallback fills the exact SDK cache; a new unmodified
        // Admin verifier must consume it and still enforce the RSA signature.
        $http = new \GuzzleHttp\Client(['handler' => \GuzzleHttp\HandlerStack::create(
            new \GuzzleHttp\Handler\MockHandler(array_fill(0, 3,
                new \GuzzleHttp\Psr7\Response(200, ['Cache-Control' => 'max-age=3600'], json_encode(['local-test-key' => $public]))
            ))
        )]);
        $cache = new \Symfony\Component\Cache\Adapter\ArrayAdapter();
        \App\Services\FirebaseCertificateCache::populate($http, $cache);
        $freshNow = new \DateTimeImmutable();
        $freshToken = $configuration->builder()->withHeader('kid', 'local-test-key')
            ->issuedBy('https://securetoken.google.com/rydap-com')->permittedFor('rydap-com')
            ->relatedTo('local-test-user')->issuedAt($freshNow->modify('-1 minute'))
            ->expiresAt($freshNow->modify('+1 hour'))
            ->withClaim('auth_time', $freshNow->modify('-1 minute')->getTimestamp())
            ->getToken($configuration->signer(), $configuration->signingKey())->toString();
        IdTokenVerifier::createWithProjectIdAndCache('rydap-com', $cache)->verifyIdToken($freshToken);
        $this->addToAssertionCount(1);
        $parts = explode('.', $valid);
        $parts[2][0] = $parts[2][0] === 'A' ? 'B' : 'A';
        foreach ([$token('wrong-project'), $token(issuer: 'wrong-project'), $token(expired: true), implode('.', $parts)] as $invalid) {
            try {
                $verifier->verifyIdToken($invalid);
                $this->fail('Invalid token was accepted.');
            } catch (IdTokenVerificationFailed $e) {
                $this->addToAssertionCount(1);
            }
        }
    }
}
