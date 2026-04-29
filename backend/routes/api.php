<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\UpdateBlogAdminController;
use App\Http\Controllers\Employee\UpdateBlogController;

// (GET)
Route::get('/admin/actualizaciones', [UpdateBlogAdminController::class, 'index']);
Route::get('/employee/actualizaciones', [UpdateBlogController::class, 'index']);
Route::get('/admin/area-servicio', [UpdateBlogAdminController::class, 'getAreas']);
Route::get('/employee/area-servicio', [UpdateBlogController::class, 'getAreas']);
Route::get('/admin/estados-actualizacion', [UpdateBlogAdminController::class, 'getStatus']);
Route::get('/admin/actualizaciones/{id}', [UpdateBlogAdminController::class, 'show']);
Route::get('/employee/actualizaciones/{id}', [UpdateBlogController::class, 'show']);

// (POST)
Route::post('/admin/actualizaciones', [UpdateBlogAdminController::class, 'store']);
Route::post('/admin/actualizaciones/{id}', [UpdateBlogAdminController::class, 'update']);
Route::post('/admin/actualizaciones/{id}/inactivar', [UpdateBlogAdminController::class, 'inactivar']);
Route::post('/admin/actualizaciones/{id}/activar', [UpdateBlogAdminController::class, 'activar']);
Route::post('/admin/subir-imagen-blog', [UpdateBlogAdminController::class, 'subirImagenEditor']);
Route::post('/admin/subir-imagen-portada', [UpdateBlogAdminController::class, 'subirImagen']);