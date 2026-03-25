<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\UpdateBlogController;

Route::post('/admin/actualizaciones', [UpdateBlogController::class, 'store']);
Route::get('/', function () {
    return view('welcome');
});
