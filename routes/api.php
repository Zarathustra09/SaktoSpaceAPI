<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\CartController;
use App\Http\Controllers\API\CategoryController;
use App\Http\Controllers\API\OrdersController;
use App\Http\Controllers\API\ProductController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:api');


Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::middleware('auth:sanctum')->get('/user', [AuthController::class, 'getUser']);



// Category routes
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('categories', CategoryController::class);
    Route::get('categories/type/{type}', [CategoryController::class, 'getByType']);

    // Product routes
    Route::get('products/search', [ProductController::class, 'search']);
    Route::apiResource('products', ProductController::class);


    // Cart routes
        Route::get('cart', [CartController::class, 'index']);
        Route::post('cart/add', [CartController::class, 'addToCart']);
        Route::put('cart/{id}', [CartController::class, 'updateQuantity']);
        Route::delete('cart/{id}', [CartController::class, 'removeItem']);
        Route::delete('cart', [CartController::class, 'clearCart']);
        Route::get('cart/count', [CartController::class, 'getCartCount']);


    // Payment Routes
    //Process payment from cart
    Route::post('/payment/process', [App\Http\Controllers\API\PaymentController::class, 'processPayment']);
    Route::get('/payment/{paymentId}', [App\Http\Controllers\API\PaymentController::class, 'getPayment']);
    Route::get('/payments/history', [App\Http\Controllers\API\PaymentController::class, 'getPaymentHistory']);

    //profile routes
    Route::get('profile', [App\Http\Controllers\API\ProfileController::class, 'show']);
    Route::put('profile', [App\Http\Controllers\API\ProfileController::class, 'update']);
    Route::post('profile/image', [App\Http\Controllers\API\ProfileController::class, 'uploadProfileImage']);
    Route::delete('profile/image', [App\Http\Controllers\API\ProfileController::class, 'resetProfileImage']);
    Route::delete('profile', [App\Http\Controllers\API\ProfileController::class, 'destroy']);

     Route::get('orders', [OrdersController::class, 'index']);
     Route::get('orders/stats', [OrdersController::class, 'getOrderStats']);
     Route::get('orders/{orderId}', [OrdersController::class, 'show']);
});
