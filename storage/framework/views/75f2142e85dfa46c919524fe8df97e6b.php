<?php $__env->startSection('title', __('taxido::front.location')); ?>
<?php $__env->startSection('detailBox'); ?>
<div class="dashboard-details-box">
    <div class="dashboard-title">
        <h3><?php echo e(__('taxido::front.save_address')); ?></h3>
        <a href="<?php echo e(route('front.cab.location.create')); ?>" class="btn p-0"><?php echo e(__('taxido::front.add_address')); ?></a>
    </div>

    <ul class="address-list">
        <?php $__empty_1 = true; $__currentLoopData = $locations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $location): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <li class="address-box">
                <div class="address-top">
                    <span class="badge badge-primary"><?php echo e($location->type); ?></span>
                    <div class="edit-delete">
                        <a href="<?php echo e(route('front.cab.location.edit', $location->id)); ?>" class="btn edit">
                            <i class="ri-edit-line"></i>
                        </a>
                        <button type="button" class="btn delete" data-bs-toggle="modal" data-bs-target="#confirmationModal<?php echo e($location->id); ?>">
                            <i class="ri-delete-bin-line"></i>
                        </button>
                    </div>
                </div>
                <div class="address-bottom">
                    <p><?php echo e(__('taxido::front.address')); ?>: <span><?php echo e($location->location); ?></span></p>
                </div>

                <!-- Delete Confirmation Modal -->
                <div class="modal theme-modal fade confirmation-modal" id="confirmationModal<?php echo e($location->id); ?>" tabindex="-1" role="dialog" aria-labelledby="confirmationLabel<?php echo e($location->id); ?>" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-body text-start confirmation-data">
                                <div class="main-img">
                                    <div class="delete-icon">
                                        <i class="ri-question-mark"></i>
                                    </div>
                                </div>
                                <h4 class="modal-title"><?php echo e(__('taxido::static.chats.confirmation')); ?></h4>
                                <p><?php echo e(__('taxido::front.modal')); ?></p>
                            </div>

                            <div class="modal-footer">
                                <form action="<?php echo e(route('front.cab.location.destroy', $location->id)); ?>" method="POST">
                                    <?php echo csrf_field(); ?>
                                    <?php echo method_field('DELETE'); ?>
                                    <button type="button" class="btn cancel-btn" data-bs-dismiss="modal"><?php echo e(__('taxido::front.no')); ?></button>
                                    <button type="submit" class="btn gradient-bg-color spinner-btn"><?php echo e(__('taxido::front.yes')); ?></button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </li>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <tr>
            <td colspan="8">
                <div class="dashboard-no-data">
                    <svg>
                        <use xlink:href="<?php echo e(asset('images/dashboard/front/location.svg#location')); ?>"></use>
                    </svg>
                    <h6><?php echo e(__('taxido::front.no_locations')); ?></h6>
                </div>
            </td>
        </tr>
        <?php endif; ?>
    </ul>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('taxido::front.account.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\solve-project\taxido_v2\Modules/Taxido\resources/views/front/location/location.blade.php ENDPATH**/ ?>