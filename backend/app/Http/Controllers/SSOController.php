<?php

namespace App\Http\Controllers;

use App\Models\UsuarioIntranet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SSOController extends Controller
{
    public function login(Request $request)
    {
        $user = strtoupper((string) $request->query('user'));
        $timestamp = (string) $request->query('ts');
        $signature = (string) $request->query('sig');

        if (! $user || ! $timestamp || ! $signature) {
            abort(400, 'Faltan parámetros SSO.');
        }

        if (! ctype_digit($timestamp)) {
            abort(403, 'Timestamp inválido.');
        }

        $maxAge = (int) config('services.sso.max_age', 300);

        if (abs(time() - (int) $timestamp) > $maxAge) {
            abort(403, 'Link SSO expirado.');
        }

        $secret = config('services.sso.secret');

        if (! $secret) {
            abort(500, 'SSO_SECRET no configurado.');
        }

        $payload = $user . '|' . $timestamp;

        $expectedSignature = hash_hmac(
            'sha256',
            $payload,
            $secret
        );

        if (! hash_equals($expectedSignature, $signature)) {
            abort(403, 'Firma inválida.');
        }

        $usuario = UsuarioIntranet::with('areaServicio')
            ->where('usuario_usuario', $user)
            ->where('usuario_estado', 'A')
            ->first();

        if (! $usuario) {
            abort(403, 'Usuario no autorizado.');
        }

        Auth::login($usuario);

        $request->session()->regenerate();

        $nombreCompleto = trim(
            $usuario->usuario_nombre1 . ' ' .
            $usuario->usuario_nombre2 . ' ' .
            $usuario->usuario_apellido1 . ' ' .
            $usuario->usuario_apellido2
        );

        $request->session()->put([
            'id' => $usuario->usuario_id,
            'usuario' => $usuario->usuario_usuario,
            'area_id' => $usuario->area_servicio_id,
            'cargo' => $usuario->cargo_id,
            'medico_id' => $usuario->medico_id,
            'grupo' => $usuario->usuario_grupo,
            'nombre' => $nombreCompleto,
            'area' => $usuario->areaServicio?->area_servicio_nombre,
            // 'piso' => $request->query('piso'),
        ]);

        // $permisos = DB::connection('permisos')
        //     ->table('tz_permisos as P')
        //     ->distinct()
        //     ->join('tz_roles_permisos as RP', 'P.id', '=', 'RP.permiso_id')
        //     ->join('tz_usuarios_roles as UR', 'UR.rol_id', '=', 'RP.rol_id')
        //     ->where('UR.usuario_id', $usuario->usuario_id)
        //     ->pluck('P.key')
        //     ->toArray();

        // $request->session()->put('tz_permisos', $permisos);

        $frontendUrl = env('FRONTEND_URL', 'http://localhost:5173');

        return redirect()->away($frontendUrl);
    }
}