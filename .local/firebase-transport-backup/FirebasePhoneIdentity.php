<?php

namespace App\Services;

use Illuminate\Validation\ValidationException;
use Kreait\Firebase\Contract\Auth;

class FirebasePhoneIdentity
{
    public function failureReason(\Throwable $error): string
    {
        // Inspect internally, but never emit the SDK message: it can contain the token.
        $message = strtolower($error->getMessage());
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

    public function verify(string $idToken): string
    {
        $claims = app(Auth::class)->verifyIdToken($idToken)->claims();
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
