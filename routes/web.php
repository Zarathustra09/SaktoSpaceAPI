<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Web\CategoryController;
use App\Http\Controllers\Web\PaymentController;
use App\Http\Controllers\Web\ProductController;
use App\Http\Controllers\Web\UserController;
use App\Http\Controllers\Web\OrderController;
use App\Http\Controllers\Admin\UserAdminController;
use App\Http\Controllers\Web\PromotionalAdvertisementController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('auth.login');
});

Route::auth(['register' => false]);

Route::middleware(['auth', 'role:Admin'])->group(function () {
    Route::get('/home', [HomeController::class, 'index'])->name('home');

    Route::resource('categories', CategoryController::class)->except(['create', 'edit']);
    Route::resource('products', ProductController::class)->except(['create', 'edit']);
    Route::get('/payments/export', [PaymentController::class, 'export'])->name('payments.export');
    Route::resource('payments', PaymentController::class)->only(['index', 'show', 'update']);
    Route::resource('users', UserController::class)->only(['index', 'show']);
    Route::resource('promotional-advertisements', PromotionalAdvertisementController::class)->except(['create', 'edit']);

    // Order Management Routes
    Route::resource('orders', OrderController::class)->only(['index', 'show']);
    Route::patch('orders/{order}/status', [OrderController::class, 'updateStatus'])->name('orders.update-status');
    Route::post('orders/bulk-update-status', [OrderController::class, 'bulkUpdateStatus'])->name('orders.bulk-update-status');
    Route::get('orders/stats', [OrderController::class, 'getStats'])->name('orders.stats');

    Route::get('/profile', [ProfileController::class, 'index'])->name('profile');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/upload-image', [ProfileController::class, 'uploadProfileImage'])->name('profile.uploadImage');
    Route::post('/profile/reset-image', [ProfileController::class, 'resetProfileImage'])->name('profile.resetImage');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Admin user access management (moved off /users prefix to avoid conflicts)
    Route::prefix('admin/users')->name('admin.users.')->group(function () {
        Route::get('/', [UserAdminController::class, 'index'])->name('index');
        Route::post('{user}/promote', [UserAdminController::class, 'promote'])->name('promote');
        Route::post('{user}/demote', [UserAdminController::class, 'demote'])->name('demote');
    });
});

Route::get('/password-updated', function () {
    return view('auth.password-updated');
})->name('password.updated');
