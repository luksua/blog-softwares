<?php

use App\Support\LocalDemoAuth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SSOController;

Route::get('/sso-login', [SSOController::class, 'login'])->name('sso.login');

Route::get('/dev-login/{user?}', function (Request $request, string $user = 'ADMIN') {
    if (! app()->environment('local')) {
        abort(404);
    }

    if (! LocalDemoAuth::enabled()) {
        abort(404);
    }

    LocalDemoAuth::apply($request, $user);

    return redirect()->away(config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:5173')));
})->where('user', '[A-Za-z0-9_\-]+');

Route::get('/dev-sso/{user}', function ($user) {
    if (! app()->environment('local')) {
        abort(404);
    }

    if (LocalDemoAuth::enabled()) {
        LocalDemoAuth::apply(request(), $user);

        return redirect()->away(config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:5173')));
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
