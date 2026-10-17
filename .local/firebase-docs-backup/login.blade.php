@extends('front.layouts.master')

@section('title', __('taxido::front.login'))

@section('content')
    <section class="authentication-section section-b-space">
        <div class="container">
            @php
                $cabSettings = getSettings();
            @endphp

            <div class="auth-form-box">
                <div class="row align-items-center">
                    <div class="col-xl-7 col-lg-6 d-lg-block d-none">
                        <img src="{{ asset('images/authentication-img.png') }}" class="img-fluid auth-image" alt="Authentication">
                    </div>

                    <div class="col-xl-5 col-lg-6">
                        <div class="auth-right-box">
                            <h3>{{ __('taxido::front.welcome_to', ['app_name' => env('APP_NAME')]) }}</h3>

                            <h6>{{ __('taxido::front.account_information') }}</h6>

                            @if(session('status'))<p class="text-success" role="status">{{ session('status') }}</p>@endif
                            <form method="POST" id="loginForm">
                                @csrf

                                <div class="form-box">
                                    <div class="number-mail-box">
                                        <div class="country-code-section" style="display: block;">
                                            <select class="select-2 form-control"
                                                id="select-country-code"
                                                name="country_code">

                                                @foreach (getCountryCodes() as $option)
                                                    <option
                                                        value="{{ $option->calling_code }}"
                                                        data-image="{{ asset('images/flags/' . $option->flag) }}"
                                                        @selected(
                                                            ltrim((string) $option->calling_code, '+') ===
                                                            ltrim((string) old('country_code', '91'), '+')
                                                        )
                                                    >
                                                        {{ $option->calling_code }}
                                                    </option>
                                                @endforeach

                                            </select>
                                        </div>

                                        <input
                                            type="text"
                                            class="form-control"
                                            name="email_or_phone"
                                            id="email_or_phone"
                                            placeholder="{{ __('taxido::front.enter_phone_email') }}"
                                            value="{{ old('email_or_phone') }}"
                                            autocomplete="username"
                                        >

                                        @error('email_or_phone')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                <button
                                    type="button"
                                    class="btn gradient-bg-color otp-btn"
                                    id="get-otp-btn">
                                    {{ __('taxido::front.get_otp') }}
                                </button>

                                <div id="email-password-section" style="display:none;">
                                    <div class="form-box"><input type="password" id="email-password" class="form-control" placeholder="Password" autocomplete="current-password"></div>
                                    <button type="button" id="password-login-btn" class="btn gradient-bg-color otp-btn">Login with password</button>
                                    <a href="{{ route('front.cab.password.request') }}">Forgot password?</a>
                                </div>
                                {{-- Firebase reCAPTCHA --}}
                                <div id="recaptcha-container" class="mt-3"></div>

                                {{-- Phone OTP verification --}}
                                <div id="firebase-otp-section" style="display: none;">
                                    <div class="form-box mt-3">
                                        <input
                                            type="text"
                                            class="form-control"
                                            id="firebase-otp"
                                            placeholder="Enter 6-digit OTP"
                                            inputmode="numeric"
                                            maxlength="6"
                                            autocomplete="one-time-code"
                                        >
                                    </div>

                                    <button
                                        type="button"
                                        class="btn gradient-bg-color otp-btn mt-3"
                                        id="verify-firebase-otp-btn">
                                        Verify OTP
                                    </button>

                                    <button
                                        type="button"
                                        class="btn btn-outline mt-2"
                                        id="resend-firebase-otp-btn">
                                        Resend OTP
                                    </button>
                                </div>
                            </form>

                            <div class="or-box">
                                <span>{{ __('taxido::front.login_with') }}</span>
                            </div>

                            @isset($cabSettings['activation']['default_credentials'])
                                @if ((int) $cabSettings['activation']['default_credentials'])
                                    <div class="demo-credential">
                                        <button
                                            type="button"
                                            class="btn btn-outline default-credentials guest-btn"
                                            data-email="rider@example.com">
                                            <span>
                                                <i class="ri-user-3-fill"></i>
                                            </span>
                                            {{ __('taxido::front.demo_user') }}
                                        </button>
                                    </div>
                                @endif
                            @endisset

                            <h6 class="new-account">
                                {{ __('taxido::front.don’t_have_account') }}
                                <a href="{{ route('front.cab.register.index') }}">
                                    {{ __('taxido::front.sign_up') }}
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

    {{-- Firebase SDK --}}
    <script src="https://www.gstatic.com/firebasejs/10.14.1/firebase-app-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/10.14.1/firebase-auth-compat.js"></script>

    <script>
        (function ($) {
            "use strict";

            $(document).ready(function () {

                const firebaseConfig = @json(config('firebase-web'));

                if (!firebase.apps.length) {
                    firebase.initializeApp(firebaseConfig);
                }

                const auth = firebase.auth();
                const authReady = auth.setPersistence(firebase.auth.Auth.Persistence.NONE);

                const csrfToken = @json(csrf_token());
                const loginUrl = @json(route('front.cab.login_with_credential'));
                const verifyUrl = @json(route('front.cab.verify_firebase_otp'));
                const emailVerifyUrl = @json(route('front.cab.verify_otp'));
                const otpButtonText = @json(__('taxido::front.get_otp'));
                const failedOtpMessage = @json(__('taxido::front.failed_resent_otp'));

                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

                let confirmationResult = null;
                let verifiedIdToken = null;
                let busy = false;
                let recaptchaVerifier = null;
                let isNumb = false;

                function showError(message) {
                    if (typeof toastr !== 'undefined') {
                        toastr.error(message);
                    } else {
                        alert(message);
                    }
                }

                function showSuccess(message) {
                    if (typeof toastr !== 'undefined') {
                        toastr.success(message);
                    } else {
                        alert(message);
                    }
                }

                function getErrorMessage(xhr, fallback) {
                    if (xhr.responseJSON?.message) {
                        return xhr.responseJSON.message;
                    }

                    if (xhr.responseJSON?.errors) {
                        return Object.values(xhr.responseJSON.errors)
                            .flat()
                            .join('\n');
                    }

                    return fallback;
                }

                function setButtonLoading($button, loading, text) {
                    $button.prop('disabled', loading);

                    if (loading) {
                        $button.html(
                            text +
                            ' <span class="spinner-border spinner-border-sm ms-2 spinner_loader"></span>'
                        );
                    } else {
                        $button.text(text);
                    }
                }

                function getPhoneNumber() {
                    const countryCode = String(
                        $('#select-country-code').val() || ''
                    ).replace(/\D/g, '');

                    const phone = String(
                        $('#email_or_phone').val() || ''
                    ).trim().replace(/\D/g, '');

                    if (!countryCode || !phone) {
                        throw new Error('Please enter your country code and phone number.');
                    }

                    const fullPhoneNumber = '+' + countryCode + phone;

                    if (!/^\+[1-9]\d{7,14}$/.test(fullPhoneNumber)) {
                        throw new Error('Please enter a valid international phone number.');
                    }

                    return fullPhoneNumber;
                }

                function setupRecaptcha() {
                    if (recaptchaVerifier) {
                        return Promise.resolve();
                    }

                    recaptchaVerifier = new firebase.auth.RecaptchaVerifier(
                        'recaptcha-container',
                        {
                            size: 'normal',
                            callback: function () {},
                            'expired-callback': function () {
                                showError('reCAPTCHA expired. Please verify again.');
                            }
                        }
                    );

                    return recaptchaVerifier.render();
                }

                function resetRecaptcha() {
                    if (recaptchaVerifier) {
                        recaptchaVerifier.clear();
                    }

                    recaptchaVerifier = null;
                    $('#recaptcha-container').empty();
                }

                // Detect email or phone without changing the original UI structure.
                $('#email_or_phone').on('input', function () {
                    const value = $(this).val().trim();
                    const hasLetters = /[a-zA-Z]/.test(value);
                    $('#email-password-section').toggle(isNaN(Number(value)) && value.includes('@'));
                    const isEmail = emailRegex.test(value);

                    if (isEmail || hasLetters) {
                        $('.country-code-section').hide();
                        $('#recaptcha-container').hide();
                        isNumb = false;
                    } else {
                        $('.country-code-section').show();
                        $('#recaptcha-container').show();
                        isNumb = true;
                    }

                    if (confirmationResult) {
                        confirmationResult = null;
                        verifiedIdToken = null;
                        $('#firebase-otp-section').slideUp();
                        $('#firebase-otp').val('');
                    }

                    $('#email_or_phone').rules('remove', 'minlength maxlength');

                    if (isNumb) {
                        $('#email_or_phone').rules('add', {
                            minlength: 6,
                            maxlength: 15
                        });
                    }
                });

                // Existing validation.
                $('#loginForm').validate({
                    rules: {
                        email_or_phone: {
                            required: true
                        }
                    },
                    messages: {
                        email_or_phone: {
                            required: 'Please enter your email or phone number.',
                            minlength: 'Phone number must be at least 6 digits.',
                            maxlength: 'Phone number cannot exceed 15 digits.'
                        }
                    }
                });

                $('#loginForm').on('submit', function (event) {
                    event.preventDefault();
                    $(emailRegex.test($('#email_or_phone').val().trim()) && $('#email-password').val() ? '#password-login-btn' : '#get-otp-btn').trigger('click');
                });
                $('#password-login-btn').on('click', async function () {
                    const $button = $(this);
                    if ($button.prop('disabled')) return;
                    setButtonLoading($button, true, 'Signing in...');
                    try {
                        const response = await $.ajax({
                            url: @json(route('front.cab.password.login')), method: 'POST',
                            headers: {'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken},
                            data: {email: $('#email_or_phone').val().trim(), password: $('#email-password').val()}
                        });
                        window.location.href = response.redirect;
                    } catch (xhr) { showError(getErrorMessage(xhr, 'Unable to sign in. Please try again.')); }
                    finally { setButtonLoading($button, false, 'Login with password'); }
                });
                $('#select-country-code').on('change', function () {
                    confirmationResult = null;
                    verifiedIdToken = null;
                    $('#firebase-otp-section').hide();
                    $('#firebase-otp').val('');
                    resetRecaptcha();
                });
                // Send email OTP through the existing Laravel flow,
                // or send phone OTP through Firebase.
                $('#get-otp-btn').on('click', async function () {
                    if (busy) return;
                    if (!$('#loginForm').valid()) {
                        return;
                    }

                    const value = $('#email_or_phone').val().trim();
                    const isEmail = emailRegex.test(value);
                    const $button = $(this);

                    if (isEmail) {
                        $.ajax({
                            url: loginUrl,
                            method: 'POST',
                            data: {
                                email_or_phone: value,
                                country_code: null,
                                _token: csrfToken
                            },
                            beforeSend: function () {
                                busy = true;
                                setButtonLoading($button, true, otpButtonText);
                            },
                            success: function (response) {
                                if (response.success) {
                                    sessionStorage.removeItem('taxido_otp_mode');
                                    sessionStorage.removeItem('taxido_firebase_verification_id');
                                    window.location.href = emailVerifyUrl;
                                } else {
                                    showError(response.message || failedOtpMessage);
                                }
                            },
                            error: function (xhr) {
                                showError(getErrorMessage(xhr, failedOtpMessage));
                            },
                            complete: function () {
                                busy = false;
                                setButtonLoading($button, false, otpButtonText);
                            }
                        });

                        return;
                    }

                    try {
                        const phoneNumber = getPhoneNumber();
                        busy = true;
                        confirmationResult = null;
                        verifiedIdToken = null;
                        resetRecaptcha();

                        setButtonLoading($button, true, otpButtonText);

                        await authReady;
                        await setupRecaptcha();

                        confirmationResult = await auth.signInWithPhoneNumber(
                            phoneNumber,
                            recaptchaVerifier
                        );

                        $('#firebase-otp-section').slideDown();
                        $('#firebase-otp').trigger('focus');

                        showSuccess('OTP sent successfully.');
                    } catch (error) {
                        console.error('Firebase send OTP error:', error.code);

                        showError(
                            error.message || 'Unable to send OTP. Please try again.'
                        );

                        resetRecaptcha();
                    } finally {
                        busy = false;
                        setButtonLoading($button, false, otpButtonText);
                    }
                });

                // Verify Firebase phone OTP and authenticate with Laravel.
                $('#verify-firebase-otp-btn').on('click', async function () {
                    if (busy) return;
                    const otp = $('#firebase-otp').val().trim();
                    const $button = $(this);

                    if (!/^\d{6}$/.test(otp)) {
                        showError('Please enter a valid 6-digit OTP.');
                        return;
                    }

                    if (!confirmationResult) {
                        showError('Please request an OTP first.');
                        return;
                    }

                    try {
                        busy = true;
                        $button.prop('disabled', true).text('Verifying...');

                        if (!verifiedIdToken) {
                            const credential = await confirmationResult.confirm(otp);
                            if (credential.user.phoneNumber !== getPhoneNumber()) {
                                throw new Error('Phone number changed. Please request a new OTP.');
                            }
                            verifiedIdToken = await credential.user.getIdToken(true);
                        }
                        const idToken = verifiedIdToken;

                        const response = await $.ajax({
                            url: verifyUrl,
                            method: 'POST',
                            contentType: 'application/json',
                            dataType: 'json',
                            headers: {
                                'X-CSRF-TOKEN': csrfToken,
                                'Accept': 'application/json'
                            },
                            data: JSON.stringify({
                                id_token: idToken
                            })
                        });

                        if (response.success && response.redirect) {
                            window.location.href = response.redirect;
                            return;
                        }

                        showError(response.message || 'Login failed.');
                    } catch (error) {
                        console.error('Firebase OTP verification error:', error.code || error.status);

                        showError(
                            error.responseJSON?.message ||
                            error.message ||
                            'OTP verification failed.'
                        );
                    } finally {
                        busy = false;
                        $button.prop('disabled', false).text('Verify OTP');
                    }
                });

                // Resend phone OTP.
                $('#resend-firebase-otp-btn').on('click', async function () {
                    if (busy) return;
                    const $button = $(this);

                    confirmationResult = null;
                    verifiedIdToken = null;
                    $('#firebase-otp').val('');

                    resetRecaptcha();
                    $('#recaptcha-container').show();

                    $('#get-otp-btn').trigger('click');
                });

                // Existing demo account functionality.
                $('.default-credentials').on('click', function (event) {
                    event.preventDefault();

                    $('#email_or_phone').val($(this).data('email')).trigger('input');

                    $('.country-code-section').hide();
                    $('#recaptcha-container').hide();
                    $('#firebase-otp-section').hide();

                    confirmationResult = null;
                    verifiedIdToken = null;
                });

                // Keep the existing Select2 country-code UI.
                $('#select-country-code').select2({
                    templateResult: function (data) {
                        if (!data.id) return data.text;

                        const imageUrl = $(data.element).data('image');

                        return $(`
                            <span>
                                <img
                                    src="${imageUrl}"
                                    class="flag-img"
                                    onerror="this.onerror=null;this.src='{{ asset('front/images/placeholder/49x37.png') }}';"
                                />
                                + ${data.text.trim()}
                            </span>
                        `);
                    },

                    templateSelection: function (data) {
                        if (!data.id) return data.text;

                        const imageUrl = $(data.element).data('image');

                        return $(`
                            <span>
                                <img
                                    src="${imageUrl}"
                                    class="flag-img"
                                    onerror="this.onerror=null;this.src='{{ asset('front/images/placeholder/49x37.png') }}';"
                                />
                                + ${data.text.trim()}
                            </span>
                        `);
                    },

                    escapeMarkup: function (markup) {
                        return markup;
                    }
                });

            });
        })(jQuery);
    </script>

@endpush
