@extends('front.layouts.master')
@section('title', 'Reset password')
@section('content')
<section class="authentication-section section-b-space">
    <div class="container"><div class="auth-form-box"><div class="auth-right-box">
        <h3>{{ $token ? 'Choose a new password' : 'Forgot password' }}</h3>
        @if(session('status'))<p class="text-success" role="status">{{ session('status') }}</p>@endif
        @foreach($errors->all() as $error)<p class="text-danger" role="alert">{{ $error }}</p>@endforeach
        <form method="POST" action="{{ route($token ? 'front.cab.password.update' : 'front.cab.password.email') }}">
            @csrf
            @if($token)<input type="hidden" name="token" value="{{ $token }}">@endif
            <div class="form-box"><input class="form-control" type="email" name="email" placeholder="Email" value="{{ old('email', $email) }}" autocomplete="email" required></div>
            @if($token)
                <div class="form-box"><input class="form-control" type="password" name="password" placeholder="New password" minlength="8" autocomplete="new-password" required></div>
                <div class="form-box"><input class="form-control" type="password" name="password_confirmation" placeholder="Confirm password" minlength="8" autocomplete="new-password" required></div>
            @endif
            <button class="btn gradient-bg-color otp-btn" type="submit">{{ $token ? 'Reset password' : 'Send reset link' }}</button>
        </form>
        <a href="{{ route('front.cab.login.index') }}">Back to login</a>
    </div></div></div>
</section>
@endsection
