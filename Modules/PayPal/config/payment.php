<?php

return array (
  'name' => 'PayPal',
  'slug' => 'paypal',
  'image' => 'modules/paypal/images/logo.png',
  'title' => 'PayPal',
  'processing_fee' => '1.3',
  'subscription' => '1',
  'configs' => 
  array (
    'paypal_client_id' => env('PAYPAL_CLIENT_ID', ''),
    'paypal_client_secret' => env('PAYPAL_CLIENT_SECRET', ''),
    'paypal_webhook_id' => env('PAYPAL_WEBHOOK_ID', ''),
    'paypal_mode' => '1',
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
    'paypal_mode' => 
    array (
      'type' => 'select',
      'label' => 'Select Mode',
      'options' => 
      array (
        1 => 'Sandbox',
        0 => 'Live',
      ),
    ),
    'paypal_client_id' => 
    array (
      'type' => 'password',
      'label' => 'Client ID',
    ),
    'paypal_client_secret' => 
    array (
      'type' => 'password',
      'label' => 'Client Secret',
    ),
    'paypal_webhook_id' => 
    array (
      'type' => 'password',
      'label' => 'Webhook ID',
    ),
    'subscription' => 
    array (
      'type' => 'checkbox',
      'label' => 'Subscription',
    ),
  ),
);
