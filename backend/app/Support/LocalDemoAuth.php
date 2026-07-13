<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LocalDemoAuth
{
    public static function enabled(): bool
    {
        return app()->environment('local') || (bool) config('services.sso.allow_local_demo', false);
    }

    public static function sessionData(string $user): array
    {
        $user = strtoupper(trim($user)) ?: 'DEMO';
        $usuarioId = abs(crc32($user)) % 100000 + 1;
        $esAdmin = in_array($user, ['ADMIN', 'DEMO', 'DEMOADMIN'], true) || str_contains($user, 'ADMIN');

        $areaServicio = [
            'area_servicio_id' => 1,
            'area_servicio_nombre' => 'Desarrollo local',
        ];

        $jefaturas = $esAdmin
            ? [[
                'jefe_area' => 1,
                'area_servicio' => $areaServicio,
            ]]
            : [];

        $nombreCompleto = $esAdmin ? 'Usuario Demo Administrador' : 'Usuario Demo';

        return [
            'usuario_id' => $usuarioId,
            'usuario_usuario' => $user,
            'usuario_nombre1' => $esAdmin ? 'Usuario' : 'Demo',
            'usuario_nombre2' => $esAdmin ? 'Administrador' : '',
            'usuario_apellido1' => 'Local',
            'usuario_apellido2' => '',
            'usuario_grupo' => $esAdmin ? 'ADMIN' : 'USER',
            'cargo_id' => null,
            'medico_id' => null,
            'area_servicio_id' => 1,
            'areaServicio' => $areaServicio,
            'area_servicio' => $areaServicio,
            'area' => $areaServicio['area_servicio_nombre'],
            'nombre' => $nombreCompleto,
            'permisos' => $esAdmin ? ['blog.supervisar_area'] : [],
            'jefaturas' => $jefaturas,
            'jefaturasActivas' => $jefaturas,
            'areas_supervisadas' => $esAdmin ? [1] : [],
            'tipo_usuario' => $esAdmin ? 'admin' : 'employee',
            'es_admin' => $esAdmin,
            'es_admin_area' => $esAdmin,
            'puede_supervisar_area' => $esAdmin,
        ];
    }

    public static function makeUser(string $user): LocalDemoUser
    {
        return new LocalDemoUser(self::sessionData($user));
    }

    public static function apply(Request $request, string $user): LocalDemoUser
    {
        $sessionData = self::sessionData($user);
        $demoUser = self::makeUser($user);

        if ($request->hasSession()) {
            $request->session()->regenerate();
            $request->session()->put(array_merge($sessionData, [
                'demo_user' => $sessionData,
                'demo_auth' => true,
            ]));
        }

        Auth::shouldUse('web');
        Auth::setUser($demoUser);
        $request->setUserResolver(fn () => $demoUser);

        return $demoUser;
    }

    public static function resolveFromSession(Request $request): ?LocalDemoUser
    {
        if (! $request->hasSession()) {
            return null;
        }

        $sessionData = $request->session()->get('demo_user');

        if (! is_array($sessionData)) {
            return null;
        }

        $demoUser = new LocalDemoUser($sessionData);

        Auth::shouldUse('web');
        Auth::setUser($demoUser);
        $request->setUserResolver(fn () => $demoUser);

        return $demoUser;
    }
}