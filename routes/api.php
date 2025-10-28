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
Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);
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
    Route::post('/payment/direct', [App\Http\Controllers\API\PaymentController::class, 'processDirectPayment']);


    //profile routes
    Route::get('profile', [App\Http\Controllers\API\ProfileController::class, 'show']);
    Route::put('profile', [App\Http\Controllers\API\ProfileController::class, 'update']);
    Route::post('profile/image', [App\Http\Controllers\API\ProfileController::class, 'uploadProfileImage']);
    Route::delete('profile/image', [App\Http\Controllers\API\ProfileController::class, 'resetProfileImage']);
    Route::delete('profile', [App\Http\Controllers\API\ProfileController::class, 'destroy']);

     Route::get('orders', [OrdersController::class, 'index']);
     Route::get('orders/stats', [OrdersController::class, 'getOrderStats']);
     Route::get('orders/{orderId}', [OrdersController::class, 'show']);

    Route::apiResource('ratings', App\Http\Controllers\API\RatingController::class);
});



Route::middleware('auth:sanctum')->group(function () {
    Route::post('/device-token', function (Request $request) {
        $data = $request->validate([
            'token' => 'required|string',
            'device_type' => 'nullable|string|in:android,ios,web',
            'timestamp' => 'nullable|date'
        ]);

        $user = $request->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $timestamp = isset($data['timestamp']) ? \Carbon\Carbon::parse($data['timestamp']) : now();
        $user->addDeviceToken($data['token'], $data['device_type'] ?? null, $timestamp);

        return response()->json(['success' => true, 'message' => 'Device token registered']);
    });

    Route::delete('/device-token', function (Request $request) {
        $data = $request->validate([
            'token' => 'required|string'
        ]);

        $user = $request->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $user->removeDeviceToken($data['token']);

        return response()->json(['success' => true, 'message' => 'Device token removed']);
    });

    Route::post('/device-token/refresh', function (Request $request) {
        $user = $request->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $staleTokensRemoved = $user->removeStaleTokens();

        return response()->json([
            'success' => true,
            'message' => 'Token refresh completed',
            'stale_tokens_removed' => $staleTokensRemoved
        ]);
    });


});
