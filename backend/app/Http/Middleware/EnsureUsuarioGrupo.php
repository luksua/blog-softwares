<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restringe el acceso a una ruta según el grupo del usuario autenticado.
 *
 * Uso (alias 'usuario.grupo' ya registrado en bootstrap/app.php):
 *   Route::middleware('usuario.grupo:admin')->group(...);
 *   Route::middleware('usuario.grupo:employee')->group(...);
 *
 * Nota: actualmente ningún grupo de rutas la aplica todavía; la autorización
 * de admin/supervisor se resuelve hoy dentro de UpdateBlogController
 * (esAdmin/esJefeArea). Esta clase queda lista para cuando se quiera mover
 * esa validación al nivel de ruta.
 */
class EnsureUsuarioGrupo
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $usuario = $request->user();

        if (! $usuario) {
            abort(401, 'Unauthenticated.');
        }

        $grupo = strtoupper((string) $usuario->usuario_grupo);

        foreach ($roles as $role) {
            $role = strtolower($role);

            if ($role === 'admin' && $grupo === 'ADMIN') {
                return $next($request);
            }

            if ($role === 'employee' && $grupo !== 'ADMIN') {
                return $next($request);
            }
        }

        abort(403, 'No tienes permiso para acceder a esta ruta.');
    }
}
