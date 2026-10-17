# RYDAP authentication implementation report

## Confirmed local 401 certificate failure (17 October follow-up)

The request reported at 11:43:42 UTC matches the 17:13:42 local log entry `verification_transport_failed`. A subsequent harmless diagnostic request against the actual PHP 8.4 server on port 8000 confirmed **cURL error 60**, meaning server-side TLS certificate trust failed while downloading Google's Firebase verification certificates. The request was mislabeled as an invalid/expired token because Kreait wraps certificate-fetch failures in `FailedToVerifyToken`. This establishes the cause of this local failure; it does not establish the cause of the earlier production incident.

The PHP 8.4 runtime has neither `curl.cainfo` nor `openssl.cafile` configured. The existing Laravel HTTP facade CA setting does not affect Kreait's separate Guzzle client for ID-token certificates. The targeted fix adds `app/Services/FirebaseCertificateCache.php`. Only when Windows has no configured CA files and the original verifier fails with cURL 60, the phone identity service securely downloads Google's public certificates using Composer's maintained CA bundle into the same expiring SDK verifier cache. It then retries the **original Admin verifier**; signature, audience, issuer, expiry and provider checks are retained. No TLS verification is disabled and no vendor code is modified.

On the running PHP 8.4 server, a deliberately unsigned diagnostic token previously failed certificate fetching (503/cURL 60); after the fix it reached key/signature validation and was rejected there (401, `signature_or_key_invalid`, no transport error). This confirms that public certificate retrieval now works on that server. No real OTP or valid user ID token was used for this check, so successful real-user Login still requires one manual retry.

Unresolved network/TLS failures now return HTTP 503 / `firebase_verification_unavailable`, without logging the token or suggesting that a correct OTP is invalid. Logs include only a fixed failure category and numeric cURL error code. Signup uses the same verified-token service and gets an appropriate service-unavailable validation message.

The local URL and launcher now use the actual port **8000**. `start-local.cmd` and `php-local.cmd` explicitly supply both CA paths and the existing PHP 8.3.31 runtime; the runtime fallback also handles the user's plain PHP 8.4 server. Only the local `.env` APP_URL was adjusted; credentials and production environment files were not changed. The optional command `.\php-local.cmd artisan firebase:check-auth --check-network` tests direct certificate connectivity without using credentials, OTPs or ID tokens. The tool execution environment's outbound proxy returns cURL 7, so a direct CLI network success from that environment is not claimed; the actual running server's successful certificate-fetch/signature-rejection result is separate evidence.

Additional follow-up files: `app/Services/FirebaseCertificateCache.php`, `app/Services/FirebasePhoneIdentity.php`, `app/Console/Commands/CheckFirebaseAuth.php`, cab `AuthController.php` and `RegisterController.php`, the two local command wrappers, local `.env` APP_URL, and the authentication regression tests. Final verification: **34 PHPUnit tests, 126 assertions, plus 7 frontend tests, all passing**. Tests cover cURL 60/7/28, certificate HTTP failures, Signup fail-closed behavior, retry through the original Admin verifier, and actual SDK verification using its securely populated certificate cache with local test keys.

## Evidence and diagnosis

The inspected checkout already contained a secure ID-token-based phone Login implementation and 12 phone Login tests. Those existing changes were preserved. The deployed production revision and the body of its reported 401 response were not provided, so the precise cause of that production failure cannot be confirmed from this checkout.

In this checkout, `/cab/verify-firebase-otp` resolves to `Modules\Taxido\Http\Controllers\Front\AuthController::verifyFirebaseOtp`. It has no authentication-guard middleware. Its controller returns 401 when Kreait rejects the Firebase ID token, or when the signed identity lacks a valid E.164 phone claim or the phone sign-in provider. Missing `id_token` returns 400; disabled accounts return 403; ambiguous phone accounts return 409. An unknown phone retains the existing registration redirect without creating a user.

Added response codes distinguish `firebase_token_invalid` from `firebase_phone_identity_required`. Token failures log only an exception class and a fixed reason category. SDK exception messages, OTPs, and ID tokens are not logged by this implementation. A new `firebase:check-auth` command checks local project alignment without displaying credentials.

