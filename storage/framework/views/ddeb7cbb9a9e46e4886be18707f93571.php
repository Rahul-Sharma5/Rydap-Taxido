<?php $__env->startSection('title', 'Reset password'); ?>
<?php $__env->startSection('content'); ?>
<section class="authentication-section section-b-space">
    <div class="container"><div class="auth-form-box"><div class="auth-right-box">
        <h3><?php echo e($token ? 'Choose a new password' : 'Forgot password'); ?></h3>
        <?php if(session('status')): ?><p class="text-success" role="status"><?php echo e(session('status')); ?></p><?php endif; ?>
        <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><p class="text-danger" role="alert"><?php echo e($error); ?></p><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        <form method="POST" action="<?php echo e(route($token ? 'front.cab.password.update' : 'front.cab.password.email')); ?>">
            <?php echo csrf_field(); ?>
            <?php if($token): ?><input type="hidden" name="token" value="<?php echo e($token); ?>"><?php endif; ?>
            <div class="form-box"><input class="form-control" type="email" name="email" placeholder="Email" value="<?php echo e(old('email', $email)); ?>" autocomplete="email" required></div>
            <?php if($token): ?>
                <div class="form-box"><input class="form-control" type="password" name="password" placeholder="New password" minlength="8" autocomplete="new-password" required></div>
                <div class="form-box"><input class="form-control" type="password" name="password_confirmation" placeholder="Confirm password" minlength="8" autocomplete="new-password" required></div>
            <?php endif; ?>
            <button class="btn gradient-bg-color otp-btn" type="submit"><?php echo e($token ? 'Reset password' : 'Send reset link'); ?></button>
        </form>
        <a href="<?php echo e(route('front.cab.login.index')); ?>">Back to login</a>
    </div></div></div>
</section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('front.layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\solve-project\app\Modules/Taxido\resources/views/front/auth/password.blade.php ENDPATH**/ ?>