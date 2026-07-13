<?php

namespace App\Http\Middleware;

use App\Support\LocalDemoAuth;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUsuarioAutenticado
{
    public function handle(Request $request, Closure $next): Response
    {
        if (LocalDemoAuth::enabled()) {
            $user = (string) config('services.sso.local_demo_user', 'ADMIN');
            LocalDemoAuth::apply($request, $user);

            return $next($request);
        }

        if (Auth::guard('web')->check()) {
            $usuario = Auth::guard('web')->user();
            Auth::shouldUse('web');
            $request->setUserResolver(fn () => $usuario);

            return $next($request);
        }

        abort(401, 'Unauthenticated.');
    }
}