The readable Admin service account and actual browser configuration both use `rydap-com`. Admin initialization and private-key parsing succeeded. The local `.env` contains older `taxido-local-test` values for `services.firebase`; those values do not determine the current cab browser configuration or the service account's project. They were not overwritten. Browser settings are now centralized in `config/firebase-web.php`, retaining the existing public configuration as defaults with optional `FIREBASE_WEB_*` overrides.

Two additional defects were confirmed and repaired:

- Signup only enforced Firebase verification in JavaScript; the server ignored `firebase_id_token` entirely. It now verifies the token and matches its signed phone before creating an account.
- `phpblaze/bladelib` replaces the `web` middleware group without Laravel CSRF validation. An actual HTTP POST without CSRF initially reached the controller (400). Explicit Laravel CSRF middleware on cab authentication and logout now produces 419 for that request. The dependency and unrelated routes were not changed.

## Existing architecture and resulting behavior

- Local PHP 8.3.31; locked Laravel 12.62.0, Kreait Firebase PHP 7.24.1, Laravel Firebase 6.2.0.
- Cab uses the Laravel `web` session guard and `Rider`, a `users` model scoped to the Spatie `rider` role and non-deleted accounts. Existing profile fields, role assignment and dashboard redirects remain.
- Phone Signup and Login now use Firebase 10.14.1 modular Auth imports from the existing Firebase CDN, through `public/front/js/firebase-phone-auth.js`. No browser `firebase` npm package was declared; Node `firebase-admin` and `firebase-functions` are server packages. No dependency or lockfile changes were necessary. Vite is used for the existing styles/scripts; the shared auth module is served directly and the page handlers remain inline Blade, so no asset rebuild was required.
- Phone Login confirms OTP in Firebase, obtains an ID token, posts `id_token` with CSRF, verifies the signature/project/time in Kreait, matches country code plus national phone from the signed claim, checks rider status, logs in, and regenerates the Laravel session. Client phone fields cannot select the authenticated identity.
- Signup retains name, email, national phone, country code, password and password confirmation. It now requires `firebase_id_token` server-side; mismatched, missing or rejected tokens cannot create users. It preserves Laravel password hashing and the rider role. Duplicate email/phone validation includes soft-deleted accounts. Cab registrations acquire a cache lock and repeat uniqueness checks before insertion.
- Email/password Signup remains part of that existing profile registration, including required phone verification. Email-only registration without a phone was not introduced because it would change the existing required-profile policy. Password Login was added alongside the existing email OTP method; it checks existing Laravel hashes and rejects disabled, non-rider and ambiguous email accounts. No Firebase password accounts or unsafe identity links are created.
- Existing application policy does not require verified email before access (`Auth::routes` disables verification and `User` does not implement `MustVerifyEmail`). Successful email OTP now records `email_verified_at`, consumes the OTP and rotates the session. Mandatory email verification was not imposed on existing users.
- Forgot/reset password uses Laravel, matching the actual password store. A rider-scoped broker and notification produce cab reset links. Valid reset tokens update the Laravel hash, rotate the remember token, consume the reset token and return to Login without automatically authenticating. Firebase password reset would not reset these Laravel hashes, so it was not used.
- Logout destroys the session, rotates CSRF and preserves the existing locale/theme preferences. Browser Firebase Auth uses in-memory persistence. Loading guards, phone-change invalidation, resend handling and backend error display were tightened. The alternate OTP page now sends Firebase tokens to the same verified endpoint.

## Files changed

