<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Models\UpdateBlog;
use App\Models\UsuarioIntranet;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;

class BlogDashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $usuario = $request->user();
        $alcance = $this->resolverAlcance($usuario);

        // Solo bloqueamos si de verdad no hay nada que mostrarle: no es
        // admin, no supervisa áreas, y ni siquiera tiene área propia asignada.
        if ($alcance['tipo'] === 'area_usuario' && empty($alcance['areas'])) {
            abort(403, 'No tienes permiso para consultar el dashboard.');
        }

        $base = UpdateBlog::withoutGlobalScopes();

        // Evita incluir eliminados si el modelo utiliza SoftDeletes.
        if (Schema::hasColumn('actualizaciones_blog', 'deleted_at')) {
            $base->whereNull('deleted_at');
        }

        $this->aplicarAlcance($base, $alcance);

        $estadoSql = $this->estadoNormalizadoSql();

        $conteosEstado = (clone $base)
            ->selectRaw("{$estadoSql} AS estado, COUNT(*) AS total")
            ->groupByRaw($estadoSql)
            ->get()
            ->mapWithKeys(fn($item) => [
                (string) $item->estado => (int) $item->total,
            ]);

        $ordenEstados = collect([
            'publicado',
            'revision',
            'borrador',
            'programado',
            'inactivo',
        ]);

        $porEstado = $ordenEstados
            ->map(fn(string $estado) => [
                'estado' => $estado,
                'total' => (int) $conteosEstado->get($estado, 0),
            ])
            ->concat(
                $conteosEstado
                    ->except($ordenEstados->all())
                    ->map(fn($total, $estado) => [
                        'estado' => (string) $estado,
                        'total' => (int) $total,
                    ])
                    ->values()
            )
            ->values();
        $ahora = now();
        $limiteProgramados = now()->addHours(24);

        $programadosRaw = (clone $base)
            ->whereRaw("({$estadoSql}) = ?", ['programado'])
            ->whereNotNull('actualizacion_fecha_publicacion')
            ->whereBetween(
                'actualizacion_fecha_publicacion',
                [$ahora, $limiteProgramados]
            )
            ->orderBy('actualizacion_fecha_publicacion')
            ->limit(30)
            ->get([
                'id',
                'actualizacion_titulo',
                'actualizacion_fecha_publicacion',
                'actualizacion_area_servicio_id',
            ]);

        $areasProgramados = Area::query()
            ->select('area_servicio_id', 'area_servicio_nombre')
            ->whereIn(
                'area_servicio_id',
                $programadosRaw
                    ->pluck('actualizacion_area_servicio_id')
                    ->filter()
                    ->unique()
                    ->values()
            )
            ->get()
            ->keyBy('area_servicio_id');

        $programadosProximos = $programadosRaw
            ->map(function (UpdateBlog $registro) use ($areasProgramados) {
                $area = $registro->actualizacion_area_servicio_id
                    ? $areasProgramados->get(
                        $registro->actualizacion_area_servicio_id
                    )
                    : null;

                return [
                    'id' => (int) $registro->id,
                    'titulo' => $registro->actualizacion_titulo
                        ?: 'Sin título',
                    'fecha_publicacion' => $registro
                        ->actualizacion_fecha_publicacion
                        ?->toIso8601String(),
                    'area' => $area?->area_servicio_nombre
                        ?: 'Sin área',
                ];
            })
            ->values();

        $porAreaAgrupado = (clone $base)
            ->selectRaw("
                    actualizacion_area_servicio_id as area_servicio_id,
                    COUNT(*) as total,
                    SUM(CASE WHEN actualizacion_estado = 'revision' THEN 1 ELSE 0 END) as pendientes_revision
                ")
            ->groupBy('actualizacion_area_servicio_id')
            ->orderByDesc('total')
            ->limit(12)
            ->get();

        $areas = Area::select('area_servicio_id', 'area_servicio_nombre')
            ->whereIn('area_servicio_id', $porAreaAgrupado->pluck('area_servicio_id')->filter()->values())
            ->get()
            ->keyBy('area_servicio_id');

        $porArea = $porAreaAgrupado
            ->map(fn($item) => [
                'area_servicio_id' => $item->area_servicio_id ? (int) $item->area_servicio_id : null,
                'area' => $item->area_servicio_id && isset($areas[$item->area_servicio_id])
                    ? $areas[$item->area_servicio_id]->area_servicio_nombre
                    : 'Sin área',
                'total' => (int) $item->total,
                'pendientes_revision' => (int) $item->pendientes_revision,
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

        $tablaActualizaciones = (new UpdateBlog())->getTable();
        $tablaUsuarios = (new UsuarioIntranet())->getTable();
        $tablaAreas = (new Area())->getTable();

        $areasAutores = $this->normalizarAreasAlcance($alcance);

        $queryAreasMencionadas = DB::table(
            "{$tablaActualizaciones} as actualizacion"
        )
            ->join(
                "{$tablaUsuarios} as autor",
                'autor.usuario_id',
                '=',
                'actualizacion.actualizacion_usuario_id_autor'
            )
            ->leftJoin(
                "{$tablaAreas} as area_destino",
                'area_destino.area_servicio_id',
                '=',
                'actualizacion.actualizacion_area_servicio_id'
            );

        /*
 * Para un administrador se consideran todos los autores.
 * Para supervisores o usuarios normales, solamente los autores
 * pertenecientes a las áreas incluidas en su alcance.
 */
        if ($alcance['tipo'] !== 'global') {
            if (empty($areasAutores)) {
                $queryAreasMencionadas->whereRaw('1 = 0');
            } else {
                $queryAreasMencionadas->whereIn(
                    'autor.area_servicio_id',
                    $areasAutores
                );
            }
        }

        /*
 * Excluye registros eliminados.
 */
        if (
            Schema::hasColumn(
                $tablaActualizaciones,
                'deleted_at'
            )
        ) {
            $queryAreasMencionadas->whereNull(
                'actualizacion.deleted_at'
            );
        }

        $areasMasMencionadas = $queryAreasMencionadas
            ->selectRaw('
        actualizacion.actualizacion_area_servicio_id
            AS area_servicio_id,

        COALESCE(
            area_destino.area_servicio_nombre,
            "Sin área"
        ) AS area,

        COUNT(*) AS total,

        COUNT(
            DISTINCT actualizacion.actualizacion_usuario_id_autor
        ) AS autores
    ')
            ->groupBy(
                'actualizacion.actualizacion_area_servicio_id',
                'area_destino.area_servicio_nombre'
            )
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->map(fn($item) => [
                'area_servicio_id' => $item->area_servicio_id
                    ? (int) $item->area_servicio_id
                    : null,

                'area' => (string) $item->area,

                'total' => (int) $item->total,

                'autores' => (int) $item->autores,
            ])
            ->values();

        $registrosMasLeidos = collect();

        $empleadosMasActivos = collect();

        if (Schema::hasTable('actualizaciones_blog_visualizaciones')) {
            $visualizacionesBase = DB::table(
                'actualizaciones_blog_visualizaciones as visualizacion'
            )
                ->join(
                    'actualizaciones_blog as actualizacion',
                    'actualizacion.id',
                    '=',
                    'visualizacion.actualizacion_id'
                );

            $this->aplicarAlcanceVisualizaciones(
                $visualizacionesBase,
                $alcance
            );

            $empleadosAgrupados = (clone $visualizacionesBase)
                ->selectRaw('
            visualizacion.usuario_id,
            COUNT(*) AS total_visualizaciones,
            COUNT(DISTINCT visualizacion.actualizacion_id) AS registros_vistos,
            MAX(visualizacion.visualizado_at) AS ultima_visualizacion
        ')
                ->groupBy('visualizacion.usuario_id')
                ->orderByDesc('total_visualizaciones')
                ->limit(10)
                ->get();

            $usuariosVisualizaciones = UsuarioIntranet::query()
                ->select(
                    'usuario_id',
                    'usuario_usuario',
                    'usuario_nombre1',
                    'usuario_nombre2',
                    'usuario_apellido1',
                    'usuario_apellido2'
                )
                ->whereIn(
                    'usuario_id',
                    $empleadosAgrupados
                        ->pluck('usuario_id')
                        ->filter()
                        ->unique()
                        ->values()
                )
                ->get()
                ->keyBy('usuario_id');

            $empleadosMasActivos = $empleadosAgrupados
                ->map(function ($item) use ($usuariosVisualizaciones) {
                    $usuario = $usuariosVisualizaciones->get(
                        $item->usuario_id
                    );

                    return [
                        'usuario_id' => (int) $item->usuario_id,
                        'usuario' => $this->nombreUsuario($usuario),
                        'total_visualizaciones' =>
                        (int) $item->total_visualizaciones,
                        'registros_vistos' =>
                        (int) $item->registros_vistos,
                        'ultima_visualizacion' =>
                        $item->ultima_visualizacion,
                    ];
                })
                ->values();

            // $historialVisualizaciones = $historialRaw
            //     ->map(function ($item) use ($usuariosVisualizaciones) {
            //         $usuario = $usuariosVisualizaciones->get(
            //             $item->usuario_id
            //         );

            //         return [
            //             'id' => (int) $item->id,
            //             'usuario_id' => (int) $item->usuario_id,
            //             'usuario' => $this->nombreUsuario($usuario),
            //             'actualizacion_id' =>
            //             (int) $item->actualizacion_id,
            //             'titulo' => $item->titulo ?: 'Sin título',
            //             'visualizado_at' => $item->visualizado_at,
            //         ];
            //     })
            //     ->values();
        }

        if (Schema::hasColumn('actualizaciones_blog', 'actualizacion_lecturas')) {
            $queryMasLeidos = (clone $base)
                ->select([
                    'id',
                    'actualizacion_titulo',
                    'actualizacion_area_servicio_id',
                    'actualizacion_estado',
                    'actualizacion_lecturas',
                ])
                ->whereRaw("({$estadoSql}) = ?", ['publicado'])
                ->where('actualizacion_lecturas', '>', 0)
                ->orderByDesc('actualizacion_lecturas')
                ->latest('actualizacion_fecha_publicacion')
                ->limit(10);

            $registrosMasLeidos = $queryMasLeidos->get()->map(fn(UpdateBlog $item) => [
                'id' => $item->id,
                'titulo' => $item->actualizacion_titulo,
                'lecturas' => (int) ($item->actualizacion_lecturas ?? 0),
                'area_servicio_id' => $item->actualizacion_area_servicio_id ? (int) $item->actualizacion_area_servicio_id : null,
            ]);
        }

        $totalRegistros = (clone $base)->count();

        $totalPublicados = (int) $conteosEstado->get('publicado', 0);
        $totalRevision = (int) $conteosEstado->get('revision', 0);
        $totalBorradores = (int) $conteosEstado->get('borrador', 0);
        $totalProgramados = (int) $conteosEstado->get('programado', 0);
        $totalInactivos = (int) $conteosEstado->get('inactivo', 0);

        return response()->json([
            'data' => [
                'alcance' => $alcance,

                'resumen' => [
                    'total_registros' => $totalRegistros,
                    'publicados' => $totalPublicados,
                    'revision' => $totalRevision,
                    'borradores' => $totalBorradores,
                    'programados' => $totalProgramados,
                    'inactivos' => $totalInactivos,
                ],

                'registros_mas_leidos_area' =>
                $registrosMasLeidos->values(),

                'usuarios_mas_registros' =>
                $usuariosMasRegistros,

                'empleados_mas_activos' =>
                $empleadosMasActivos,

                'areas_mas_mencionadas' =>
                $areasMasMencionadas,

                'registros_por_estado' =>
                $porEstado,

                'registros_por_area' =>
                $porArea,

                'programados_proximos' =>
                $programadosProximos,

                'lecturas_disponibles' =>
                Schema::hasColumn(
                    'actualizaciones_blog',
                    'actualizacion_lecturas'
                ),
            ],
        ]);
    }

    private function estadoNormalizadoSql(): string
    {
        $estado = "LOWER(TRIM(COALESCE(actualizacion_estado, 'sin_estado')))";

        $normalizado = "
        CASE
            WHEN {$estado} IN ('publicado', 'publicada') THEN 'publicado'
            WHEN {$estado} IN ('programado', 'programada') THEN 'programado'
            WHEN {$estado} IN (
                'revision',
                'revisión',
                'en revision',
                'en revisión',
                'en_revision'
            ) THEN 'revision'
            WHEN {$estado} = 'borrador' THEN 'borrador'
            WHEN {$estado} IN ('inactivo', 'inactiva') THEN 'inactivo'
            ELSE {$estado}
        END
    ";

        if (
            Schema::hasColumn(
                'actualizaciones_blog',
                'actualizacion_activo'
            )
        ) {
            return "
            CASE
                WHEN {$estado} IN ('programado', 'programada')
                    THEN 'programado'

                WHEN actualizacion_activo = 0
                    THEN 'inactivo'

                ELSE ({$normalizado})
            END
        ";
        }

        return $normalizado;
    }

    private function aplicarAlcanceVisualizaciones(
        QueryBuilder $query,
        array $alcance
    ): void {
        if ($alcance['tipo'] === 'global') {
            return;
        }

        $areas = $this->normalizarAreasAlcance($alcance);

        if (empty($areas)) {
            $query->whereRaw('1 = 0');
            return;
        }

        $tablaUsuarios = (new UsuarioIntranet())->getTable();

        $query->where(function (QueryBuilder $scope) use (
            $areas,
            $tablaUsuarios
        ) {
            $scope->whereIn(
                'actualizacion.actualizacion_area_servicio_id',
                $areas
            );

            $scope->orWhereExists(function ($subquery) use (
                $areas,
                $tablaUsuarios
            ) {
                $subquery
                    ->selectRaw('1')
                    ->from("{$tablaUsuarios} as autor")
                    ->whereColumn(
                        'autor.usuario_id',
                        'actualizacion.actualizacion_usuario_id_autor'
                    )
                    ->whereIn(
                        'autor.area_servicio_id',
                        $areas
                    );
            });
        });
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

        if (!empty($areasSupervisadas)) {
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

    private function aplicarAlcance(
        Builder $query,
        array $alcance
    ): void {
        if ($alcance['tipo'] === 'global') {
            return;
        }

        $areas = $this->normalizarAreasAlcance($alcance);

        if (empty($areas)) {
            $query->whereRaw('1 = 0');
            return;
        }

        $tablaActualizaciones = (new UpdateBlog())->getTable();
        $tablaUsuarios = (new UsuarioIntranet())->getTable();

        $query->where(function (Builder $scope) use (
            $areas,
            $tablaActualizaciones,
            $tablaUsuarios
        ) {
            /*
         * Primera condición:
         * el registro está asignado a un área supervisada.
         */
            $scope->whereIn(
                "{$tablaActualizaciones}.actualizacion_area_servicio_id",
                $areas
            );

            /*
         * Segunda condición:
         * el autor pertenece a un área supervisada.
         */
            $scope->orWhereExists(function ($subquery) use (
                $areas,
                $tablaActualizaciones,
                $tablaUsuarios
            ) {
                $subquery
                    ->selectRaw('1')
                    ->from("{$tablaUsuarios} as autor")
                    ->whereColumn(
                        'autor.usuario_id',
                        "{$tablaActualizaciones}.actualizacion_usuario_id_autor"
                    )
                    ->whereIn(
                        'autor.area_servicio_id',
                        $areas
                    );
            });
        });
    }

    private function normalizarAreasAlcance(
        array $alcance
    ): array {
        return collect($alcance['areas'] ?? [])
            ->filter(
                fn($areaId) =>
                $areaId !== null &&
                    $areaId !== ''
            )
            ->map(fn($areaId) => (int) $areaId)
            ->unique()
            ->values()
            ->all();
    }

    private function esAdmin($usuario): bool
    {
        return strtoupper((string) $usuario->usuario_grupo) === 'ADMIN';
    }

    private function areasSupervisadas($usuario): array
    {
        if (!method_exists($usuario, 'areasSupervisadas')) {
            return [];
        }

        $areas = $usuario->areasSupervisadas();

        if ($areas instanceof \Illuminate\Support\Collection) {
            $areas = $areas->all();
        }

        return collect($areas)
            ->filter(fn($areaId) => $areaId !== null && $areaId !== '')
            ->map(fn($areaId) => (int) $areaId)
            ->unique()
            ->values()
            ->all();
    }

    private function nombreUsuario(?UsuarioIntranet $usuario): string
    {
        if (!$usuario) {
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
