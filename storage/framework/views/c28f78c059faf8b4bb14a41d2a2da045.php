<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo $__env->yieldContent('title'); ?> - Installation Wizard</title>
    <link rel="icon" href="<?php echo e(asset('favicon.png')); ?>" type="image/x-icon">
    <link rel="shortcut icon" href="<?php echo e(asset('favicon.png')); ?>" type="image/x-icon">

    <!--Google font-->
    <link href="https://fonts.googleapis.com/css?family=Roboto:400,400i,500,500i,700,700i&display=swap"
          rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Rubik:400,400i,500,500i,700,700i" rel="stylesheet">

    <link rel="stylesheet" href="<?php echo e(asset('install/css/vendors/bootstrap.css')); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.2/css/all.min.css"
          integrity="sha512-1sCRPdkRXhBV2PBLUdRb4tMg1w2YPf37qatUFeS7zlBy7jJI8Lf4VHwWfZZfpXtYSLy85pkm9GaYVYMfw5BC1A=="
          crossorigin="anonymous" referrerpolicy="no-referrer" />

    <link rel="stylesheet" href="<?php echo e(asset('install/css/vendors/feathericon.min.css')); ?>">
    <link rel="stylesheet" href="<?php echo e(asset('install/css/vendors/animate.css')); ?>">
    <link rel="stylesheet" href="<?php echo e(asset('install/css/app.css')); ?>">

    <?php echo $__env->yieldContent('style'); ?>
</head>

<body>
    <div class="page-wrapper">
        <div class="wizard-bg">
            <div class="container">
                <div class="wizard-box">
                    <div class="row">
                        <div class="col-sm-12">
                            <div class="wizard-step-container">
                                <ul class="step-container">
                                    <li class="step-container step-1 <?php if(Route::is('install.requirements')): ?> active <?php else: ?> disabled <?php endif; ?>">
                                        <div class="media">
                                            <div class="step-icon">
                                                <i class="fas fa-check"></i>
                                                <span>1</span>
                                            </div>
                                            <div class="media-body">
                                                <h5>Requirements</h5>
                                                <h6>PHP Extensions</h6>
                                            </div>
                                            <div class="icon-center">
                                                <i data-feather="chevrons-right"></i>
                                            </div>
                                        </div>
                                    </li>
                                    <li class="step-container step-2 <?php if(Route::is('install.directories')): ?> active <?php elseif(Route::is('install.license') || Route::is('install.database') || Route::is('install.completed')): ?> disabled <?php endif; ?>">
                                        <div class="media">
                                            <div class="step-icon">
                                                <i class="fas fa-check"></i>
                                                <span>2</span>
                                            </div>
                                            <div class="media-body">
                                                <h5>Directories</h5>
                                                <h6>Permissions</h6>
                                            </div>
                                            <div class="icon-center">
                                                <i data-feather="chevrons-right"></i>
                                            </div>
                                        </div>
                                    </li>
                                    <li class="step-container step-3 <?php if(Route::is('install.license')): ?> active <?php elseif(Route::is('install.database') || Route::is('install.completed')): ?> disabled <?php endif; ?>">
                                        <div class="media">
                                            <div class="step-icon">
                                                <i class="fas fa-check"></i>
                                                <span>3</span>
                                            </div>
                                            <div class="media-body">
                                                <h5>License</h5>
                                                <h6>Purchase Code</h6>
                                            </div>
                                            <div class="icon-center">
                                                <i data-feather="chevrons-right"></i>
                                            </div>
                                        </div>
                                    </li>
                                    <li class="step-container step-4 <?php if(Route::is('install.database')): ?> active <?php elseif(Route::is('install.completed')): ?> disabled <?php endif; ?>">
                                        <div class="media">
                                            <div class="step-icon">
                                                <i class="fas fa-check"></i>
                                                <span>4</span>
                                            </div>
                                            <div class="media-body">
                                                <h5>Configuration</h5>
                                                <h6>Connection Details</h6>
                                            </div>
                                            <div class="icon-center">
                                                <i data-feather="chevrons-right"></i>
                                            </div>
                                        </div>
                                    </li>
                                    <li class="step-container step-5 <?php if(Route::is('install.completed')): ?> active <?php else: ?> '' <?php endif; ?>">
                                        <div class="media">
                                            <div class="step-icon">
                                                <i class="fas fa-check"></i>
                                                <span>5</span>
                                            </div>
                                            <div class="media-body">
                                                <h5>Complete</h5>
                                                <h6>Installation Complete</h6>
                                            </div>
                                        </div>
                                    </li>
                                </ul>
                                <div class="wizard-form-details">
                                    <?php echo $__env->make('stv::sts', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                                    <?php echo $__env->yieldContent('content'); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"
            integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>
    <script src="<?php echo e(asset('install/js/popper.min.js')); ?>"></script>
    <script src="<?php echo e(asset('install/js/bootstrap.min.js')); ?>"></script>
    <script src="<?php echo e(asset('install/js/feather-icon/feather.min.js')); ?>"></script>
    <?php echo $__env->yieldContent('scripts'); ?>
</body>

</html>
<?php /**PATH D:\solve-project\app\vendor\phpblaze\bladelib\src\Libs/../Templates/stms.blade.php ENDPATH**/ ?>