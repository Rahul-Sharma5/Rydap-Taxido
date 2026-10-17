
@extends('front.layouts.master')
@section('title', 'OTP Verification')

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
                        <h3>{{ __('taxido::front.otp_verification') }}</h3>
                        <h6>{{ __('taxido::front.digit_code') }}</h6>

                        <form method="POST"
                            action="{{ route('front.cab.verify_otp.store') }}"
                            id="otpForm">
                            @csrf

                            <input type="hidden" name="email_or_phone"
                                id="email_or_phone"
                                value="{{ old('email_or_phone', session('email_or_phone')) }}">

                            <input type="hidden" name="country_code"
                                id="country_code"
                                value="{{ old('country_code', session('country_code', '91')) }}">

                            <input type="hidden" name="firebase_id_token"
                                id="firebase_id_token">

                            <div class="form-box">
                                <div class="otp-inputs" id="otp-inputs">
                                    @for ($i = 1; $i <= 6; $i++)
                                        <input type="text"
                                            class="form-control otp-field otp__field__{{ $i }}"
                                            maxlength="1"
                                            name="otp[]"
                                            inputmode="numeric"
                                            pattern="[0-9]"
                                            autocomplete="{{ $i === 1 ? 'one-time-code' : 'off' }}"
                                            oninput="handleInput({{ $i }}, event)"
                                            onkeydown="handleBackspace({{ $i }}, event)"
                                            @if ($i > 1) disabled @endif
                                            @if ($i === 1) autofocus @endif>
                                    @endfor
                                </div>

                                @error('otp')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                                <div id="otp-error" class="text-danger mt-2" style="display:none;"></div>
                            </div>

                            @if (isDemoMode())
                                <h6>{{ __('taxido::front.demo_otp') }}</h6>
                            @endif

                            <button type="submit"
                                class="btn gradient-bg-color otp-btn verifyBtn spinner-btn"
                                disabled>
                                {{ __('taxido::front.verify') }}
                            </button>

                            <h6 class="new-account">
                                {{ __('taxido::front.not_receive_otp') }}
                                <a href="#" id="resend-otp">
                                    {{ __('taxido::front.resent_otp') }}
                                </a>
                            </h6>
                        </form>

                        <div id="recaptcha-container"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')

