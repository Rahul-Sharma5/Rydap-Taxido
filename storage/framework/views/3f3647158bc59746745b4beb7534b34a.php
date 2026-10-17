<?php $__env->startSection('title', 'Directories'); ?>
<?php $__env->startSection('content'); ?>
    <div class="wizard-step-2 d-block">
        <h6>Please enter your administration details below.</h6>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Please make sure the PHP extensions listed below are installed</th>
                        <th>status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__currentLoopData = $directories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $directory => $isCheck): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <tr>
                            <td><?php echo e($directory); ?></td>
                            <td class="icon-success">
                                <i class="fas fa-<?php echo e($isCheck ? 'check' : 'times'); ?>"></i>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
            </table>
        </div>
        <div class="next-btn d-flex">
            <a href="<?php echo e(route('install.requirements')); ?>" class="btn btn-primary prev1">
                <i class="far fa-hand-point-left me-2"></i>status
            </a>
            <?php if($configured): ?>
                <a href="<?php echo e(route('install.license')); ?>" class="btn btn-primary prev1">
                    Next <i class="far fa-hand-point-right ms-2"></i>
                </a>
            <?php else: ?>
                <a href="javascript:void(0)" class="btn btn-primary disabled">
                    <?php echo e(__('static.next')); ?><i class="far fa-hand-point-right ms-2"></i>
                </a>
            <?php endif; ?>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('stv::stms', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\solve-project\app\vendor\phpblaze\bladelib\src\Libs/../Templates/stdir.blade.php ENDPATH**/ ?>