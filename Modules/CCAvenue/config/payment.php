<?php

return array (
  'name' => 'CCAvenue',
  'slug' => 'ccavenue',
  'image' => 'modules/ccavenue/images/logo.png',
  'title' => 'CCAvenue',
  'processing_fee' => '1.4',
  'subscription' => 0,
  'configs' => 
  array (
    'ccavenue_merchant_id' => env('CCAVENUE_MERCHANT_ID', ''),
    'ccavenue_access_code' => env('CCAVENUE_ACCESS_CODE', ''),
    'ccavenue_working_key' => env('CCAVENUE_WORKING_KEY', ''),
    'ccavenue_sandbox_mode' => '1',
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
    'ccavenue_sandbox_mode' => 
    array (
      'type' => 'select',
      'label' => 'Select Mode',
      'options' => 
      array (
        1 => 'Sandbox',
        0 => 'Live',
      ),
    ),
    'ccavenue_merchant_id' => 
    array (
      'type' => 'password',
      'label' => 'Merchant ID',
    ),
    'ccavenue_access_code' => 
    array (
      'type' => 'password',
      'label' => 'Access Code',
    ),
    'ccavenue_working_key' => 
    array (
      'type' => 'password',
      'label' => 'Working Key',
    ),
    'subscription' => 
    array (
      'type' => 'checkbox',
      'label' => 'Subscription',
    ),
  ),
);
