<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUsuarioGrupo
{
    // public function handle(Request $request, Closure $next, string ...$roles): Response
    // {
    //     $usuario = $request->user();

    //     if (! $usuario) {
    //         abort(401, 'Unauthenticated.');
    //     }

    //     $grupo = strtoupper((string) $usuario->usuario_grupo);

    //     foreach ($roles as $role) {
    //         $role = strtolower($role);

    //         if ($role === 'admin' && $grupo === 'ADMIN') {
    //             return $next($request);
    //         }

    //         if ($role === 'employee' && $grupo !== 'ADMIN') {
    //             return $next($request);
    //         }
    //     }

    //     abort(403, 'No tienes permiso para acceder a esta ruta.');
    // }
}
