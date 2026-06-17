<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Models\Usuario; // Basado en la estructura de tu proyecto, este parece ser el modelo correcto.

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Aquí es donde puedes registrar las rutas web para tu aplicación.
|
*/

Route::get('/', function () {
    return ['Laravel' => app()->version()];
});

/**
 * RUTA DE AUTO-LOGIN PARA DESARROLLO LOCAL
 *
 * Accede a /dev-login en tu navegador para iniciar sesión automáticamente.
 */
Route::get('/dev-login', function () {
    // IMPORTANTE: Nos aseguramos de que esta ruta solo funcione en el entorno 'local'.
    if (!app()->environment('local')) {
        abort(403, 'Acción no permitida en este entorno.');
    }

    // Busca un usuario de desarrollo o lo crea si no existe.
    // Asume que tu modelo se llama 'Usuario' y la tabla 'usuarios'.
    $user = Usuario::firstOrCreate(
        ['usuario_usuario' => 'admin_dev'], // Campo único para identificar al usuario.
        [
            'usuario_grupo' => 'ADMIN',       // Rol para pasar las validaciones de administrador.
            'area_servicio_id' => 1,          // Un ID de área válido que exista en tu BD local.
            'usuario_nombre1' => 'Admin',
            'usuario_apellido1' => 'Local',
            'password' => bcrypt('password')  // Contraseña de relleno.
        ]
    );

    // Inicia la sesión para el usuario encontrado o creado.
    Auth::login($user);

    return "✅ ¡Sesión iniciada como 'admin_dev'! <a href='/'>Ir a la página principal del proyecto</a>.";
});