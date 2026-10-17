<?php

namespace Modules\Taxido\Http\Controllers\Front;

use Exception;
use Illuminate\Http\Request;
use Modules\Taxido\Models\Rider;
use Illuminate\Support\Facades\DB;
use Modules\Taxido\Enums\RoleEnum;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class RegisterController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|string|email|max:255|unique:users,email',
            'phone' => 'required|string|regex:/^[0-9]{6,15}$/|unique:users,phone',
            'country_code' => 'required|string|regex:/^\+?[1-9][0-9]{0,2}$/',
            'firebase_id_token' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
            'name' => 'required|string|max:255',
        ]);

        try {
            $phone = app(\App\Services\FirebasePhoneIdentity::class)->verify($validated['firebase_id_token']);
        } catch (\Kreait\Firebase\Exception\Auth\FailedToVerifyToken $e) {
            \Illuminate\Support\Facades\Log::warning('Firebase signup token rejected.', [
                'reason' => app(\App\Services\FirebasePhoneIdentity::class)->failureReason($e),
            ]);
            throw \Illuminate\Validation\ValidationException::withMessages([
                'firebase_id_token' => 'Invalid or expired Firebase token. Please verify your phone again.',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Firebase signup verification unavailable.', ['exception_class' => get_class($e)]);
            throw \Illuminate\Validation\ValidationException::withMessages([
                'firebase_id_token' => 'Phone verification is temporarily unavailable. Please try again.',
            ]);
        }
        if ($phone !== '+' . ltrim($validated['country_code'], '+') . $validated['phone']) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'phone' => 'The verified phone number does not match your registration.',
            ]);
        }

        try {
            return \Illuminate\Support\Facades\Cache::lock('cab:rider-registration', 15)->block(5,
                fn () => $this->createRider($request, $validated));
        } catch (\Illuminate\Contracts\Cache\LockTimeoutException $e) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'phone' => 'Registration is busy. Please try again.',
            ]);
        }
    }

    private function createRider(Request $request, array $validated)
    {
        // Recheck while holding the lock: the existing schema has no phone/email unique indexes.
        $request->validate(['email' => 'unique:users,email', 'phone' => 'unique:users,phone']);
        DB::beginTransaction();
        try {

            $rider = Rider::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'country_code' => $validated['country_code'],
                'password' => Hash::make($validated['password']),
                'referral_code' => $this->getReferralCode($validated['name']),
                'status' => true,
            ]);

            $rider->assignRole(RoleEnum::RIDER);
            DB::commit();

            Auth::login($rider);
            $request->session()->regenerate();
            return redirect()->route('front.cab.dashboard.index');
        } catch (Exception $e) {
            DB::rollback();
            \Illuminate\Support\Facades\Log::error('Rider registration failed.', ['exception_class' => get_class($e)]);
            return back()->withErrors(['error' => 'Registration failed. Please try again.'])
                ->withInput($request->except('password', 'password_confirmation', 'firebase_id_token'));
        }
    }

    private function getReferralCode($name)
    {
        return strtoupper(substr($name, 0, 3)) . rand(1000, 9999);
    }
}