<script type="module">
import { createPhoneAuth } from "{{ asset('front/js/firebase-phone-auth.js') }}";
(function ($) {
    "use strict";

    const firebaseConfig = @json(config('firebase-web'));

    const phoneAuth = createPhoneAuth(firebaseConfig);
    const authReady = phoneAuth.ready;
    const csrf = @json(csrf_token());
    const resendUrl = @json(route('front.cab.login_with_credential'));
    const verifyText = @json(__('taxido::front.verify'));

    let verificationId = null;
    let phoneMode = false;
    let busy = false;
    let verifier = null;

    function notifyError(message) {
        if (window.toastr) toastr.error(message);
        else alert(message);
    }

    function notifySuccess(message) {
        if (window.toastr) toastr.success(message);
        else alert(message);
    }

    function otpValue() {
        return Array.from(document.querySelectorAll('.otp-field'))
            .map(input => input.value).join('');
    }

    function updateButton() {
        $('.verifyBtn').prop('disabled', !/^\d{6}$/.test(otpValue()));
    }

    window.handleInput = function (n) {
        const current = document.querySelector('.otp__field__' + n);
        const next = document.querySelector('.otp__field__' + (n + 1));

        current.value = current.value.replace(/\D/g, '').slice(0, 1);

        if (current.value && next) {
            next.disabled = false;
            next.focus();
        }

        updateButton();
    };

    window.handleBackspace = function (n, event) {
        const current = document.querySelector('.otp__field__' + n);
        const previous = document.querySelector('.otp__field__' + (n - 1));

        if (event.key === 'Backspace' && !current.value && previous) {
            previous.disabled = false;
            previous.focus();
        }

        setTimeout(updateButton, 0);
    };

    function resetInputs() {
        $('.otp-field').each(function (index) {
            this.value = '';
            this.disabled = index !== 0;
        });

        $('.otp__field__1').trigger('focus');
        updateButton();
    }

    function createRecaptcha() {
        if (verifier) verifier.clear();

        $('#recaptcha-container').empty();

        verifier = phoneAuth.createRecaptcha(
            'recaptcha-container',
            {
                size: 'invisible',
                'expired-callback': () => notifyError('reCAPTCHA expired. Please request the OTP again.')
            }
        );

        return verifier.render();
    }

    function showError(message) {
        $('#otp-error').text(message).show();
    }

    $(document).ready(function () {
        phoneMode = sessionStorage.getItem('taxido_otp_mode') === 'phone';

        if (phoneMode) {
            verificationId = sessionStorage.getItem('taxido_firebase_verification_id');

            const phone = sessionStorage.getItem('taxido_email_or_phone') || '';
            const country = sessionStorage.getItem('taxido_country_code') || '91';

            $('#email_or_phone').val(phone);
            $('#country_code').val(country);

            if (!verificationId) {
                showError('OTP session expired. Please return to login and request a new OTP.');
                $('.verifyBtn').prop('disabled', true);
            }
        }

        // Paste all six OTP digits at once.
        $('#otp-inputs').on('paste', function (event) {
            const clipboard = event.originalEvent.clipboardData;
            const value = clipboard ? clipboard.getData('text').replace(/\D/g, '').slice(0, 6) : '';

            if (!value) return;

            event.preventDefault();

            $('.otp-field').each(function (index) {
                this.value = value[index] || '';
                this.disabled = index > 0 && !value[index];
            });

            updateButton();
        });

        $('#otpForm').on('submit', async function (event) {
            if (!phoneMode) {
                // Existing email OTP verification remains server-side.
                return;
            }

            event.preventDefault();

            if (busy) return;

            const otp = otpValue();

            if (!/^\d{6}$/.test(otp)) {
                showError('Please enter the complete 6-digit OTP.');
                return;
            }

            if (!verificationId) {
                showError('OTP session expired. Please request a new OTP.');
                return;
            }

            busy = true;
            $('.verifyBtn').prop('disabled', true).text('Verifying...');

            try {
                // Use the verification ID saved on the login page.
                const result = await phoneAuth.confirmVerificationId(verificationId, otp);
                const idToken = await result.user.getIdToken(true);
                await phoneAuth.signOut();

                const response = await fetch(@json(route('front.cab.verify_firebase_otp')), {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf},
                    body: JSON.stringify({id_token: idToken})
                });
                const body = await response.json();
                if (!response.ok || !body.success || !body.redirect) {
                    throw new Error(body.message || 'Unable to verify login. Please try again.');
                }
                window.location.href = body.redirect;

            } catch (error) {
                console.error('Firebase OTP verification:', error.code, error.message);

                showError(phoneAuth.errorMessage(error, 'OTP verification failed.'));

                busy = false;
                $('.verifyBtn').prop('disabled', false).text(verifyText);
            }
        });

        $('#resend-otp').on('click', async function (event) {
            event.preventDefault();

            if (busy) return;

            if (!phoneMode) {
                // Existing email OTP resend.
                $.ajax({
                    url: resendUrl,
                    method: 'POST',
                    data: {
                        email_or_phone: $('#email_or_phone').val(),
                        country_code: $('#country_code').val(),
                        _token: csrf
                    },
                    success: function (response) {
                        if (response.success) {
                            notifySuccess(@json(__('taxido::front.otp_resent_successfully')));
                            resetInputs();
                        } else {
                            notifyError(response.message || 'Unable to resend OTP.');
                        }
                    },
                    error: function (xhr) {
                        notifyError(xhr.responseJSON?.message || 'Unable to resend OTP.');
                    }
                });
                return;
            }

            try {
                busy = true;
                $('#resend-otp').text('Sending...');

                const fullPhone = sessionStorage.getItem('taxido_phone');

                if (!fullPhone) {
                    throw new Error('Phone session expired. Please return to login.');
                }

                await authReady;
                await createRecaptcha();

                const confirmation = await verifier.send(fullPhone);

                verificationId = confirmation.verificationId;
                sessionStorage.setItem('taxido_firebase_verification_id', verificationId);

                resetInputs();
                notifySuccess('A new OTP has been sent.');

            } catch (error) {
                console.error('Resend OTP:', error.code, error.message);
                notifyError(phoneAuth.errorMessage(error, 'Unable to resend OTP.'));
            } finally {
                busy = false;
                $('#resend-otp').text(@json(__('taxido::front.resent_otp')));
            }
        });
    });
})(jQuery);
</script>
@endpush
