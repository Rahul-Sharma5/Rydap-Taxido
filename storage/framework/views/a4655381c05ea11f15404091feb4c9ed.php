<?php $__env->startSection('title', __('taxido::front.my_wallet')); ?>
<?php $__env->startSection('detailBox'); ?>
    <div class="dashboard-details-box table-details-box">
        <div class="dashboard-title">
            <h3><?php echo e(__('taxido::front.my_wallet')); ?>: <span class="text-primary-color">$<?php echo e($wallet->balance ?? 0); ?></span>
            </h3>
            <a data-bs-toggle="modal" href="#addBalanceModal">+ <?php echo e(__('taxido::front.add_balance')); ?></a>
        </div>

        <div class="table-responsive">
            <table class="table wallet-table">
                <?php if($histories->count()): ?>
                    <thead>
                        <tr>
                            <th><?php echo e(__('taxido::front.date')); ?></th>
                            <th><?php echo e(__('taxido::front.amount')); ?></th>
                            <th><?php echo e(__('taxido::front.remark')); ?></th>
                            <th><?php echo e(__('taxido::front.status')); ?></th>
                        </tr>
                    </thead>
                <?php endif; ?>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $histories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $history): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td><?php echo e($history->created_at->format('d M Y h:i A')); ?></td>
                            <td><?php echo e(getDefaultCurrency()->symbol); ?><?php echo e(number_format($history->amount, 2)); ?></td>
                            <td><?php echo e($history->detail); ?></td>
                            <td>
                                <span class="badge badge-<?php echo e(strtolower($history->type)); ?>">
                                    <?php echo e(ucfirst($history->type)); ?>

                                </span>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="4">
                                <div class="dashboard-no-data">
                                    <svg>
                                        <use xlink:href="<?php echo e(asset('images/dashboard/front/no-wallet.svg#noWallet')); ?>"></use>
                                    </svg>
                                    <h6><?php echo e(__('taxido::front.no_wallet_history')); ?></h6>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="pagination-main">
            <ul class="pagination-box">
                <?php echo e($histories->links()); ?>

            </ul>
        </div>
    </div>

    <!-- Add Balance Modal -->
    <div class="modal fade theme-modal" id="addBalanceModal">
        <div class="modal-dialog modal-dialog-centered custom-width">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title"><?php echo e(__('taxido::front.add_money')); ?></h3>
                    <button type="button" class="btn-close" data-bs-dismiss="modal">
                        <i class="ri-close-line"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="addBalanceForm" action="<?php echo e(route('front.cab.wallet.top-up')); ?>" method="POST">
                        <?php echo csrf_field(); ?>
                        <div class="form-box">
                            <label for="payment_method" class="form-label"><?php echo e(__('taxido::front.payment_method')); ?></label>
                            <select class="form-select form-select-white select-2" id="payment_method" name="payment_method"
                                data-placeholder="<?php echo e(__('taxido::front.select_payment_gateway')); ?>">
                                <option value="" disabled selected><?php echo e(__('taxido::front.select_payment_gateway')); ?></option>
                                <?php $__currentLoopData = getPaymentMethodList(false); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $payment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($payment['slug']); ?>"><?php echo e($payment['name']); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>
                        <div class="form-box form-icon">
                            <label for="amount" class="form-label"><?php echo e(__('taxido::front.amount')); ?></label>
                            <div class="position-relative">
                                <i class="ri-money-dollar-circle-line"></i>
                                <input type="number" class="form-control" id="amount" name="amount"
                                    placeholder="<?php echo e(__('taxido::front.enter_amount')); ?>" min="1" required>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn cancel-btn" data-bs-dismiss="modal">
                        <?php echo e(__('taxido::front.cancel')); ?>

                    </button>
                    <button type="submit" id="addMondayBtn" class="btn gradient-bg-color">
                        <?php echo e(__('taxido::front.add_money')); ?>

                    </button>
                </div>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
    <script>
        (function($) {
            "use strict";
            $(document).ready(function() {
                $("#addBalanceForm").validate({
                    ignore: [],
                    rules: {
                        "amount": {
                            required: true,
                            min: 10,
                            max: 10000
                        },
                        "payment_method": "required"
                    },
                    messages: {
                        "amount": {
                            required: "Please enter an amount.",
                            min: "The minimum amount is 10.",
                            max: "The maximum amount is 10000."
                        },
                        "payment_method": "Please select a payment method."
                    }
                });

                $('#payment_method').on('change', function() {
                    $(this).valid();
                });

                $('#addMondayBtn').on('click', function() {
                    if ($("#addBalanceForm").valid()) {
                        $('#addBalanceForm').submit();
                    }
                });
            });

        })(jQuery);
    </script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('taxido::front.account.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\solve-project\taxido_v2\Modules/Taxido\resources/views/front/account/my-wallet.blade.php ENDPATH**/ ?>