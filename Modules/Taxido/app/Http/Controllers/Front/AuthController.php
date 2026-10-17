<?php

namespace Modules\Taxido\Http\Controllers\Front;

use Exception;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Modules\Taxido\Models\Rider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Mail;
use Modules\Taxido\Emails\VerifyEmail;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function passwordLogin(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);
        $riders = Rider::where('email', $validated['email'])->limit(2)->get();
        $rider = $riders->count() === 1 ? $riders->first() : null;
        if (!$rider || !\Illuminate\Support\Facades\Hash::check($validated['password'], $rider->password)) {
            return response()->json(['success' => false, 'message' => 'Invalid email or password.'], 401);
        }
        if (!$rider->status) {
            return response()->json(['success' => false, 'message' => 'Account is disabled.'], 403);
        }
        Auth::login($rider);
        $request->session()->regenerate();
        return response()->json(['success' => true, 'redirect' => route('front.cab.dashboard.index')]);
    }

    public function login()
    {
        if (Auth::check()) {
            return redirect()->route('front.cab.dashboard.index');
        }

        return view('taxido::front.auth.login');
    }

    public function register()
    {
        if (Auth::check()) {
            return redirect()->route('front.cab.dashboard.index');
        }
        return view('taxido::front.auth.register');
    }

    public function loginWithCredential(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email_or_phone' => 'required|string',
                'country_code' => 'nullable|required_if:email_or_phone,phone|string',
            ]);

            if ($validator->fails()) {
                return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
            }

            $emailOrPhone = $request->email_or_phone;
            $isEmail = filter_var($emailOrPhone, FILTER_VALIDATE_EMAIL);
            $request->session()->put('email_or_phone', $emailOrPhone);
            if (!$isEmail) {
                if(strlen((string)$emailOrPhone) < 6 || strlen((string)$emailOrPhone) > 15) {
                    return response()->json(['success' => false, 'message' => "Phone number must be 6 to 15 digits."], 422);
                }

                $request->session()->put('country_code', $request->country_code);
            }

            if ($isEmail) {
                return $this->sendEmailToken($emailOrPhone);
            } else {
                if (!$request->country_code) {
                    return response()->json(['success' => false, 'message' => 'Country code is required for phone login.'], 422);
                }
                return $this->sendPhoneToken($request->country_code, $emailOrPhone);
            }
        } catch (Exception $e) {
            \Illuminate\Support\Facades\Log::error('Login OTP delivery failed.', ['exception_class' => get_class($e)]);
            return response()->json(['success' => false, 'message' => 'Unable to send OTP. Please try again later.'], 500);
        }
    }

    private function sendEmailToken($email)
    {
        $token = $this->generateToken(email: $email);
        if(!isDemoMode()) {
            Mail::to($email)->queue(new VerifyEmail($token));
        }
        return response()->json(['message' => 'OTP sent successfully to your email!', 'success' => true], 200);
    }

    private function sendPhoneToken($country_code, $phone)
    {
        $token = $this->generateToken($country_code, $phone);
        if(!isDemoMode()) {
            sendSMS('+' . $country_code . $phone, $token);
        }
        return response()->json(['message' => 'OTP sent successfully to your phone!', 'success' => true], 200);
    }

    public function generateToken($country_code = null, $phone = null, $email = null)
    {
        $token = random_int(100000, 999999);
        if (isDemoMode()) {
            $token = 123456;
        }
        DB::table('auth_tokens')->insert([
            'token' => $token,
            'phone' => '+' . $country_code . $phone,
            'email' => $email,
            'created_at' => Carbon::now()
        ]);

        return $token;
    }

    public function verifyOtp()
    {
        if (!session('email_or_phone')) {
            return redirect()->route('front.cab.login.index')->with('error', 'Please request an OTP first.');
        }

        return view('taxido::front.auth.verify_otp');
    }

    public function verifyOtpStore(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'otp' => 'required|array|size:6',
                'otp.*' => 'required|digits:1',
                'email_or_phone' => 'required',
                'country_code' => 'nullable',
            ]);

            if ($validator->fails()) {
                return back()->withErrors($validator)->withInput();
            }

            $otp = implode('', $request->otp);
            $input = $request->email_or_phone;
            $isEmail = filter_var($input, FILTER_VALIDATE_EMAIL);
            $phone = $isEmail ? null : '+' . $request->country_code . $input;

            $verify_otp = DB::table('auth_tokens')
                ->where('token', $otp)
                ->where(function ($query) use ($input, $isEmail, $phone) {
                    if ($isEmail) {
                        $query->where('email', $input);
                    } else {
                        $query->where('phone', $phone);
                    }
                })
                ->where('created_at', '>', Carbon::now()->subHours(1))
                ->first();

            if (!$verify_otp) {
                return redirect()->back()->with('error', __('taxido::front.otp_invalid'));
            }

            $rider = Rider::whereNull('deleted_at')
                ->where($isEmail ? 'email' : 'phone', $isEmail ? $input : $input)
                ->first();

            if (!$rider) {
                return redirect()->route('front.cab.login.index')->with('error', __('taxido::front.user_not_found'));
            }

            if (!$rider->status) {
                return back()->withErrors(['otp' => 'Account is disabled.']);
            }

            $consumed = DB::table('auth_tokens')->where('token', $otp)
                ->where($isEmail ? 'email' : 'phone', $isEmail ? $input : $phone)
                ->where('created_at', $verify_otp->created_at)->delete();
            if (!$consumed) {
                return back()->withErrors(['otp' => 'OTP already used. Please request a new one.']);
            }
            if ($isEmail && !$rider->email_verified_at) {
                $rider->forceFill(['email_verified_at' => now()])->save();
            }

            Auth::login($rider);
            $request->session()->regenerate();
            return redirect()->route('front.cab.dashboard.index');
        } catch (Exception $e) {
            return back()->withErrors(['otp' => 'An error occurred. Please try again.']);
        }
    }

    public function verifyFirebaseOtp(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                'id_token' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'A Firebase ID token must be provided in id_token.',
                    'errors' => $validator->errors(),
                ], 400);
            }

            try {
                $verifiedToken = app(\App\Services\FirebasePhoneIdentity::class)
                    ->verifyToken($request->input('id_token'));
            } catch (\Kreait\Firebase\Exception\Auth\FailedToVerifyToken $e) {
                $identity = app(\App\Services\FirebasePhoneIdentity::class);
                \Illuminate\Support\Facades\Log::warning('Firebase phone token rejected.', [
                    'exception_class' => get_class($e),
                    'reason' => $identity->failureReason($e),
                    'transport_error_code' => $identity->transportErrorCode($e),
                ]);
                if ($identity->verificationUnavailable($e)) {
                    return response()->json([
                        'success' => false,
                        'code' => 'firebase_verification_unavailable',
                        'message' => 'The server cannot reach Firebase to verify your login. Please try again shortly.',
                    ], 503);
                }
                return response()->json([
                    'success' => false,
                    'code' => 'firebase_token_invalid',
                    'message' => 'Invalid or expired Firebase token. Please request a new OTP.',
                ], 401);
            }

            $phone = $verifiedToken->claims()->get('phone_number');
            $firebase = $verifiedToken->claims()->get('firebase', []);
            if (!is_string($phone) || !preg_match('/^\+[1-9][0-9]{7,14}$/D', $phone)
                || ($firebase['sign_in_provider'] ?? null) !== 'phone') {
                return response()->json([
                    'success' => false,
                    'code' => 'firebase_phone_identity_required',
                    'message' => 'A verified Firebase phone identity is required.',
                ], 401);
            }

            // Signup stores the national number and country code separately.
            // Match both against the signed E.164 claim, never client identity fields.
            $digits = substr($phone, 1);
            // Include soft-deleted riders so a verified phone can recover the
            // existing account instead of being sent to signup as a duplicate.
            $riders = Rider::withTrashed()->where(function ($query) use ($digits) {
                for ($length = 1; $length <= 3; $length++) {
                    $code = substr($digits, 0, $length);
                    $number = substr($digits, $length);
                    $query->orWhere(function ($candidate) use ($code, $number) {
                        $candidate->whereIn('country_code', [$code, '+' . $code])
                            ->where('phone', $number);
                    });
                }
            })->limit(2)->get();

            if ($riders->count() > 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unable to identify a unique account. Please contact support.',
                ], 409);
            }
            $rider = $riders->first();
            if (!$rider) {
                return response()->json([
                    'success' => true,
                    'message' => 'User not found. Please register first.',
                    'redirect' => route('front.cab.register.index')
                ]);
            } else {
                if (!$rider->status) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Account is disabled.'
                    ], 403);
                }
            }

            if ($rider->trashed() && !$rider->restore()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unable to restore your account. Please contact support.'
                ], 500);
            }

            Auth::login($rider);
            $request->session()->regenerate();
            return response()->json([
                'success' => true,
                'message' => 'Login successful!',
                'redirect' => route('front.cab.dashboard.index')
            ]);

        } catch (Exception $e) {

            // Exception messages can contain credentials or raw token details.
            \Illuminate\Support\Facades\Log::error('Firebase phone login failed.', [
                'exception_class' => get_class($e),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Unable to verify login. Please try again later.'
            ], 500);
        }
    }
}
