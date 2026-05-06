<?php

namespace App\Http\Controllers;

use App\Models\UsuarioSistema;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SimuladorLoginController extends Controller
{
    // public function login(Request $request)
    // {
    //     $data = $request->validate([
    //         'user' => ['nullable', 'string', 'max:80'],
    //         'piso' => ['nullable', 'string', 'max:30'],
    //     ]);

    //     $user = trim($data['user'] ?? 'BMANDRES');
    //     $piso = trim($data['piso'] ?? 'q');

    //     $usuario = UsuarioSistema::query()
    //         ->select([
    //             'usuario.usuario_id',
    //             'usuario.usuario_usuario',
    //             'usuario.usuario_clave',
    //             'usuario.cargo_id',
    //             'usuario.usuario_nombre1',
    //             'usuario.usuario_nombre2',
    //             'usuario.usuario_apellido1',
    //             'usuario.usuario_apellido2',
    //             'usuario.medico_id',
    //             'usuario.area_servicio_id',
    //             'usuario.usuario_grupo',
    //             'area_servicio.area_servicio_nombre as area',
    //         ])
    //         ->join(
    //             'area_servicio',
    //             'usuario.area_servicio_id',
    //             '=',
    //             'area_servicio.area_servicio_id'
    //         )
    //         ->where('usuario.usuario_usuario', $user)
    //         ->where('usuario.usuario_estado', 'A')
    //         ->first();

    //     if (! $usuario) {
    //         return response()->json([
    //             'ok' => false,
    //             'message' => 'Sesión no iniciada. Usuario no encontrado o inactivo.',
    //         ], 404);
    //     }

    //     $permisos = DB::connection('permisos')
    //         ->table('tz_permisos as P')
    //         ->distinct()
    //         ->join('tz_roles_permisos as RP', 'P.id', '=', 'RP.permiso_id')
    //         ->join('tz_usuarios_roles as UR', 'UR.rol_id', '=', 'RP.rol_id')
    //         ->where('UR.usuario_id', $usuario->usuario_id)
    //         ->pluck('P.key')
    //         ->values()
    //         ->all();

    //     $nombre = collect([
    //         $usuario->usuario_nombre1,
    //         $usuario->usuario_nombre2,
    //         $usuario->usuario_apellido1,
    //         $usuario->usuario_apellido2,
    //     ])
    //         ->filter()
    //         ->implode(' ');

    //     $request->session()->regenerate();

    //     $request->session()->put([
    //         'id' => $usuario->usuario_id,
    //         'area' => $usuario->area,
    //         'piso' => $piso,
    //         'area_id' => $usuario->area_servicio_id,
    //         'cargo' => $usuario->cargo_id,
    //         'usuario' => $usuario->usuario_usuario,
    //         'medico_id' => $usuario->medico_id,
    //         'nombre' => $nombre,
    //         'grupo' => $usuario->usuario_grupo,
    //         'tz_permisos' => $permisos,
    //     ]);

    //     return response()->json([
    //         'ok' => true,
    //         'message' => 'Sesión iniciada correctamente.',
    //         'user' => [
    //             'id' => $usuario->usuario_id,
    //             'usuario' => $usuario->usuario_usuario,
    //             'nombre' => $nombre,
    //             'area' => $usuario->area,
    //             'area_id' => $usuario->area_servicio_id,
    //             'cargo' => $usuario->cargo_id,
    //             'medico_id' => $usuario->medico_id,
    //             'grupo' => $usuario->usuario_grupo,
    //             'piso' => $piso,
    //             'permisos' => $permisos,
    //         ],
    //     ]);
    // }

    // public function me(Request $request)
    // {
    //     if (! $request->session()->has('id')) {
    //         return response()->json([
    //             'authenticated' => false,
    //         ], 401);
    //     }

    //     return response()->json([
    //         'authenticated' => true,
    //         'user' => [
    //             'id' => $request->session()->get('id'),
    //             'usuario' => $request->session()->get('usuario'),
    //             'nombre' => $request->session()->get('nombre'),
    //             'area' => $request->session()->get('area'),
    //             'area_id' => $request->session()->get('area_id'),
    //             'cargo' => $request->session()->get('cargo'),
    //             'medico_id' => $request->session()->get('medico_id'),
    //             'grupo' => $request->session()->get('grupo'),
    //             'piso' => $request->session()->get('piso'),
    //             'permisos' => $request->session()->get('tz_permisos', []),
    //         ],
    //     ]);
    // }

    // public function logout(Request $request)
    // {
    //     $request->session()->invalidate();
    //     $request->session()->regenerateToken();

    //     return response()->json([
    //         'ok' => true,
    //         'message' => 'Sesión cerrada correctamente.',
    //     ]);
    // }
}