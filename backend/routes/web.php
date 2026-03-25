<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\UpdateBlogAdminController;
use App\Http\Controllers\Employee\UpdateBlogController;

Route::post('/admin/actualizaciones', [UpdateBlogAdminController::class, 'store']);
Route::post('/admin/actualizaciones', [UpdateBlogAdminController::class, 'index']);
Route::post('/employee/actualizaciones', [UpdateBlogController::class, 'index']);

