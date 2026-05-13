<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UpdateBlogController;

Route::middleware('auth:sanctum')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Usuario autenticado
    |--------------------------------------------------------------------------
    */

    Route::get('/me', function (Request $request) {
        $usuario = $request->user();

        return response()->json([
            'usuario' => [
                'usuario_id' => $usuario->usuario_id,
                'usuario_usuario' => $usuario->usuario_usuario,
                'usuario_nombre1' => $usuario->usuario_nombre1,
                'usuario_nombre2' => $usuario->usuario_nombre2,
                'usuario_apellido1' => $usuario->usuario_apellido1,
                'usuario_apellido2' => $usuario->usuario_apellido2,
                'usuario_grupo' => $usuario->usuario_grupo,
                'cargo_id' => $usuario->cargo_id,
                'medico_id' => $usuario->medico_id,
                'area_servicio_id' => $usuario->area_servicio_id,
                'area' => $request->session()->get('area'),
                'nombre' => $request->session()->get('nombre'),
                'permisos' => $request->session()->get('tz_permisos', []),
            ],
        ]);
    });

    Route::get('/me', function (Request $request) {
    $usuario = $request->user();

    $permisos = session('tz_permisos', []);

    $esAdmin = strtoupper((string) $usuario->usuario_grupo) === 'ADMIN';

    $puedeSupervisarArea =
        $esAdmin ||
        in_array('blog.supervisar_area', $permisos, true);

    return response()->json([
        'usuario' => $usuario->loadMissing('areaServicio'),
        'permisos' => $permisos,
        'es_admin' => $esAdmin,
        'puede_supervisar_area' => $puedeSupervisarArea,
    ]);
});

    Route::post('/logout', function (Request $request) {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'success' => true,
        ]);
    });

    /*
    |--------------------------------------------------------------------------
    | Catálogos
    |--------------------------------------------------------------------------
    */

    Route::get('/area-servicio', [UpdateBlogController::class, 'getAreas']);
    Route::get('/categorias', [UpdateBlogController::class, 'getCategorias']);
    Route::get('/estados-actualizacion', [UpdateBlogController::class, 'getStatus']);

    /*
    |--------------------------------------------------------------------------
    | Imágenes
    |--------------------------------------------------------------------------
    */

    Route::post('/subir-imagen-blog', [UpdateBlogController::class, 'subirImagenEditor']);
    Route::post('/subir-imagen-destacada', [UpdateBlogController::class, 'subirImagenPortada']);

    /*
    |--------------------------------------------------------------------------
    | Actualizaciones
    |--------------------------------------------------------------------------
    */

    Route::get('/actualizaciones', [UpdateBlogController::class, 'index']);
    Route::post('/actualizaciones', [UpdateBlogController::class, 'store']);

    Route::get('/actualizaciones/{id}', [UpdateBlogController::class, 'show'])
        ->whereNumber('id');

    Route::put('/actualizaciones/{id}', [UpdateBlogController::class, 'update'])
        ->whereNumber('id');

    Route::patch('/actualizaciones/{id}', [UpdateBlogController::class, 'update'])
        ->whereNumber('id');

    Route::post('/actualizaciones/{id}/estado', [UpdateBlogController::class, 'cambiarEstado'])
        ->whereNumber('id');

    Route::post('/actualizaciones/{id}/activar', [UpdateBlogController::class, 'activar'])
        ->whereNumber('id');

    Route::post('/actualizaciones/{id}/inactivar', [UpdateBlogController::class, 'inactivar'])
        ->whereNumber('id');
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