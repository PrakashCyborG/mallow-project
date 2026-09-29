<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\ProductController;

Route::post('/orders', [OrderController::class, 'store']);

Route::get('/customers/lookup', [CustomerController::class, 'lookup']);
Route::get('/customers/{email}/orders', [CustomerController::class, 'orderHistory']);

Route::get('/products/low-stock', [ProductController::class, 'lowStock']);
Route::get('/products', [ProductController::class, 'index']);