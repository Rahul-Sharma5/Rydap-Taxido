<?php

// Public browser configuration; Admin credentials remain server-side.
return [
    'apiKey' => env('FIREBASE_WEB_API_KEY', 'AIzaSyAWUoFrJ_G3YSh0kDWMm685RaE61UPKOWM'),
    'authDomain' => env('FIREBASE_WEB_AUTH_DOMAIN', 'rydap-com.firebaseapp.com'),
    'projectId' => env('FIREBASE_WEB_PROJECT_ID', 'rydap-com'),
    'storageBucket' => env('FIREBASE_WEB_STORAGE_BUCKET', 'rydap-com.firebasestorage.app'),
    'messagingSenderId' => env('FIREBASE_WEB_MESSAGING_SENDER_ID', '185030684517'),
    'appId' => env('FIREBASE_WEB_APP_ID', '1:185030684517:web:01ab918a968bccb8ef3b8c'),
    'measurementId' => env('FIREBASE_WEB_MEASUREMENT_ID', 'G-BHHEG7NTHV'),
];
