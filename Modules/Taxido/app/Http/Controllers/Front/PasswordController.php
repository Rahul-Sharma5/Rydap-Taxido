<?php

namespace Modules\Taxido\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Modules\Taxido\Models\Rider;

class PasswordController extends Controller
{
    public function form(Request $request, ?string $token = null)
    {
        return view('taxido::front.auth.password', ['token' => $token, 'email' => $request->query('email')]);
    }

    public function send(Request $request)
    {
        $request->validate(['email' => 'required|email']);
        $rider = Rider::where('email', $request->email)->where('status', 1)->first();
        if ($rider) {
            Password::broker('riders')->sendResetLink(['email' => $rider->email, 'status' => 1], function ($user, $token) {
                $user->notify(new \Modules\Taxido\Notifications\RiderResetPassword($token));
            });
        }
        return back()->with('status', 'If an active account exists, a password reset link has been sent.');
    }

    public function reset(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email', 'token' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);
        $rider = Rider::where('email', $data['email'])->where('status', 1)->first();
        if (!$rider || !Password::broker('riders')->tokenExists($rider, $data['token'])) {
            return back()->withErrors(['email' => 'This password reset link is invalid or expired.']);
        }
        $rider->forceFill(['password' => Hash::make($data['password']), 'remember_token' => Str::random(60)])->save();
        Password::broker('riders')->deleteToken($rider);
        event(new PasswordReset($rider));
        return redirect()->route('front.cab.login.index')->with('status', 'Password reset. Please sign in.');
    }
}
