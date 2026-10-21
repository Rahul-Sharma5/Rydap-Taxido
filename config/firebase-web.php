<?php

// Public browser configuration; Admin credentials remain server-side.
return [
    'apiKey' => env('FIREBASE_WEB_API_KEY', ''),
    'authDomain' => env('FIREBASE_WEB_AUTH_DOMAIN', ''),
    'projectId' => env('FIREBASE_WEB_PROJECT_ID', ''),
    'storageBucket' => env('FIREBASE_WEB_STORAGE_BUCKET', ''),
    'messagingSenderId' => env('FIREBASE_WEB_MESSAGING_SENDER_ID', ''),
    'appId' => env('FIREBASE_WEB_APP_ID', ''),
    'measurementId' => env('FIREBASE_WEB_MEASUREMENT_ID', ''),
];
