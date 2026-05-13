<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

use App\Models\UsuarioIntranet;

class SSOController extends Controller
{
    public function login(Request $request)
    {
        $user = $request->query('user');

        $timestamp = $request->query('ts');

        $signature = $request->query('sig');

        // Validar expiración
        // if (time() - $timestamp > 300) {

        //     abort(403, 'Token expirado');
        // }

        // Validar firma
        $secret = env('SSO_SECRET');

        $expected = hash_hmac(
            'sha256',
            $user . $timestamp,
            $secret
        );

        if (!hash_equals($expected, $signature)) {

            abort(403, 'Firma inválida');
        }

        // Buscar usuario
        $usuario = UsuarioIntranet::where(
            'usuario_usuario',
            $user
        )
            ->where('usuario_estado', 'A')
            ->first();

        if (!$usuario) {

            abort(404, 'Usuario no encontrado');
        }

        // Login Laravel
        Auth::login($usuario);

        // Regenerar sesión
        $request->session()->regenerate();

        return redirect('/dashboard');
    }
}