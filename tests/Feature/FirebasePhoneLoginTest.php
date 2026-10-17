<?php

namespace Tests\Feature;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Kreait\Firebase\Contract\Auth as FirebaseAuth;
use Kreait\Firebase\Exception\Auth\FailedToVerifyToken;
use Lcobucci\JWT\UnencryptedToken;
use Lcobucci\JWT\Token\DataSet;
use Mockery;
use Modules\Taxido\Http\Controllers\Front\AuthController;
use Tests\TestCase;

#[\PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses]
#[\PHPUnit\Framework\Attributes\PreserveGlobalState(false)]
class FirebasePhoneLoginTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.connections.otp_testing' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
        ]]);
        DB::setDefaultConnection('otp_testing');
        $schema = Schema::connection('otp_testing');
        $schema->create('users', function (Blueprint $table) {
            $table->id();
            $table->string('phone');
            $table->string('country_code');
            $table->integer('status')->default(1);
            $table->string('name')->nullable();
            $table->string('email')->nullable()->unique();
            $table->string('password')->nullable();
            $table->string('referral_code')->nullable();
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->rememberToken();
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        $schema->create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
        });
        $schema->create('model_has_roles', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
        });
        $schema->create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
        $schema->create('auth_tokens', function (Blueprint $table) {
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
        DB::table('roles')->insert(['id' => 1, 'name' => 'rider', 'guard_name' => 'web']);
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function rider(int $id = 1, string $code = '91', int $status = 1, bool $role = true, bool $deleted = false): void
    {
        DB::table('users')->insert([
            'id' => $id, 'phone' => '9876543210', 'country_code' => $code,
            'status' => $status, 'deleted_at' => $deleted ? now() : null,
        ]);
        if ($role) {
            DB::table('model_has_roles')->insert([
                'role_id' => 1, 'model_type' => 'App\\Models\\User', 'model_id' => $id,
            ]);
        }
    }

    private function identity(?string $phone = '+919876543210', string $provider = 'phone', int $times = 1): void
    {
        $token = Mockery::mock(UnencryptedToken::class);
        $token->shouldReceive('claims')->andReturn(new DataSet([
            'phone_number' => $phone, 'firebase' => ['sign_in_provider' => $provider],
        ], ''));
        $firebase = Mockery::mock(FirebaseAuth::class);
        $firebase->shouldReceive('verifyIdToken')->with('test-id-token')->times($times)->andReturn($token);
        $this->app->instance(FirebaseAuth::class, $firebase);
    }

    private function verify(array $payload = ['id_token' => 'test-id-token'])
    {
        $request = Request::create('/cab/verify-firebase-otp', 'POST', $payload);
        $request->setLaravelSession(app('session')->driver());
        return (new AuthController())->verifyFirebaseOtp($request);
    }

    public function test_registered_phone_logs_in_and_regenerates_session(): void
    {
        $this->rider();
        $this->identity();
        Auth::shouldReceive('login')->once()->with(Mockery::on(fn ($user) => $user->id === 1));
        $session = app('session')->driver();
        $session->start();
        $old = $session->getId();
        $response = $this->verify();
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(route('front.cab.dashboard.index'), $response->getData(true)['redirect']);
        $this->assertNotSame($old, $session->getId());
        $this->assertSame(1, DB::table('users')->count());
    }

    public function test_country_code_with_plus_is_supported(): void
    {
        $this->rider(code: '+91');
        $this->identity();
        Auth::shouldReceive('login')->once();
        $this->assertSame(200, $this->verify()->getStatusCode());
    }

    public function test_missing_token_and_old_uid_only_payload_are_rejected(): void
    {
        Auth::shouldReceive('login')->never();
        foreach ([[], ['firebase_uid' => 'untrusted', 'email_or_phone' => '9876543210'], ['id_token' => []]] as $payload) {
            $this->assertSame(400, $this->verify($payload)->getStatusCode());
        }
    }

    public function test_invalid_or_expired_token_is_rejected(): void
    {
        $firebase = Mockery::mock(FirebaseAuth::class);
        $firebase->shouldReceive('verifyIdToken')->with('test-id-token')->once()
            ->andThrow(new FailedToVerifyToken('Invalid or expired test token'));
        $this->app->instance(FirebaseAuth::class, $firebase);
        Auth::shouldReceive('login')->never();
        $this->assertSame(401, $this->verify()->getStatusCode());
    }

    public function test_certificate_transport_failures_are_service_errors_without_authenticating(): void
    {
        $firebase = Mockery::mock(FirebaseAuth::class);
        $failures = [
            'Unable to verify local-test-token: cURL error 60: SSL certificate problem',
            'Unable to verify local-test-token: cURL error 7: could not connect',
            'Unable to verify local-test-token: cURL error 28: timed out',
        ];
        $firebase->shouldReceive('verifyIdToken')->with('test-id-token')->times(3)
            ->andReturnUsing(function () use (&$failures) { throw new FailedToVerifyToken(array_shift($failures)); });
        $this->app->instance(FirebaseAuth::class, $firebase);
        Auth::shouldReceive('login')->never();
        foreach (range(1, 3) as $attempt) {
            $response = $this->verify();
            $this->assertSame(503, $response->getStatusCode());
            $this->assertSame('firebase_verification_unavailable', $response->getData(true)['code']);
            $this->assertStringNotContainsString('local-test-token', $response->getContent());
        }
        $this->assertSame(0, DB::table('users')->count());
    }

    public function test_certificate_fetch_failures_are_not_reported_as_invalid_otps(): void
    {
        $firebase = Mockery::mock(FirebaseAuth::class);
        $firebase->shouldReceive('verifyIdToken')->once()
            ->andThrow(new FailedToVerifyToken('Unexpected status code 503 when trying to fetch public keys from Google'));
        $this->app->instance(FirebaseAuth::class, $firebase);
        Auth::shouldReceive('login')->never();
        $this->assertSame(503, $this->verify()->getStatusCode());
    }

    public function test_missing_windows_ca_retries_the_original_admin_verifier_after_secure_cache_warmup(): void
    {
        $identity = Mockery::mock(\App\Services\FirebasePhoneIdentity::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $identity->shouldReceive('needsCaFallback')->once()->andReturn(true);
        $cache = Mockery::mock(\App\Services\FirebaseCertificateCache::class);
        $cache->shouldReceive('warm')->once();
        $this->app->instance(\App\Services\FirebaseCertificateCache::class, $cache);
        $token = Mockery::mock(UnencryptedToken::class);
        $firebase = Mockery::mock(FirebaseAuth::class);
        $firebase->shouldReceive('verifyIdToken')->with('test-id-token')->once()->ordered()
            ->andThrow(new FailedToVerifyToken('cURL error 60: SSL certificate problem'));
        $firebase->shouldReceive('verifyIdToken')->with('test-id-token')->once()->ordered()->andReturn($token);
        $this->app->instance(FirebaseAuth::class, $firebase);
        $this->assertSame($token, $identity->verifyToken('test-id-token'));
    }

    public function test_signup_transport_failure_keeps_registration_closed_with_service_message(): void
    {
        $firebase = Mockery::mock(FirebaseAuth::class);
        $firebase->shouldReceive('verifyIdToken')->once()
            ->andThrow(new FailedToVerifyToken('cURL error 7: could not connect'));
        $this->app->instance(FirebaseAuth::class, $firebase);
        Auth::shouldReceive('login')->never();
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $this->expectExceptionMessage('The server cannot reach Firebase');
        $this->registration();
    }

    public function test_unregistered_phone_redirects_to_registration_without_creating_user(): void
    {
        $this->identity();
        Auth::shouldReceive('login')->never();
        $response = $this->verify();
        $this->assertSame(route('front.cab.register.index'), $response->getData(true)['redirect']);
        $this->assertSame(0, DB::table('users')->count());
    }

    public function test_client_phone_cannot_override_verified_identity(): void
    {
        $this->rider();
        $this->identity('+449876543210');
        Auth::shouldReceive('login')->never();
        $response = $this->verify(['id_token' => 'test-id-token', 'email_or_phone' => '9876543210', 'country_code' => '91']);
        $this->assertSame(route('front.cab.register.index'), $response->getData(true)['redirect']);
    }

    public function test_disabled_account_is_rejected(): void
    {
        $this->rider(status: 0);
        $this->identity();
        Auth::shouldReceive('login')->never();
        $this->assertSame(403, $this->verify()->getStatusCode());
    }

    public function test_non_phone_firebase_identity_is_rejected(): void
    {
        $this->identity(provider: 'password');
        Auth::shouldReceive('login')->never();
        $this->assertSame(401, $this->verify()->getStatusCode());
    }

    public function test_missing_phone_claim_is_rejected(): void
    {
        $this->identity(null);
        Auth::shouldReceive('login')->never();
        $this->assertSame(401, $this->verify()->getStatusCode());
    }

    public function test_deleted_and_non_rider_accounts_cannot_log_in(): void
    {
        $this->rider(deleted: true);
        $this->rider(id: 2, role: false);
        $this->identity();
        Auth::shouldReceive('login')->never();
        $this->assertSame(route('front.cab.register.index'), $this->verify()->getData(true)['redirect']);
    }

    public function test_ambiguous_accounts_are_rejected(): void
    {
        $this->rider();
        $this->rider(id: 2);
        $this->identity();
        Auth::shouldReceive('login')->never();
        $this->assertSame(409, $this->verify()->getStatusCode());
    }

    public function test_repeated_verification_uses_same_account_without_creating_users(): void
    {
        $this->rider();
        $this->identity(times: 2);
        Auth::shouldReceive('login')->twice()->with(Mockery::on(fn ($user) => $user->id === 1));
        $this->assertSame(200, $this->verify()->getStatusCode());
        $this->assertSame(200, $this->verify()->getStatusCode());
        $this->assertSame(1, DB::table('users')->count());
    }

    private function registration(array $overrides = [])
    {
        $request = Request::create('/cab/register', 'POST', array_merge([
            'name' => 'Test Rider', 'email' => 'rider@example.test', 'phone' => '9876543210',
            'country_code' => '91', 'password' => 'Password123!', 'password_confirmation' => 'Password123!',
            'firebase_id_token' => 'test-id-token',
        ], $overrides));
        $request->setLaravelSession(app('session')->driver());
        return \Modules\Taxido\Models\Rider::withoutEvents(fn () =>
            (new \Modules\Taxido\Http\Controllers\Front\RegisterController())->store($request));
    }

    public function test_signup_verifies_phone_and_preserves_email_password_and_rider_role(): void
    {
        $this->identity();
        Auth::shouldReceive('login')->once();
        $response = $this->registration();
        $this->assertSame(route('front.cab.dashboard.index'), $response->getTargetUrl());
        $user = DB::table('users')->first();
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('Password123!', $user->password));
        $this->assertSame('rider@example.test', $user->email);
        $this->assertSame(1, DB::table('model_has_roles')->where('model_id', $user->id)->count());
    }

    public function test_signup_requires_token_before_account_creation(): void
    {
        Auth::shouldReceive('login')->never();
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $this->registration(['firebase_id_token' => '']);
    }

    public function test_signup_rejects_phone_mismatch(): void
    {
        $this->identity('+449876543210');
        Auth::shouldReceive('login')->never();
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $this->registration();
    }

    public function test_duplicate_phone_signup_is_rejected(): void
    {
        $this->rider();
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $this->registration();
    }

    public function test_duplicate_email_signup_is_rejected(): void
    {
        $this->rider();
        DB::table('users')->update(['email' => 'rider@example.test']);
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $this->registration(['phone' => '9876543211']);
    }

    public function test_existing_laravel_password_logs_in_without_firebase(): void
    {
        $this->rider();
        DB::table('users')->update(['email' => 'rider@example.test', 'password' => bcrypt('LegacyPassword!')]);
        Auth::shouldReceive('login')->once();
        $request = Request::create('/cab/login-with-password', 'POST', ['email' => 'rider@example.test', 'password' => 'LegacyPassword!']);
        $request->setLaravelSession(app('session')->driver());
        $this->assertSame(200, (new AuthController())->passwordLogin($request)->getStatusCode());
    }

    public function test_wrong_password_disabled_and_non_rider_accounts_are_rejected(): void
    {
        $this->rider();
        DB::table('users')->update(['email' => 'rider@example.test', 'password' => bcrypt('LegacyPassword!')]);
        Auth::shouldReceive('login')->never();
        $controller = new AuthController();
        $request = fn ($password) => Request::create('/cab/login-with-password', 'POST', ['email' => 'rider@example.test', 'password' => $password]);
        $this->assertSame(401, $controller->passwordLogin($request('wrong'))->getStatusCode());
        DB::table('users')->update(['status' => 0]);
        $this->assertSame(403, $controller->passwordLogin($request('LegacyPassword!'))->getStatusCode());
        DB::table('model_has_roles')->delete();
        $this->assertSame(401, $controller->passwordLogin($request('LegacyPassword!'))->getStatusCode());
    }

    public function test_signup_rejects_invalid_firebase_token(): void
    {
        $firebase = Mockery::mock(FirebaseAuth::class);
        $firebase->shouldReceive('verifyIdToken')->once()->andThrow(new FailedToVerifyToken('Rejected test token'));
        $this->app->instance(FirebaseAuth::class, $firebase);
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $this->registration();
    }

    public function test_password_reset_updates_hash_consumes_token_and_does_not_log_in(): void
    {
        $this->rider();
        DB::table('users')->update(['email' => 'rider@example.test', 'password' => bcrypt('OldPassword!')]);
        $rider = \Modules\Taxido\Models\Rider::first();
        $broker = \Illuminate\Support\Facades\Password::broker('riders');
        $token = $broker->createToken($rider);
        Auth::shouldReceive('login')->never();
        $request = Request::create('/cab/reset-password', 'POST', [
            'email' => $rider->email, 'token' => $token,
            'password' => 'NewPassword!', 'password_confirmation' => 'NewPassword!',
        ]);
        $response = \Modules\Taxido\Models\Rider::withoutEvents(fn () =>
            (new \Modules\Taxido\Http\Controllers\Front\PasswordController())->reset($request));
        $this->assertSame(route('front.cab.login.index'), $response->getTargetUrl());
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('NewPassword!', DB::table('users')->first()->password));
        $this->assertFalse($broker->tokenExists($rider, $token));
    }

    public function test_password_reset_email_uses_cab_route_without_sending_real_mail(): void
    {
        \Illuminate\Support\Facades\Notification::fake();
        $this->rider();
        DB::table('users')->update(['email' => 'rider@example.test']);
        $request = Request::create('/cab/forgot-password', 'POST', ['email' => 'rider@example.test']);
        (new \Modules\Taxido\Http\Controllers\Front\PasswordController())->send($request);
        \Illuminate\Support\Facades\Notification::assertSentTo(
            \Modules\Taxido\Models\Rider::first(),
            \Modules\Taxido\Notifications\RiderResetPassword::class,
            function ($notification, $channels, $user) {
                $this->assertStringContainsString('/cab/reset-password/', $notification->toMail($user)->actionUrl);
                return true;
            }
        );
    }

    public function test_logout_invalidates_session_and_rotates_csrf_token(): void
    {
        $session = app('session')->driver();
        $session->start();
        $session->put(['locale' => 'en', 'private_data' => 'remove']);
        $session->regenerateToken();
        $oldId = $session->getId();
        $oldToken = $session->token();
        Auth::shouldReceive('logout')->once();
        $repository = Mockery::mock(\Modules\Taxido\Repositories\Front\AccountRepository::class);
        (new \Modules\Taxido\Http\Controllers\Front\AccountController($repository))->logout();
        $this->assertNotSame($oldId, $session->getId());
        $this->assertNotSame($oldToken, $session->token());
        $this->assertNull($session->get('private_data'));
        $this->assertSame('en', $session->get('locale'));
    }

    public function test_email_otp_verifies_email_is_single_use_and_regenerates_session(): void
    {
        $this->rider();
        DB::table('users')->update(['email' => 'rider@example.test']);
        DB::table('auth_tokens')->insert(['email' => 'rider@example.test', 'token' => '123456', 'created_at' => now()]);
        $request = Request::create('/cab/verify-otp', 'POST', ['email_or_phone' => 'rider@example.test', 'otp' => str_split('123456')]);
        $session = app('session')->driver();
        $session->start();
        $oldId = $session->getId();
        $request->setLaravelSession($session);
        Auth::shouldReceive('login')->once();
        $response = \Modules\Taxido\Models\Rider::withoutEvents(fn () => (new AuthController())->verifyOtpStore($request));
        $this->assertSame(route('front.cab.dashboard.index'), $response->getTargetUrl());
        $this->assertNotNull(DB::table('users')->first()->email_verified_at);
        $this->assertSame(0, DB::table('auth_tokens')->count());
        $this->assertNotSame($oldId, $session->getId());
        (new AuthController())->verifyOtpStore($request);
    }

    public function test_invalid_and_expired_email_otps_cannot_authenticate(): void
    {
        $this->rider();
        DB::table('users')->update(['email' => 'rider@example.test']);
        DB::table('auth_tokens')->insert(['email' => 'rider@example.test', 'token' => '123456', 'created_at' => now()->subHours(2)]);
        Auth::shouldReceive('login')->never();
        foreach (['123456', '654321'] as $otp) {
            $request = Request::create('/cab/verify-otp', 'POST', ['email_or_phone' => 'rider@example.test', 'otp' => str_split($otp)]);
            $response = (new AuthController())->verifyOtpStore($request);
            $this->assertSame(302, $response->getStatusCode());
        }
    }

    public function test_invalid_reset_token_does_not_change_password(): void
    {
        $this->rider();
        $oldHash = bcrypt('OldPassword!');
        DB::table('users')->update(['email' => 'rider@example.test', 'password' => $oldHash]);
        \Illuminate\Support\Facades\Password::broker('riders');
        Auth::shouldReceive('login')->never();
        $request = Request::create('/cab/reset-password', 'POST', [
            'email' => 'rider@example.test', 'token' => 'invalid-test-token',
            'password' => 'NewPassword!', 'password_confirmation' => 'NewPassword!',
        ]);
        (new \Modules\Taxido\Http\Controllers\Front\PasswordController())->reset($request);
        $this->assertSame($oldHash, DB::table('users')->first()->password);
    }

    public function test_csrf_middleware_is_explicit_on_auth_routes_despite_package_web_override(): void
    {
        foreach (['front.cab.verify_firebase_otp', 'front.cab.register.store', 'front.cab.password.login',
            'front.cab.login_with_credential', 'front.cab.verify_otp.store', 'front.cab.password.email',
            'front.cab.password.update', 'front.cab.logout'] as $name) {
            $route = app('router')->getRoutes()->getByName($name);
            $this->assertContains(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class, $route->gatherMiddleware());
        }
    }
}