| File | Purpose |
| --- | --- |
| `Modules/Taxido/app/Http/Controllers/Front/AuthController.php` | Native password Login, safe token diagnostics, single-use email OTP, email verification timestamp, status/session checks |
| `Modules/Taxido/app/Http/Controllers/Front/RegisterController.php` | Server-side phone proof, identity matching, duplicate validation/registration lock, safe errors |
| `Modules/Taxido/app/Http/Controllers/Front/AccountController.php` | Session and CSRF invalidation on logout |
| `Modules/Taxido/app/Http/Controllers/Front/PasswordController.php` | Rider password reset request and completion |
| `Modules/Taxido/app/Notifications/RiderResetPassword.php` | Cab-specific reset URL |
| `Modules/Taxido/routes/web.php` | Auth endpoints, rate limits, explicit CSRF, removal of duplicate Firebase route |
| `Modules/Taxido/resources/views/front/auth/login.blade.php` | Password controls and phone request/retry handling within existing page |
| `Modules/Taxido/resources/views/front/auth/register.blade.php` | Shared Firebase configuration, server error display and stale verification reset |
| `Modules/Taxido/resources/views/front/auth/verify_otp.blade.php` | Shared configuration and correct Firebase verification endpoint |
| `Modules/Taxido/resources/views/front/auth/password.blade.php` | Reset form using existing layout and classes |
| `Modules/Taxido/resources/views/front/account/sidebar.blade.php` | Explicit Firebase sign-out before Laravel logout, with a timeout/failure fallback that still destroys the Laravel session |
| `public/front/js/firebase-phone-auth.js` | Shared modular Firebase initialization, device language, verifier lifecycle, deduplicated SMS requests, sign-out and actionable errors |
| `app/Services/FirebasePhoneIdentity.php` | Signed phone identity validation and safe failure classification |
| `app/Console/Commands/CheckFirebaseAuth.php` | Credential-safe configuration diagnostics |
| `config/auth.php` | Rider password broker/provider, preserving the default guard/provider |
| `config/firebase-web.php` | One public browser configuration with environment overrides |
| `tests/Feature/FirebasePhoneLoginTest.php` | Expanded isolated authentication regression coverage |
| `tests/Unit/FirebaseTokenValidationTest.php` | Real SDK signature/project/expiry validation using ephemeral local test keys |
| `tests/Frontend/FirebasePhoneAuth.test.cjs` | Mocked modular SDK lifecycle, expiry/reset, duplicate requests, stale responses, confirmation and sign-out tests |
| `docs/authentication-integration.md` | This report and setup/verification instructions |
| `.local/check-auth-assets.cjs` | Task-local inline JavaScript syntax checker (Blade JSON replaced with placeholders for parsing) |

The original implementation did not change `.env`; the certificate follow-up changed only its local APP_URL to port 8000, as described above. No service-account file, schema, dependency, lockfile, stylesheet or unrelated business controller was changed. This directory has no Git metadata; a repository-wide Git diff/status was unavailable. Copies of pre-edit files and task-only diagnostics are under `.local/auth-task-backup` and `.local`; comparisons were made against those copies. Pre-existing changes were retained.

## Windows setup and checks

Dependencies and the existing local database were already available. Run in PowerShell:

```powershell
Set-Location D:\solve-project\app
$env:PHPRC = (Resolve-Path .local\php.ini).Path
.\php-local.cmd artisan firebase:check-auth
.\php-local.cmd vendor/phpunit/phpunit/phpunit
node .local\check-auth-assets.cjs
node --experimental-vm-modules tests/Frontend/FirebasePhoneAuth.test.cjs
.\start-local.cmd
```

The actual configured URL is `http://127.0.0.1:8000`. `start-local.cmd` uses the existing PHP 8.3.31 installation, local INI and Laravel server router. `PHPRC` is needed for PHPUnit's isolated child processes to inherit the SQLite extension configuration. No Composer/npm installation, migrations, cache clearing or asset rebuild is needed for this checkout. There is no cached Laravel configuration/routes file in the inspected checkout. Mail currently uses SMTP and a synchronous queue; real mail delivery remains unverified.

Optional public browser overrides are `FIREBASE_WEB_API_KEY`, `FIREBASE_WEB_AUTH_DOMAIN`, `FIREBASE_WEB_PROJECT_ID`, `FIREBASE_WEB_STORAGE_BUCKET`, `FIREBASE_WEB_MESSAGING_SENDER_ID`, `FIREBASE_WEB_APP_ID` and `FIREBASE_WEB_MEASUREMENT_ID`. Admin credentials remain in the existing protected service-account location. Do not put that JSON in public assets.

## Firebase / Google Cloud checks still requiring Console access

No Console settings were changed or claimed to be verified remotely.

