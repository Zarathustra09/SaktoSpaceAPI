<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\Web\CategoryController;
use App\Http\Controllers\Web\PaymentController;
use App\Http\Controllers\Web\ProductController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('auth.login');
});

Route::middleware(['auth', 'role:Admin'])->group(function () {
    Route::get('/home', [HomeController::class, 'index'])->name('home');
    Route::resource('categories', CategoryController::class)->except(['create', 'edit']);
    Route::resource('products', ProductController::class)->except(['create', 'edit']);
    Route::resource('payments', PaymentController::class)->only(['index', 'show', 'update']);
});
