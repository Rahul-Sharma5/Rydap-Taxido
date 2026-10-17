<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
Route::group(['middleware' => ['localization' , 'maintenance', 'web'], 'namespace' => 'Front', 'as' => 'front.cab.'], function () {

  // Authentication
  Route::middleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class)->group(function () {
  Route::get('login', 'AuthController@login')->name('login.index');
  Route::get('forgot-password', 'PasswordController@form')->name('password.request');
  Route::post('forgot-password', 'PasswordController@send')->middleware('throttle:6,1')->name('password.email');
  Route::get('reset-password/{token}', 'PasswordController@form')->name('password.reset');
  Route::post('reset-password', 'PasswordController@reset')->middleware('throttle:6,1')->name('password.update');
  Route::post('login-with-credential', 'AuthController@loginWithCredential')->middleware('throttle:6,1')->name('login_with_credential');
  Route::post('login-with-password', 'AuthController@passwordLogin')->middleware('throttle:6,1')->name('password.login');
  Route::post('verify-firebase-otp', 'AuthController@verifyFirebaseOtp')->middleware('throttle:10,1')->name('verify_firebase_otp');
  Route::get('verify-otp', 'AuthController@verifyOtp')->name('verify_otp');
  Route::post('verify-otp', 'AuthController@verifyOtpStore')->middleware('throttle:10,1')->name('verify_otp.store');
  Route::get('register', 'AuthController@register')->name('register.index');
  Route::post('register', 'RegisterController@store')->middleware('throttle:6,1')->name('register.store');
  });

  // Ride Track
  Route::get('/ride/track/{track_id}', 'RideController@track')->name('ride.track');
  Route::get('/ride/track-data/{track_id}', 'RideController@trackData')->name('ride.track-data');

  Route::group(['middleware' => ['taxido.auth']], function() {

  // Account
  Route::get('dashboard', 'AccountController@dashboard')->name('dashboard.index');
  Route::get('notifications', 'AccountController@notifications')->name('notification.index');
  Route::post('update-profile', 'AccountController@updateProfile')->name('updateProfile');
  Route::post('update-password', 'AccountController@updatePassword')->name('updatePassword');
  Route::post('update-profile-image', 'AccountController@updateProfileImage')->name('updateProfileImage');

  // Locations
  Route::resource('location','LocationController', ['except' => ['show']]);

  // Notifications
  Route::post('notifications/mark-as-read', 'AccountController@markAsRead')->name('notifications.markAsRead');

  // Ride
  Route::get('history', 'RideController@history')->name('history.index');
  Route::get('create-ride', 'RideController@create')->name('ride.create');
  Route::get('ride/{ride_id}', 'RideController@show')->name('ride.show');
  Route::get('ride/invoice/{invoice_id}', 'RideController@getInvoice')->name('ride.invoice');

  // Ride Request
  Route::post('ride', 'RideRequestController@store')->name('ride-request.store');

  // Wallet
  Route::get('wallet', 'WalletController@index')->name('wallet.index');
  Route::post('wallet/top-up', 'WalletController@topUp')->name('wallet.top-up');
  Route::get('wallet/verify-top-up/{item_id}', 'WalletController@verifyPayment')->name('wallet.top-up.verify');

  // Chat
  Route::get('chat', 'ChatController@index')->name('chat.index');

  // Logout
  Route::post('/', 'AccountController@logout')->middleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class)->name('logout');

  });
});

