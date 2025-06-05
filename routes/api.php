<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\CategoryController;
use App\Http\Controllers\API\ProductController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:api');


Route::post('/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->get('/user', [AuthController::class, 'getUser']);



// Category routes
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('categories', CategoryController::class);
    Route::get('categories/type/{type}', [CategoryController::class, 'getByType']);

    // Product routes
    Route::apiResource('products', ProductController::class);
    Route::post('products/{id}/ar-model', [ProductController::class, 'uploadARModel']);
    Route::get('ar-products', [ProductController::class, 'getARProducts']);
    Route::get('products/category/{categoryId}', [ProductController::class, 'getByCategory']);
});
