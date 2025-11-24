<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\Api\AddressController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UploadController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\OrderController;

Route::get('/', function () {
    return ['ok' => true, 'message' => 'Welcome to the API'];
});

Route::prefix('api/v1')->group(function () {
    // Auth
    Route::get('login/{provider}/redirect', [AuthController::class, 'redirect'])->name('login.provider.redirect');
    Route::get('login/{provider}/callback', [AuthController::class, 'callback'])->middleware(['web'])->name('login.provider.callback');
    Route::post('login', [AuthController::class, 'login'])->middleware(['throttle:login'])->name('login');
    Route::post('register', [AuthController::class, 'register'])->name('register');
    Route::post('forgot-password', [AuthController::class, 'sendResetPasswordLink'])->middleware('throttle:5,1')->name('password.email');
    Route::post('reset-password', [AuthController::class, 'resetPassword'])->name('password.store');
    Route::post('verification-notification', [AuthController::class, 'verificationNotification'])->middleware('throttle:verification-notification')->name('verification.send');
    Route::get('verify-email/{ulid}/{hash}', [AuthController::class, 'verifyEmail'])->middleware(['signed', 'throttle:6,1'])->name('verification.verify');

    // Products
    Route::get('products', [ProductController::class, 'index']);
//    Route::get('products/{slug}', [ProductController::class, 'show']);

    // Categories
    Route::get('categories', [CategoryController::class, 'index']);
    Route::get('categories/tree', [CategoryController::class, 'tree']);
    Route::get('categories/{slug}', [CategoryController::class, 'show']);
    Route::get('categories/{slug}/products', [CategoryController::class, 'products']);

    Route::get('orders', [OrderController::class, 'index']);
    Route::get('products/statistics', [ProductController::class, 'statistics']);
    Route::get('products/{id}', [ProductController::class, 'show']);


    Route::middleware(["auth:" . config('auth.defaults.guard')])->group(function () {
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');
        Route::post('devices/disconnect', [AuthController::class, 'deviceDisconnect'])->name('devices.disconnect');
        Route::get('devices', [AuthController::class, 'devices'])->name('devices');
        Route::get('user', [AuthController::class, 'user'])->name('user');

        Route::post('account/update', [AccountController::class, 'update'])->name('account.update');
        Route::post('account/password', [AccountController::class, 'password'])->name('account.password');

        Route::middleware(['throttle:uploads'])->group(function () {
            Route::post('upload', [UploadController::class, 'image'])->name('upload.image');
        });

        Route::apiResource('users', UserController::class);
        Route::patch('users/{ulid}/status', [UserController::class, 'updateStatus']);
        Route::get('user-statuses', [UserController::class, 'getStatuses']);

        Route::apiResource('roles', RoleController::class)->only(['index', 'store', 'destroy']);

        // Products management
        Route::post('products', [ProductController::class, 'store']);
        Route::put('products/{product}', [ProductController::class, 'update']);
        Route::delete('products/{product}', [ProductController::class, 'destroy']);

        // Categories management
        Route::post('categories', [CategoryController::class, 'store']);
        Route::put('categories/{category}', [CategoryController::class, 'update']);
        Route::delete('categories/{category}', [CategoryController::class, 'destroy']);
        Route::post('categories/reorder', [CategoryController::class, 'reorder']);

        // Orders
//        Route::get('orders', [OrderController::class, 'index']);
        Route::get('orders/{order}', [OrderController::class, 'show']);
        Route::post('orders', [OrderController::class, 'store']);
        Route::patch('orders/{order}/status', [OrderController::class, 'updateStatus']);
        Route::post('orders/{order}/cancel', [OrderController::class, 'cancel']);

        // Customers
        Route::get('customers', [CustomerController::class, 'index']);
        Route::get('customers/{customer}', [CustomerController::class, 'show']);
        Route::put('customers/{customer}', [CustomerController::class, 'update']);

        // Customer addresses
        Route::post('customers/{customer}/addresses', [AddressController::class, 'store']);
        Route::put('addresses/{address}', [AddressController::class, 'update']);
        Route::delete('addresses/{address}', [AddressController::class, 'destroy']);
        Route::patch('addresses/{address}/set-default', [AddressController::class, 'setDefault']);
    });
});


