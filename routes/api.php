<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:api');


Route::post('/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->get('/user', [AuthController::class, 'getUser']);



// Category routes
Route::middleware('auth:sanctum')->apiResource('categories', CategoryController::class);
Route::middleware('auth:sanctum')->get('categories/type/{type}', [CategoryController::class, 'getByType']);

// Product routes
Route::middleware('auth:sanctum')->get('products', ProductController::class);
Route::middleware('auth:sanctum')->post('products/{id}/ar-model', [ProductController::class, 'uploadARModel']);
Route::middleware('auth:sanctum')->get('ar-products', [ProductController::class, 'getARProducts']);
Route::middleware('auth:sanctum')->get('products/category/{categoryId}', [ProductController::class, 'getByCategory']);
