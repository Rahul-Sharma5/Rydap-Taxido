<?php if(Session::has('success')): ?>
    <p class="alert alert-success"><?php echo e(Session::get('success')); ?></p>
<?php endif; ?>
<?php if(Session::has('error')): ?>
    <p class="alert alert-danger"><?php echo e(Session::get('error')); ?></p>
<?php endif; ?>
<?php if(Session::has('warning')): ?>
    <p class="alert alert-warning"><?php echo e(Session::get('warning')); ?></p>
<?php endif; ?>
<?php /**PATH D:\solve-project\app\vendor\phpblaze\bladelib\src\Libs/../Templates/sts.blade.php ENDPATH**/ ?>