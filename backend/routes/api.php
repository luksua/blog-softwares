<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\UpdateBlogAdminController;
use App\Http\Controllers\Employee\UpdateBlogController;
use App\Http\Controllers\SimuladorLoginController;

// (GET)
Route::get('/admin/actualizaciones', [UpdateBlogAdminController::class, 'index']);
Route::get('/employee/actualizaciones', [UpdateBlogController::class, 'index']);
Route::get('/admin/area-servicio', [UpdateBlogAdminController::class, 'getAreas']);
Route::get('/employee/area-servicio', [UpdateBlogController::class, 'getAreas']);
Route::get('/admin/categorias', [UpdateBlogAdminController::class, 'getCategorias']);
Route::get('/employee/categorias', [UpdateBlogController::class, 'getCategorias']);
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

// Route::post('/simulador-login', [SimuladorLoginController::class, 'login']);

// Route::middleware('auth:sanctum')->group(function () {
//     Route::get('/me', [SimuladorLoginController::class, 'me']);
//     Route::post('/logout', [SimuladorLoginController::class, 'logout']);
// });



Route::middleware('auth')->get('/me', function (Request $request) {
    return response()->json([
        'usuario' => auth()->user()
    ]);
});

Route::post('/logout', function (Request $request) {

    Auth::logout();

    $request->session()->invalidate();

    $request->session()->regenerateToken();

    return response()->json([
        'success' => true
    ]);
});

// V2

// <?php

// use Illuminate\Support\Facades\Route;
// use App\Http\Controllers\Admin\UpdateBlogAdminController;
// use App\Http\Controllers\Employee\UpdateBlogController;
// use App\Http\Controllers\SimuladorLoginController;

// /*
// |--------------------------------------------------------------------------
// | Login simulado con sesión
// |--------------------------------------------------------------------------
// | Estas rutas siguen siendo:
// | /api/simulador-login
// | /api/me
// | /api/logout
// |
// | Pero ahora pasan por middleware web para que Laravel maneje sesión/cookies.
// */
// Route::middleware('web')->group(function () {
//     Route::post('/simulador-login', [SimuladorLoginController::class, 'login']);
//     Route::get('/me', [SimuladorLoginController::class, 'me']);
//     Route::post('/logout', [SimuladorLoginController::class, 'logout']);
// });

// /*
// |--------------------------------------------------------------------------
// | Rutas públicas o sin sesión
// |--------------------------------------------------------------------------
// */
// Route::get('/admin/actualizaciones', [UpdateBlogAdminController::class, 'index']);
// Route::get('/employee/actualizaciones', [UpdateBlogController::class, 'index']);
// Route::get('/admin/area-servicio', [UpdateBlogAdminController::class, 'getAreas']);
// Route::get('/employee/area-servicio', [UpdateBlogController::class, 'getAreas']);
// Route::get('/admin/categorias', [UpdateBlogAdminController::class, 'getCategorias']);
// Route::get('/employee/categorias', [UpdateBlogController::class, 'getCategorias']);
// Route::get('/admin/estados-actualizacion', [UpdateBlogAdminController::class, 'getStatus']);
// Route::get('/admin/actualizaciones/{id}', [UpdateBlogAdminController::class, 'show']);
// Route::get('/employee/actualizaciones/{id}', [UpdateBlogController::class, 'show']);

// /*
// |--------------------------------------------------------------------------
// | Rutas POST de admin
// |--------------------------------------------------------------------------
// */
// Route::post('/admin/actualizaciones', [UpdateBlogAdminController::class, 'store']);
// Route::post('/admin/actualizaciones/{id}', [UpdateBlogAdminController::class, 'update']);
// Route::post('/admin/actualizaciones/{id}/inactivar', [UpdateBlogAdminController::class, 'inactivar']);
// Route::post('/admin/actualizaciones/{id}/activar', [UpdateBlogAdminController::class, 'activar']);
// Route::post('/admin/subir-imagen-blog', [UpdateBlogAdminController::class, 'subirImagenEditor']);
// Route::post('/admin/subir-imagen-portada', [UpdateBlogAdminController::class, 'subirImagen']);