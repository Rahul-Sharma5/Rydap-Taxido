
@extends('front.layouts.master')
@section('title', __('taxido::front.register'))

@section('content')
<section class="authentication-section section-b-space">
    <div class="container">
        <div class="auth-form-box">
            <div class="row align-items-center">
                <div class="col-xl-7 col-lg-6 d-lg-block d-none">
                    <img src="{{ asset('images/authentication-img.png') }}" class="img-fluid auth-image">
                </div>

                <div class="col-xl-5 col-lg-6">
                    <div class="auth-right-box">
                        <h3>{{ __('taxido::front.create_account') }}</h3>
                        <h6>{{ __('taxido::front.sign_up_minutes') }}</h6>

                        <form method="POST" action="{{ route('front.cab.register.store') }}" id="registerForm">
                            @csrf

                            <input type="hidden" name="firebase_id_token" id="firebase_id_token">
                            @foreach($errors->all() as $error)<p class="text-danger" role="alert">{{ $error }}</p>@endforeach

                            <div class="form-box form-icon">
                                <div class="position-relative">
                                    <i class="ri-user-line"></i>
                                    <input type="text" class="form-control" name="name"
                                        placeholder="{{ __('taxido::front.full_name') }}"
                                        value="{{ old('name') }}" required>
                                    @error('name')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="form-box form-icon">
                                <div class="position-relative">
                                    <i class="ri-mail-line"></i>
                                    <input type="email" class="form-control" name="email"
                                        placeholder="{{ __('taxido::front.enter_email') }}"
                                        value="{{ old('email') }}" required>
                                    @error('email')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="form-box">
                                <div class="number-mail-box">
                                    <div class="country-code-section">
                                        <select class="select-2 form-control select-country-code"
                                            id="select-country-code" name="country_code" required>
                                            @foreach (getCountryCodes() as $option)
                                                <option value="{{ $option->calling_code }}"
                                                    data-image="{{ asset('images/flags/' . $option->flag) }}"
                                                    @selected(ltrim((string)$option->calling_code, '+') === ltrim((string)old('country_code', '91'), '+'))>
                                                    {{ $option->calling_code }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="col-sm-12-full">
                                        <input type="tel" name="phone" class="form-control"
                                            id="phoneNumber"
                                            value="{{ old('phone', $rider?->phone ?? '') }}"
                                            placeholder="{{ __('taxido::front.enter_phone_number') }}"
                                            inputmode="numeric" required>
                                    </div>

                                    @error('phone')
                                        <span class="invalid-feedback d-block">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <div class="form-box form-icon">
                                <div class="position-relative">
                                    <i class="ri-lock-2-line"></i>
                                    <input type="password" class="form-control" name="password"
                                        id="password"
                                        placeholder="{{ __('taxido::front.enter_password') }}"
                                        required>
                                    <i class="ri-eye-line toggle-password right-icon"></i>
                                    @error('password')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="form-box form-icon">
                                <div class="position-relative">
                                    <i class="ri-lock-2-line"></i>
                                    <input type="password" class="form-control"
                                        name="password_confirmation"
                                        id="password_confirmation"
                                        placeholder="{{ __('taxido::front.enter_confirm_password') }}"
                                        required>
                                    <i class="ri-eye-line toggle-password right-icon"></i>
                                    @error('password_confirmation')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <button type="button" class="btn gradient-bg-color otp-btn"
                                id="send-phone-otp">
                                Send Phone OTP
                            </button>

                            <div id="recaptcha-container"></div>

                            <div id="phone-otp-section" style="display:none;">
                                <div class="form-box mt-3">
                                    <input type="text" class="form-control" id="phone-otp"
                                        placeholder="Enter 6-digit OTP" inputmode="numeric"
                                        maxlength="6" autocomplete="one-time-code">
                                </div>

                                <button type="button" class="btn gradient-bg-color otp-btn"
                                    id="verify-phone-otp">
                                    Verify OTP
                                </button>

                                <button type="button" class="btn btn-outline mt-2"
                                    id="resend-phone-otp">
                                    Resend OTP
                                </button>

                                <div id="phone-verified-message" class="text-success mt-2"
                                    style="display:none;">
                                    Phone number verified successfully.
                                </div>
                            </div>

                            <button type="submit" id="register-submit"
                                class="btn gradient-bg-color otp-btn spinner-btn" disabled>
                                {{ __('taxido::front.sign_up') }}
                            </button>
                        </form>

                        <div class="or-box">
                            <span>{{ __('taxido::front.or') }}</span>
                        </div>

                        <h6 class="new-account mt-0 flex-column">
                            {{ __('taxido::front.already_have_account') }}
                            <a href="{{ route('front.cab.login.index') }}">
                                {{ __('taxido::front.sign_in') }}
                            </a>
                        </h6>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script src="https://www.gstatic.com/firebasejs/10.14.1/firebase-app-compat.js"></script>
<script src="https://www.gstatic.com/firebasejs/10.14.1/firebase-auth-compat.js"></script>

<script>
(function ($) {
    "use strict";

    const firebaseConfig = @json(config('firebase-web'));

    if (!firebase.apps.length) firebase.initializeApp(firebaseConfig);

    const auth = firebase.auth();
    const authReady = auth.setPersistence(firebase.auth.Auth.Persistence.NONE);

    let confirmationResult = null;
    let verifier = null;
    let verifiedPhone = null;
    let isVerified = false;
    let busy = false;

    const $form = $('#registerForm');
    const $send = $('#send-phone-otp');
    const $verify = $('#verify-phone-otp');
    const $register = $('#register-submit');

    function notifyError(message) {
        if (window.toastr) toastr.error(message);
        else alert(message);
    }

    function notifySuccess(message) {
        if (window.toastr) toastr.success(message);
        else alert(message);
    }

    function getFullPhone() {
        const code = String($('#select-country-code').val() || '').replace(/\D/g, '');
        const phone = String($('#phoneNumber').val() || '').trim().replace(/\D/g, '');
        const full = '+' + code + phone;

        if (!/^\+[1-9]\d{7,14}$/.test(full)) {
            throw new Error('Please enter a valid international phone number.');
        }

        return full;
    }

    function resetVerification() {
        confirmationResult = null;
        verifiedPhone = null;
        isVerified = false;

        $('#firebase_id_token').val('');
        $('#phone-otp').val('');
        $('#phone-otp-section').hide();
        $('#phone-verified-message').hide();
        $register.prop('disabled', true);
    }

    function createRecaptcha() {
        if (verifier) verifier.clear();

        $('#recaptcha-container').empty();

        verifier = new firebase.auth.RecaptchaVerifier(
            'recaptcha-container',
            { size: 'invisible' }
        );

        return verifier.render();
    }

    $form.validate({
        rules: {
            name: { required: true },
            email: { required: true, email: true },
            phone: { required: true, digits: true, minlength: 6, maxlength: 15 },
            country_code: { required: true },
            password: { required: true, minlength: 8 },
            password_confirmation: {
                required: true,
                equalTo: '#password'
            }
        },
        messages: {
            name: { required: 'Please enter your full name.' },
            email: {
                required: 'Please enter your email address.',
                email: 'Please enter a valid email address.'
            },
            phone: {
                required: 'Please enter your phone number.',
                digits: 'Please enter digits only.',
                minlength: 'Phone number must be at least 6 digits.',
                maxlength: 'Phone number cannot exceed 15 digits.'
            },
            password: {
                required: 'Please enter a password.',
                minlength: 'Password must be at least 8 characters.'
            },
            password_confirmation: {
                required: 'Please confirm your password.',
                equalTo: 'Passwords do not match.'
            }
        }
    });

    $('#phoneNumber, #select-country-code').on('input change', function () {
        resetVerification();
    });

    // Send OTP.
    $send.on('click', async function () {
        if (busy || !$form.valid()) return;

        try {
            const phone = getFullPhone();

            busy = true;
            $send.prop('disabled', true).text('Sending OTP...');

            resetVerification();
            await authReady;
            await createRecaptcha();

            confirmationResult = await auth.signInWithPhoneNumber(phone, verifier);

            $('#phone-otp-section').slideDown();
            notifySuccess('OTP sent successfully.');

        } catch (error) {
            console.error('Firebase send OTP:', error.code, error.message);
            notifyError(error.message || 'Unable to send OTP.');

            if (verifier) {
                verifier.clear();
                verifier = null;
            }
        } finally {
            busy = false;
            $send.prop('disabled', false).text('Send Phone OTP');
        }
    });

    // Verify OTP.
    $verify.on('click', async function () {
        const otp = $('#phone-otp').val().trim();

        if (busy) return;

        if (!/^\d{6}$/.test(otp)) {
            notifyError('Please enter a valid 6-digit OTP.');
            return;
        }

        if (!confirmationResult) {
            notifyError('Please request an OTP first.');
            return;
        }

        try {
            busy = true;
            $verify.prop('disabled', true).text('Verifying...');

            const result = await confirmationResult.confirm(otp);
            const phone = getFullPhone();

            if (result.user.phoneNumber !== phone) {
                throw new Error('Verified phone number does not match.');
            }

            const token = await result.user.getIdToken(true);

            $('#firebase_id_token').val(token);
            verifiedPhone = phone;
            isVerified = true;

            $('#phone-verified-message').show();
            $register.prop('disabled', false);

            notifySuccess('Phone number verified successfully.');

        } catch (error) {
            console.error('Firebase verify OTP:', error.code, error.message);

            const messages = {
                'auth/invalid-verification-code': 'Incorrect OTP. Please try again.',
                'auth/code-expired': 'OTP expired. Please request a new OTP.',
                'auth/too-many-requests': 'Too many attempts. Please try again later.'
            };

            notifyError(messages[error.code] || error.message || 'OTP verification failed.');
        } finally {
            busy = false;
            $verify.prop('disabled', false).text('Verify OTP');
        }
    });

    // Resend OTP.
    $('#resend-phone-otp').on('click', function () {
        if (!busy) {
            resetVerification();
            $send.trigger('click');
        }
    });

    // Prevent registration without OTP verification.
    $form.on('submit', function (event) {
        event.preventDefault();

        if (!$form.valid()) return;

        if (!isVerified || !verifiedPhone || !$('#firebase_id_token').val()) {
            notifyError('Please verify your phone number before registering.');
            return;
        }

        try {
            if (getFullPhone() !== verifiedPhone) {
                resetVerification();
                notifyError('Phone number changed. Please verify it again.');
                return;
            }
        } catch (error) {
            notifyError(error.message);
            return;
        }

        $register.prop('disabled', true).text('Creating account...');

        // Native form submission; the server must verify firebase_id_token.
        this.submit();
    });

    $('#select-country-code').select2({
        templateResult: function (data) {
            if (!data.id) return data.text;
            const image = $(data.element).data('image');
            return $('<span><img class="flag-img"> ' + data.text.trim() + '</span>')
                .find('img').attr('src', image).end();
        },
        templateSelection: function (data) {
            if (!data.id) return data.text;
            const image = $(data.element).data('image');
            return $('<span><img class="flag-img"> ' + data.text.trim() + '</span>')
                .find('img').attr('src', image).end();
        },
        escapeMarkup: markup => markup
    });

})(jQuery);
</script>
@endpush
