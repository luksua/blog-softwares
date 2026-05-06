<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\UpdateBlogAdminController;
use App\Http\Controllers\Employee\UpdateBlogController;
use App\Http\Controllers\SimuladorLoginController;

// Route::post('/admin/actualizaciones', [UpdateBlogAdminController::class, 'store']);
// Route::post('/admin/actualizaciones', [UpdateBlogAdminController::class, 'index']);
// Route::post('/employee/actualizaciones', [UpdateBlogController::class, 'index']);

// Route::prefix('api')->group(function () {
//     Route::post('/simulador-login', [SimuladorLoginController::class, 'login']);
//     Route::get('/me', [SimuladorLoginController::class, 'me']);
//     Route::post('/logout', [SimuladorLoginController::class, 'logout']);
// });