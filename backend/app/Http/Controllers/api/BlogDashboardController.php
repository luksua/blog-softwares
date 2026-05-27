<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Models\UpdateBlog;
use App\Models\UsuarioIntranet;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class BlogDashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $usuario = $request->user();

        if (! $this->esAdmin($usuario) && empty($this->areasSupervisadas($usuario))) {
            abort(403, 'No tienes permiso para consultar el dashboard.');
        }

        $alcance = $this->resolverAlcance($usuario);

        $base = UpdateBlog::query();
        $this->aplicarAlcance($base, $alcance);

        $porEstado = (clone $base)
            ->selectRaw('actualizacion_estado as estado, COUNT(*) as total')
            ->groupBy('actualizacion_estado')
            ->orderBy('actualizacion_estado')
            ->get()
            ->map(fn ($item) => [
                'estado' => $item->estado ?: 'sin_estado',
                'total' => (int) $item->total,
            ])
            ->values();

        $porAreaAgrupado = (clone $base)
            ->selectRaw('actualizacion_area_servicio_id as area_servicio_id, COUNT(*) as total')
            ->groupBy('actualizacion_area_servicio_id')
            ->orderByDesc('total')
            ->limit(12)
            ->get();

        $areas = Area::select('area_servicio_id', 'area_servicio_nombre')
            ->whereIn('area_servicio_id', $porAreaAgrupado->pluck('area_servicio_id')->filter()->values())
            ->get()
            ->keyBy('area_servicio_id');

        $porArea = $porAreaAgrupado
            ->map(fn ($item) => [
                'area_servicio_id' => $item->area_servicio_id ? (int) $item->area_servicio_id : null,
                'area' => $item->area_servicio_id && isset($areas[$item->area_servicio_id])
                    ? $areas[$item->area_servicio_id]->area_servicio_nombre
                    : 'Sin área',
                'total' => (int) $item->total,
            ])
            ->values();

        $usuariosAgrupados = (clone $base)
            ->selectRaw('actualizacion_usuario_id_autor as usuario_id, COUNT(*) as total')
            ->whereNotNull('actualizacion_usuario_id_autor')
            ->groupBy('actualizacion_usuario_id_autor')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        $usuarios = UsuarioIntranet::select(
            'usuario_id',
            'usuario_usuario',
            'usuario_nombre1',
            'usuario_nombre2',
            'usuario_apellido1',
            'usuario_apellido2'
        )
            ->whereIn('usuario_id', $usuariosAgrupados->pluck('usuario_id')->filter()->values())
            ->get()
            ->keyBy('usuario_id');

        $usuariosMasRegistros = $usuariosAgrupados
            ->map(function ($item) use ($usuarios) {
                $usuario = $usuarios[$item->usuario_id] ?? null;

                return [
                    'usuario_id' => (int) $item->usuario_id,
                    'usuario' => $this->nombreUsuario($usuario),
                    'total' => (int) $item->total,
                ];
            })
            ->values();

        $registrosMasLeidos = collect();

        if (Schema::hasColumn('actualizaciones_blog', 'actualizacion_lecturas')) {
            $queryMasLeidos = (clone $base)
                ->select([
                    'id',
                    'actualizacion_titulo',
                    'actualizacion_area_servicio_id',
                    'actualizacion_estado',
                    'actualizacion_lecturas',
                ])
                ->where('actualizacion_estado', 'publicado')
                ->where('actualizacion_lecturas', '>', 0)
                ->orderByDesc('actualizacion_lecturas')
                ->latest('actualizacion_fecha_publicacion')
                ->limit(10);

            $registrosMasLeidos = $queryMasLeidos->get()->map(fn (UpdateBlog $item) => [
                'id' => $item->id,
                'titulo' => $item->actualizacion_titulo,
                'lecturas' => (int) ($item->actualizacion_lecturas ?? 0),
                'area_servicio_id' => $item->actualizacion_area_servicio_id ? (int) $item->actualizacion_area_servicio_id : null,
            ]);
        }

        $totalRegistros = (clone $base)->count();
        $totalPublicados = (clone $base)->where('actualizacion_estado', 'publicado')->count();
        $totalRevision = (clone $base)->where('actualizacion_estado', 'revision')->count();
        $totalBorradores = (clone $base)->where('actualizacion_estado', 'borrador')->count();

        return response()->json([
            'data' => [
                'alcance' => $alcance,
                'resumen' => [
                    'total_registros' => $totalRegistros,
                    'publicados' => $totalPublicados,
                    'revision' => $totalRevision,
                    'borradores' => $totalBorradores,
                ],
                'registros_mas_leidos_area' => $registrosMasLeidos->values(),
                'usuarios_mas_registros' => $usuariosMasRegistros,
                'registros_por_estado' => $porEstado,
                'registros_por_area' => $porArea,
                'lecturas_disponibles' => Schema::hasColumn('actualizaciones_blog', 'actualizacion_lecturas'),
            ],
        ]);
    }

    private function resolverAlcance($usuario): array
    {
        if ($this->esAdmin($usuario)) {
            return [
                'tipo' => 'global',
                'areas' => [],
                'descripcion' => 'Todos los registros',
            ];
        }

        $areasSupervisadas = $this->areasSupervisadas($usuario);

        if (! empty($areasSupervisadas)) {
            return [
                'tipo' => 'areas_supervisadas',
                'areas' => $areasSupervisadas,
                'descripcion' => 'Áreas supervisadas',
            ];
        }

        $areaUsuario = $usuario->area_servicio_id ? [(int) $usuario->area_servicio_id] : [];

        return [
            'tipo' => 'area_usuario',
            'areas' => $areaUsuario,
            'descripcion' => 'Área del usuario',
        ];
    }

    private function aplicarAlcance(Builder $query, array $alcance): void
    {
        if ($alcance['tipo'] === 'global') {
            return;
        }

        $areas = collect($alcance['areas'] ?? [])
            ->filter(fn ($areaId) => $areaId !== null && $areaId !== '')
            ->map(fn ($areaId) => (int) $areaId)
            ->unique()
            ->values()
            ->all();

        if (empty($areas)) {
            $query->whereRaw('1 = 0');
            return;
        }

        $query->whereIn('actualizacion_area_servicio_id', $areas);
    }

    private function esAdmin($usuario): bool
    {
        return strtoupper((string) $usuario->usuario_grupo) === 'ADMIN';
    }

    private function areasSupervisadas($usuario): array
    {
        if (! method_exists($usuario, 'areasSupervisadas')) {
            return [];
        }

        $areas = $usuario->areasSupervisadas();

        if ($areas instanceof \Illuminate\Support\Collection) {
            $areas = $areas->all();
        }

        return collect($areas)
            ->filter(fn ($areaId) => $areaId !== null && $areaId !== '')
            ->map(fn ($areaId) => (int) $areaId)
            ->unique()
            ->values()
            ->all();
    }

    private function nombreUsuario(?UsuarioIntranet $usuario): string
    {
        if (! $usuario) {
            return 'Usuario sin información';
        }

        $nombre = collect([
            $usuario->usuario_nombre1,
            $usuario->usuario_nombre2,
            $usuario->usuario_apellido1,
            $usuario->usuario_apellido2,
        ])
            ->filter()
            ->implode(' ');

        return trim($nombre) ?: ($usuario->usuario_usuario ?: 'Usuario sin nombre');
    }
}
