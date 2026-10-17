

<?php $__env->startSection('title', __('taxido::front.register')); ?>

<?php $__env->startSection('content'); ?>
<section class="authentication-section section-b-space">
    <div class="container">
        <div class="auth-form-box">
            <div class="row align-items-center">
                <div class="col-xl-7 col-lg-6 d-lg-block d-none">
                    <img src="<?php echo e(asset('images/authentication-img.png')); ?>" class="img-fluid auth-image">
                </div>

                <div class="col-xl-5 col-lg-6">
                    <div class="auth-right-box">
                        <h3><?php echo e(__('taxido::front.create_account')); ?></h3>
                        <h6><?php echo e(__('taxido::front.sign_up_minutes')); ?></h6>

                        <form method="POST" action="<?php echo e(route('front.cab.register.store')); ?>" id="registerForm">
                            <?php echo csrf_field(); ?>

                            <input type="hidden" name="firebase_id_token" id="firebase_id_token">
                            <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><p class="text-danger" role="alert"><?php echo e($error); ?></p><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

                            <div class="form-box form-icon">
                                <div class="position-relative">
                                    <i class="ri-user-line"></i>
                                    <input type="text" class="form-control" name="name"
                                        placeholder="<?php echo e(__('taxido::front.full_name')); ?>"
                                        value="<?php echo e(old('name')); ?>" required>
                                    <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                        <span class="text-danger"><?php echo e($message); ?></span>
                                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                </div>
                            </div>

                            <div class="form-box form-icon">
                                <div class="position-relative">
                                    <i class="ri-mail-line"></i>
                                    <input type="email" class="form-control" name="email"
                                        placeholder="<?php echo e(__('taxido::front.enter_email')); ?>"
                                        value="<?php echo e(old('email')); ?>" required>
                                    <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                        <span class="text-danger"><?php echo e($message); ?></span>
                                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                </div>
                            </div>

                            <div class="form-box">
                                <div class="number-mail-box">
                                    <div class="country-code-section">
                                        <select class="select-2 form-control select-country-code"
                                            id="select-country-code" name="country_code" required>
                                            <?php $__currentLoopData = getCountryCodes(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $option): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <option value="<?php echo e($option->calling_code); ?>"
                                                    data-image="<?php echo e(asset('images/flags/' . $option->flag)); ?>"
                                                    <?php if(ltrim((string)$option->calling_code, '+') === ltrim((string)old('country_code', '91'), '+')): echo 'selected'; endif; ?>>
                                                    <?php echo e($option->calling_code); ?>

                                                </option>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        </select>
                                    </div>

                                    <div class="col-sm-12-full">
                                        <input type="tel" name="phone" class="form-control"
                                            id="phoneNumber"
                                            value="<?php echo e(old('phone', $rider?->phone ?? '')); ?>"
                                            placeholder="<?php echo e(__('taxido::front.enter_phone_number')); ?>"
                                            inputmode="numeric" required>
                                    </div>

                                    <?php $__errorArgs = ['phone'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                        <span class="invalid-feedback d-block">
                                            <strong><?php echo e($message); ?></strong>
                                        </span>
                                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                </div>
                            </div>

                            <div class="form-box form-icon">
                                <div class="position-relative">
                                    <i class="ri-lock-2-line"></i>
                                    <input type="password" class="form-control" name="password"
                                        id="password"
                                        placeholder="<?php echo e(__('taxido::front.enter_password')); ?>"
                                        required>
                                    <i class="ri-eye-line toggle-password right-icon"></i>
                                    <?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                        <span class="text-danger"><?php echo e($message); ?></span>
                                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                </div>
                            </div>

                            <div class="form-box form-icon">
                                <div class="position-relative">
                                    <i class="ri-lock-2-line"></i>
                                    <input type="password" class="form-control"
                                        name="password_confirmation"
                                        id="password_confirmation"
                                        placeholder="<?php echo e(__('taxido::front.enter_confirm_password')); ?>"
                                        required>
                                    <i class="ri-eye-line toggle-password right-icon"></i>
                                    <?php $__errorArgs = ['password_confirmation'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                        <span class="text-danger"><?php echo e($message); ?></span>
                                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
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
                                <?php echo e(__('taxido::front.sign_up')); ?>

                            </button>
                        </form>

                        <div class="or-box">
                            <span><?php echo e(__('taxido::front.or')); ?></span>
                        </div>

                        <h6 class="new-account mt-0 flex-column">
                            <?php echo e(__('taxido::front.already_have_account')); ?>

                            <a href="<?php echo e(route('front.cab.login.index')); ?>">
                                <?php echo e(__('taxido::front.sign_in')); ?>

                            </a>
                        </h6>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>

<script type="module">
import { createPhoneAuth } from "<?php echo e(asset('front/js/firebase-phone-auth.js')); ?>";
(function ($) {
    "use strict";

    const firebaseConfig = <?php echo json_encode(config('firebase-web'), 15, 512) ?>;

    const phoneAuth = createPhoneAuth(firebaseConfig);
    const authReady = phoneAuth.ready;

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
        if (verifier) verifier.clear();
        verifier = null;
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

        verifier = phoneAuth.createRecaptcha(
            'recaptcha-container',
            {
                size: 'invisible',
                'expired-callback': () => notifyError('reCAPTCHA expired. Please request the OTP again.')
            }
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

            confirmationResult = await verifier.send(phone);

            $('#phone-otp-section').slideDown();
            notifySuccess('OTP sent successfully.');

        } catch (error) {
            console.error('Firebase send OTP:', error.code, error.message);
            notifyError(phoneAuth.errorMessage(error, 'Unable to send OTP.'));

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
            await phoneAuth.signOut();

            $('#firebase_id_token').val(token);
            verifiedPhone = phone;
            isVerified = true;

            $('#phone-verified-message').show();
            $register.prop('disabled', false);

            notifySuccess('Phone number verified successfully.');

        } catch (error) {
            console.error('Firebase verify OTP:', error.code, error.message);

            notifyError(phoneAuth.errorMessage(error, 'OTP verification failed.'));
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
<?php $__env->stopPush(); ?>

<?php echo $__env->make('front.layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\solve-project\app\Modules/Taxido\resources/views/front/auth/register.blade.php ENDPATH**/ ?>