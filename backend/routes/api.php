<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\UpdateBlogAdminController;
use App\Http\Controllers\Employee\UpdateBlogController;

// Ruta para obtener la lista (GET)
Route::get('/admin/actualizaciones', [UpdateBlogAdminController::class, 'index']);
Route::get('/employee/actualizaciones', [UpdateBlogController::class, 'index']);

// Ruta que ya tenías para guardar (POST)
Route::post('/admin/actualizaciones', [UpdateBlogAdminController::class, 'store']);