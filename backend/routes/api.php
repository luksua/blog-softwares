<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\UpdateBlogAdminController;
use App\Http\Controllers\Employee\UpdateBlogController;

// (GET)
Route::get('/admin/actualizaciones', [UpdateBlogAdminController::class, 'index']);
Route::get('/employee/actualizaciones', [UpdateBlogController::class, 'index']);

// (POST)
Route::post('/admin/actualizaciones', [UpdateBlogAdminController::class, 'store']);
Route::get('/admin/area-servicio', [UpdateBlogAdminController::class, 'getAreas']);