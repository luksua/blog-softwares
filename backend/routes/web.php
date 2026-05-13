<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SSOController;
use App\Http\Controllers\Admin\UpdateBlogAdminController;
use App\Http\Controllers\Employee\UpdateBlogController;
use App\Http\Controllers\SimuladorLoginController;
use App\Models\UsuarioIntranet;

Route::get('/test-legacy', function () {

    return UsuarioIntranet::first();
});

// Route::post('/admin/actualizaciones', [UpdateBlogAdminController::class, 'store']);
// Route::post('/admin/actualizaciones', [UpdateBlogAdminController::class, 'index']);
// Route::post('/employee/actualizaciones', [UpdateBlogController::class, 'index']);

// Route::prefix('api')->group(function () {
//     Route::post('/simulador-login', [SimuladorLoginController::class, 'login']);
//     Route::get('/me', [SimuladorLoginController::class, 'me']);
//     Route::post('/logout', [SimuladorLoginController::class, 'logout']);
// });

Route::get('/sso-login', [SSOController::class, 'login']);

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () {

        return redirect('http://localhost:5173');
    });
});