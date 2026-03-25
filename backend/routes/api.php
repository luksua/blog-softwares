<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\UpdateBlogController;

// Ruta para obtener la lista (GET)
Route::get('/admin/actualizaciones', [UpdateBlogController::class, 'index']);

// Ruta que ya tenías para guardar (POST)
Route::post('/admin/actualizaciones', [UpdateBlogController::class, 'store']);