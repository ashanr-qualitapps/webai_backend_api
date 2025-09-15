<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SwaggerController;

Route::get('/', function () {
    return view('welcome');
});

// Swagger Documentation Routes
Route::get('api/documentation', [SwaggerController::class, 'index'])->name('swagger.index');
Route::get('api/documentation/json', [SwaggerController::class, 'json'])->name('swagger.json');
