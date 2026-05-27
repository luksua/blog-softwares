<?php

namespace App\Http\Middleware;

use App\Models\UsuarioIntranet;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class QxrDevUser
{
    public function handle(Request $request, Closure $next): Response
    {
        // qxr: bypass temporal para desarrollo local sin BD de intranet actualizada.
        if (! app()->environment('local')) {
            return $next($request);
        }

        if (! $request->hasSession()) {
            abort(401, 'Unauthenticated.');
        }

        $data = $request->session()->get('qxr_dev_user');

        if (! is_array($data)) {
            abort(401, 'Unauthenticated.');
        }

        $usuario = new UsuarioIntranet($data);
        $usuario->exists = true;
        $usuario->setAttribute($usuario->getKeyName(), $data['usuario_id'] ?? 1);

        Auth::guard('web')->setUser($usuario);
        $request->setUserResolver(fn () => $usuario);

        return $next($request);
    }
}
