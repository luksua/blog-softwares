<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UpdateBlogController;
use App\Http\Controllers\Api\BlogDashboardController;
use App\Http\Controllers\Api\BookmarkController;
use App\Http\Controllers\Api\BlogNotificationController;
use App\Http\Controllers\Api\ObservacionesPendientesController;
use App\Http\Controllers\Api\RegistrarVisualizacionController;
use App\Http\Controllers\Api\MiHistorialLecturasController;

Route::middleware('auth:sanctum')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Usuario autenticado
    |--------------------------------------------------------------------------
    */

    Route::get('/me', function (Request $request) {
        $usuario = $request->user()->loadMissing([
            'areaServicio',
            'jefaturasActivas.areaServicio',
        ]);

        $permisos = session('tz_permisos', []);

        if (!$permisos && isset($usuario->permisos)) {
            $permisos = $usuario->permisos;
        }

        $esAdminGlobal = $usuario->esAdmin();
        $areasSupervisadas = $usuario->areasSupervisadas();
        $esAdminArea = $areasSupervisadas->isNotEmpty();
        $puedeSupervisarArea = $esAdminGlobal || $esAdminArea;

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
                'area_servicio' => $usuario->areaServicio,
                'area' => $request->hasSession() ? $request->session()->get('area') : ($usuario->area ?? null),
                'nombre' => $request->hasSession() ? $request->session()->get('nombre') : ($usuario->nombre ?? null),
                'permisos' => $permisos,
                'jefaturas' => $usuario->jefaturasActivas,
                'areas_supervisadas' => $areasSupervisadas,
                'tipo_usuario' => $puedeSupervisarArea ? 'admin' : 'employee',
            ],
            'permisos' => $permisos,
            'tipo_usuario' => $puedeSupervisarArea ? 'admin' : 'employee',
            'es_admin' => $esAdminGlobal,
            'es_admin_area' => $esAdminArea,
            'puede_supervisar_area' => $puedeSupervisarArea,
            'areas_supervisadas' => $areasSupervisadas,
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
    | Notificaciones/Observaciones
    |--------------------------------------------------------------------------
    */

    Route::post('/actualizaciones/{actualizacion}/visualizacion', RegistrarVisualizacionController::class);
    Route::get('/notificaciones', [BlogNotificationController::class, 'index']);
    Route::get('/observaciones-pendientes', [ObservacionesPendientesController::class, 'index']);
    Route::get('/observaciones-pendientes/contador', [ObservacionesPendientesController::class, 'contador']);
    Route::get('/notificaciones/contador', [BlogNotificationController::class, 'contador']);
    Route::post('/notificaciones/{id}/leida', [BlogNotificationController::class, 'marcarLeida'])
        ->whereNumber('id');
    Route::post('/notificaciones/marcar-todas-leidas', [BlogNotificationController::class, 'marcarTodasLeidas']);

    /*
    |--------------------------------------------------------------------------
    | Guardados / bookmarks
    |--------------------------------------------------------------------------
    */

    Route::get('/bookmarks', [BookmarkController::class, 'index']);
    Route::get('/bookmarks/ids', [BookmarkController::class, 'ids']);
    Route::post('/bookmarks/{actualizacion}', [BookmarkController::class, 'store'])
        ->whereNumber('actualizacion');
    Route::delete('/bookmarks/{actualizacion}', [BookmarkController::class, 'destroy'])
        ->whereNumber('actualizacion');
    Route::delete('/bookmarks', [BookmarkController::class, 'destroyAll']);

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

    Route::post('/actualizaciones/{id}/revision', [UpdateBlogController::class, 'marcarRevision'])
        ->whereNumber('id');

    Route::post('/actualizaciones/{id}/activar', [UpdateBlogController::class, 'activar'])
        ->whereNumber('id');

    Route::post('/actualizaciones/{id}/inactivar', [UpdateBlogController::class, 'inactivar'])
        ->whereNumber('id');

    /*
    |--------------------------------------------------------------------------
    | Dashboard
    |--------------------------------------------------------------------------
    */

    Route::get('/blog-dashboard', BlogDashboardController::class);
    Route::get('/mi-historial-lecturas', MiHistorialLecturasController::class)->middleware('auth:sanctum');
});
