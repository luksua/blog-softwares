<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SSOController;

Route::get('/sso-login', [SSOController::class, 'login'])->name('sso.login');

Route::get('/qxr-dev-login', function () {
    // qxr: login temporal solo para entorno local, borrar cuando vuelva la BD de intranet.
    if (! app()->environment('local')) {
        abort(404);
    }

    $usuario = [
        'usuario_id' => 1,
        'usuario_usuario' => 'QXRLOCAL',
        'usuario_nombre1' => 'Usuario',
        'usuario_nombre2' => null,
        'usuario_apellido1' => 'Local',
        'usuario_apellido2' => null,
        'usuario_grupo' => 'ADMIN',
        'cargo_id' => 1,
        'medico_id' => null,
        'area_servicio_id' => 1,
    ];

    request()->session()->regenerate();
    request()->session()->put('qxr_dev_user', $usuario);
    request()->session()->put([
        'id' => $usuario['usuario_id'],
        'usuario' => $usuario['usuario_usuario'],
        'area_id' => $usuario['area_servicio_id'],
        'cargo' => $usuario['cargo_id'],
        'medico_id' => $usuario['medico_id'],
        'grupo' => $usuario['usuario_grupo'],
        'nombre' => 'Usuario Local',
        'area' => 'Area local qxr',
        'tz_permisos' => ['blog.supervisar_area'],
    ]);

    return redirect()->away(env('FRONTEND_URL', 'http://localhost:5173'));
})->name('qxr.dev-login');

Route::get('/dev-sso/{user}', function ($user) {
    if (! app()->environment('local')) {
        abort(404);
    }

    $user = strtoupper($user);
    $ts = time();
    $secret = config('services.sso.secret');

    if (! $secret) {
        abort(500, 'SSO_SECRET no configurado.');
    }

    $sig = hash_hmac('sha256', $user . '|' . $ts, $secret);

    return redirect("/sso-login?user={$user}&ts={$ts}&sig={$sig}");
});

Route::middleware('auth')->get('/dashboard', function () {
    return redirect()->away(config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:5173')));
});
