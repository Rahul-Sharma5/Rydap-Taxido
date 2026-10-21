<?php

return array (
  'name' => 'RazorPay',
  'slug' => 'razorpay',
  'image' => 'modules/razorpay/images/logo.png',
  'title' => 'RazorPay',
  'processing_fee' => '1',
  'subscription' => 0,
  'configs' => 
  array (
    'razorpay_key' => env('RAZORPAY_KEY', ''),
    'razorpay_secret' => env('RAZORPAY_SECRET', ''),
    'razorpay_webhook_secret_key' => env('RAZORPAY_WEBHOOK_SECRET_KEY', ''),
    'razorpay_mode' => 'sandbox',
  ),
  'fields' => 
  array (
    'title' => 
    array (
      'type' => 'text',
      'label' => 'Label',
    ),
    'processing_fee' => 
    array (
      'type' => 'number',
      'label' => 'Processing Fee',
    ),
    'razorpay_key' => 
    array (
      'type' => 'password',
      'label' => 'RazorPay Key',
    ),
    'razorpay_secret' => 
    array (
      'type' => 'password',
      'label' => 'RazorPay Secret',
    ),
    'razorpay_webhook_secret_key' => 
    array (
      'type' => 'password',
      'label' => 'Webhook Secret Key',
    ),
    'subscription' => 
    array (
      'type' => 'checkbox',
      'label' => 'Subscription',
    ),
  ),
);
