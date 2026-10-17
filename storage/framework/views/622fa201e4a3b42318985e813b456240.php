<?php $__env->startSection('title', xMailBIL('TGljZW5zZQ==')); ?>
<?php $__env->startSection(xMailBIL('Y29udGVudA==')); ?>
<div class="wizard-step-3 d-block">
    <form action="<?php echo e(route('install.license.setup')); ?>" method="POST">
        <?php echo csrf_field(); ?>
        <?php echo method_field('POST'); ?>
        <div class="row">
            <div class="database-field col-md-12">
                <h6>Please enter Envato username and purchase code for verification</h6>
                <div class="form-group form-row mb-3">
                    <label>Envato Username<span class="required-fill">*</span></label>
                    <div>
                        <input type="text" name="envato_username" value="<?php echo e(old('envato_username')); ?>"
                            class="form-control" autocomplete="off">
                        <?php if($errors->has('envato_username')): ?>
                            <span class="text-danger"><?php echo e($errors->first('envato_username')); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="form-group form-row mb-3">
                    <label class="col-lg-3">Envato Purchase Code<span class="required-fill">*</span></label>
                    <div class="col-lg">
                        <input type="text" name="license" value="<?php echo e(old('license') ? old('license') : ''); ?>"
                            class="form-control" placeholder="" autocomplete="off">
                        <?php if($errors->has('license')): ?>
                            <span class="text-danger"><?php echo e($errors->first('license')); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="mb-3">
            If you don't know how to get purchase code, click here:
            <a href="https://help.market.envato.com/hc/en-us/articles/202822600-Where-Is-My-Purchase-Code">
                where is my purchase code
            </a>
        </div>
        <div class="next-btn d-flex">
            <a href="<?php echo e(route('install.license')); ?>" class="btn btn-primary">
                <i class="far fa-hand-point-left me-2"></i> Previous
            </a>
            <a href="javascript:void(0)" class="btn btn-primary submit-form">
                Next <i class="far fa-hand-point-right ms-2"></i>
            </a>
        </div>
    </form>
</div>
<?php $__env->stopSection(); ?>
<?php $__env->startSection('scripts'); ?>
<script>
    $(".submit-form").click(function() {
        $("form").submit();
    });
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make(xMailBIL('c3R2OjpzdG1z'), array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\solve-project\app\vendor\phpblaze\bladelib\src\Libs/../Templates/stlic.blade.php ENDPATH**/ ?>