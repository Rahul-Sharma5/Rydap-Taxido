<?php

namespace App\Services;

use Illuminate\Validation\ValidationException;
use Kreait\Firebase\Contract\Auth;

class FirebasePhoneIdentity
{
    public function verifyToken(string $idToken): \Lcobucci\JWT\UnencryptedToken
    {
        $auth = app(Auth::class);
        try {
            return $auth->verifyIdToken($idToken);
        } catch (\Kreait\Firebase\Exception\Auth\FailedToVerifyToken $error) {
            if ($this->transportErrorCode($error) !== 60 || !$this->needsCaFallback()) {
                throw $error;
            }
            try {
                app(FirebaseCertificateCache::class)->warm();
            } catch (\Throwable $fetchError) {
                throw new \Kreait\Firebase\Exception\Auth\FailedToVerifyToken(
                    'Fetching Firebase public keys failed: ' . $fetchError->getMessage()
                );
            }
            // Retry the original Admin verifier. Signature/project/expiry checks are unchanged.
            return $auth->verifyIdToken($idToken);
        }
    }

    protected function needsCaFallback(): bool
    {
        return PHP_OS_FAMILY === 'Windows' && !ini_get('curl.cainfo') && !ini_get('openssl.cafile');
    }

    public function failureReason(\Throwable $error): string
    {
        // Inspect internally, but never emit the SDK message: it can contain the token.
        $message = strtolower($error->getMessage());
        if ($this->transportErrorCode($error) !== null) {
            return 'verification_transport_failed';
        }
        foreach (['connection to ', 'fetch public keys', 'fetching public keys', 'fetch public',
            'public keys from', 'no keys are available', 'ssl ca bundle not found', 'no system ca bundle'] as $pattern) {
            if (str_contains($message, $pattern)) return 'verification_keys_unavailable';
        }
        foreach ([
            'audience' => 'project_mismatch', 'issuers' => 'project_mismatch',
            'expired' => 'expired', 'signature' => 'signature_or_key_invalid',
            'curl error' => 'verification_transport_failed', 'fetch' => 'verification_keys_unavailable',
            'invalid' => 'malformed_or_invalid',
        ] as $pattern => $reason) {
            if (str_contains($message, $pattern)) {
                return $reason;
            }
        }
        return 'verification_failed';
    }

    public function transportErrorCode(\Throwable $error): ?int
    {
        return preg_match('/curl error ([0-9]{1,3})\b/i', $error->getMessage(), $matches)
            ? (int) $matches[1] : null;
    }

    public function verificationUnavailable(\Throwable $error): bool
    {
        return in_array($this->failureReason($error), [
            'verification_transport_failed', 'verification_keys_unavailable',
        ], true);
    }

    public function verify(string $idToken): string
    {
        $claims = $this->verifyToken($idToken)->claims();
        $phone = $claims->get('phone_number');
        $firebase = $claims->get('firebase', []);

        if (!is_string($phone) || !preg_match('/^\+[1-9][0-9]{7,14}$/D', $phone)
            || ($firebase['sign_in_provider'] ?? null) !== 'phone') {
            throw ValidationException::withMessages([
                'firebase_id_token' => 'A verified Firebase phone identity is required.',
            ]);
        }

        return $phone;
    }
}