1. In Firebase Authentication authorized domains, add `www.rydap.com` if it is still absent and users access that hostname. Retain `rydap.com` and the existing Firebase domains.
2. The existing restriction list supplied in the request does not cover the actual local port. If the browser key is used at this local URL, allow `http://127.0.0.1:8000/*` on that key. Its SHA-256 fingerprint prefix is available from `firebase:check-auth`; the actual key remains in the browser config, not this report.
3. Verify the matching browser key allows Identity Toolkit API (`identitytoolkit.googleapis.com`) and Token Service API (`securetoken.googleapis.com`). Preserve APIs used by other features; do not remove referrer restrictions or enable every API. [Firebase API key documentation](https://firebase.google.com/docs/projects/api-keys).
4. Verify Phone provider and the intended India SMS policy remain enabled. Use configured Firebase fictional test numbers for manual testing. Google's current Phone Auth documentation restricts localhost as a hosted phone-auth domain; adding referrer entries alone does not prove real SMS will work on loopback. Validate real SMS/reCAPTCHA on an authorized HTTPS hostname when needed. [Firebase Phone Auth documentation](https://firebase.google.com/docs/auth/web/phone-auth).

## Verification and remaining limits

Automated PHPUnit results: **30 tests, 101 assertions, all passing** (exit code 0). Tests cover successful phone Login, plus-prefixed country codes, missing/malformed/expired token rejection, missing phone/wrong provider, client phone mismatch, unknown phones, disabled/deleted/non-rider/ambiguous accounts, repeated Login without account creation, valid Signup with mocked Firebase identity, duplicate email/phone Signup, missing/rejected/mismatched Signup token, legacy password Login, wrong password/status/role rejection, reset notification URL, successful reset/token consumption, invalid reset token, email OTP success/replay/expiry, logout rotation, and explicit CSRF route attachment.

The token-verifier unit test uses the actual installed Kreait validation code and locally generated RSA keys, verifying valid signature acceptance and rejection of wrong audience, wrong issuer, expiry and tampering. It does not contact Firebase. Feature tests mock Firebase verification and fake outgoing reset notifications, using an isolated in-memory SQLite database. Existing example tests are included in the full run. No real SMS or account provisioning was performed.

The Firebase-docs follow-up adds **7 passing frontend tests** using mocked modular Firebase exports. They verify singleton initialization and browser language, single pre-render and widget ID tracking, expiry reset, duplicate SMS suppression, failed-request retry, stale challenge rejection, Firebase credential confirmation, explicit sign-out and hostname/OTP messages. All four affected page scripts (including the account sidebar) parse successfully. Local Login/Signup pages and the directly served shared module return HTTP 200, and Login HTML no longer includes compat scripts. The command-line environment could not fetch the Firebase CDN, so live browser module loading, reCAPTCHA and SMS still need manual verification. Replacing SDK syntax does not itself correct Console hostname restrictions.

Actual local HTTP checks: Login/Register/Forgot Password pages return 200; missing Firebase token with valid CSRF returns 400; a malformed placeholder ID token returns 401 with `firebase_token_invalid`; no CSRF returns 419 after the targeted middleware fix. PHP syntax checks and inline auth JavaScript parsing pass. These checks do not establish a real browser OTP success or real SMTP delivery.

Postman CLI checks were blocked before execution by an unreadable organization-managed Private Cloud manifest. Orbit discovery was unavailable over the configured network. Existing source, installed SDK code, PHPUnit and actual local HTTP requests supplied the verification instead; no Postman simulation result is claimed.

Remaining manual verification: one Firebase fictional-number Signup/Login on each deployed hostname; incorrect/expired OTP and network retry behavior in a browser; email OTP and reset mail delivery; production session cookies and redirects. Capture the response `code` and matching safe log reason if production still returns 401. Do not share the ID token or OTP.

The existing users schema has no database unique indexes on email or phone. Cab's cache lock serializes registrations using the same lock store; multiple application servers require a shared lock-capable cache. Other existing account-creation paths do not acquire this lock. A database-wide uniqueness guarantee would require auditing existing duplicate data and a separately reviewed migration; none was run in this task.
