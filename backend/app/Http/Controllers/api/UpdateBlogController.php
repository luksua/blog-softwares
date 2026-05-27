<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ActualizacionResource;
use App\Models\Area;
use App\Models\BlogNotification;
use App\Models\Category;
use App\Models\JefeArea;
use App\Models\UpdateBlog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class UpdateBlogController extends Controller
{
    public function index(Request $request)
    {
        $usuario = $request->user();
        $vista = $request->input('vista', 'blog');

        $relaciones = [
            'areaServicio:area_servicio_id,area_servicio_nombre,area_servicio_correo',
            'categoria:categoria_actualizacion_id,categoria_actualizacion_nombre',
        ];

        // if (in_array($vista, ['mis-registros', 'supervision', 'todos'], true)) {
        //     $relaciones[] = 'usuarioAutor:usuario_id,usuario_usuario,usuario_login,usuario_nombre,usuario_nombre1,usuario_nombre2,usuario_apellido1,usuario_apellido2,area_servicio_id';
        //     $relaciones[] = 'ultimaRevisionObservacion:id,actualizacion_id,usuario_id_supervisor,observacion,estado_anterior,estado_nuevo,created_at';
        //     $relaciones[] = 'ultimaRevisionObservacion.supervisor:usuario_id,usuario_usuario,usuario_nombre,usuario_nombre1,usuario_nombre2,usuario_apellido1,usuario_apellido2';
        // }

        if (in_array($vista, ['mis-registros', 'supervision', 'todos'], true)) {
            $relaciones[] = 'usuarioAutor:usuario_id,usuario_usuario,usuario_nombre1,usuario_nombre2,usuario_apellido1,usuario_apellido2,area_servicio_id';
            $relaciones[] = 'ultimaRevisionObservacion:id,usuario_id_supervisor,observacion,estado_anterior,estado_nuevo,created_at';
            $relaciones[] = 'ultimaRevisionObservacion.supervisor:usuario_id,usuario_usuario,usuario_nombre1,usuario_nombre2,usuario_apellido1,usuario_apellido2';
        }

        $query = UpdateBlog::query()
            ->select([
                'id',
                'actualizacion_titulo',
                'actualizacion_version',
                'actualizacion_resumen',
                'actualizacion_imagen_destacada',
                'actualizacion_area_servicio_id',
                'actualizacion_usuario_id_autor',
                'actualizacion_estado',
                'actualizacion_fecha_creacion',
                'actualizacion_fecha_publicacion',
                'actualizacion_categoria_id',
            ])
            ->with($relaciones);

        /*
        |--------------------------------------------------------------------------
        | Vistas
        |--------------------------------------------------------------------------
        |
        | interes       => registros publicados de interés para el usuario.
        | mis-registros => registros creados por el usuario autenticado.
        | supervision   => registros del área del jefe.
        | todos         => solo ADMIN.
        |
        */

        if ($vista === 'blog') {
            /*
    |--------------------------------------------------------------------------
    | Blog general
    |--------------------------------------------------------------------------
    |
    | Todos los usuarios autenticados ven todos los registros publicados.
    | No se limita por área del usuario.
    |
    */

            $query->where('actualizacion_estado', 'publicado');
        } elseif ($vista === 'interes') {
            /*
    |--------------------------------------------------------------------------
    | Registros de interés
    |--------------------------------------------------------------------------
    |
    | Publicados del área del usuario.
    |
    */

            $query->where('actualizacion_estado', 'publicado');

            $query->where(function ($q) use ($usuario) {
                $q->where(
                    'actualizacion_area_servicio_id',
                    $usuario->area_servicio_id
                )
                    ->orWhereNull('actualizacion_area_servicio_id');
            });
        } elseif ($vista === 'mis-registros') {
            /*
    |--------------------------------------------------------------------------
    | Mis registros
    |--------------------------------------------------------------------------
    |
    | Registros creados por el usuario autenticado.
    | Aquí sí pueden salir borradores, revisión, publicados o inactivos.
    |
    */

            $query->where(
                'actualizacion_usuario_id_autor',
                $usuario->usuario_id
            );
        } elseif ($vista === 'supervision') {
            /*
    |--------------------------------------------------------------------------
    | Supervisión
    |--------------------------------------------------------------------------
    |
    | Jefe de área ve registros de su área.
    | ADMIN ve todos.
    |
    */

            if (! $this->esAdmin($usuario) && ! $this->esJefeArea($usuario)) {
                abort(403, 'No tienes permiso para supervisar registros.');
            }

            if (! $this->esAdmin($usuario)) {
                $areasSupervisadas = $this->areasSupervisadas($usuario);

                if (empty($areasSupervisadas)) {
                    abort(403, 'No tienes áreas asignadas para supervisar.');
                }

                $query->whereIn(
                    'actualizacion_area_servicio_id',
                    $areasSupervisadas
                );
            }
        } elseif ($vista === 'todos') {
            /*
    |--------------------------------------------------------------------------
    | Todos
    |--------------------------------------------------------------------------
    |
    | Solo ADMIN.
    | Ve todos los registros, sin importar estado ni área.
    |
    */

            if (! $this->esAdmin($usuario)) {
                abort(403, 'No tienes permiso para ver todos los registros.');
            }
        } else {
            abort(400, 'Vista no válida.');
        }

        /*
        |--------------------------------------------------------------------------
        | Filtros
        |--------------------------------------------------------------------------
        */

        if ($request->filled('busqueda')) {
            $busqueda = $request->input('busqueda');

            $query->where(function ($q) use ($busqueda) {
                $q->where('actualizacion_titulo', 'like', "%{$busqueda}%")
                    ->orWhere('actualizacion_resumen', 'like', "%{$busqueda}%")
                    ->orWhere('actualizacion_contenido', 'like', "%{$busqueda}%");
            });
        }

        if ($request->filled('estado')) {
            $query->where(
                'actualizacion_estado',
                $request->input('estado')
            );
        }

        if ($request->filled('area_servicio_id')) {
            $query->where(
                'actualizacion_area_servicio_id',
                (int) $request->input('area_servicio_id')
            );
        }

        if ($request->filled('actualizacion_categoria_id')) {
            $query->where(
                'actualizacion_categoria_id',
                (int) $request->input('actualizacion_categoria_id')
            );
        }

        if ($request->filled('fecha_desde')) {
            $query->whereDate(
                'actualizacion_fecha_publicacion',
                '>=',
                $request->input('fecha_desde')
            );
        }

        if ($request->filled('fecha_hasta')) {
            $query->whereDate(
                'actualizacion_fecha_publicacion',
                '<=',
                $request->input('fecha_hasta')
            );
        }

        match ($request->input('orden', 'recientes')) {
            'antiguos' => $query->oldest('actualizacion_fecha_publicacion'),
            'az' => $query->orderBy('actualizacion_titulo', 'asc'),
            'za' => $query->orderBy('actualizacion_titulo', 'desc'),
            default => $query->latest('actualizacion_fecha_publicacion'),
        };

        $perPage = (int) $request->input('per_page', 12);

        return ActualizacionResource::collection(
            $query->paginate($perPage)
        );
    }

    public function show(Request $request, int $id)
    {
        $usuario = $request->user();

        $actualizacion = UpdateBlog::with([
            'areaServicio',
            'categoria',
            'imagenes',
            'usuarioAutor',
            'ultimaRevisionObservacion.supervisor',
        ])->findOrFail($id);

        if (! $this->puedeVer($usuario, $actualizacion)) {
            abort(403, 'No tienes permiso para ver este registro.');
        }

        return new ActualizacionResource($actualizacion);
    }

    public function store(Request $request)
    {
        $usuario = $request->user();

        $datosValidados = $request->validate([
            'actualizacion_titulo' => ['required', 'string', 'max:255'],
            'actualizacion_version' => ['nullable', 'string', 'max:50'],
            'actualizacion_contenido' => ['required'],
            'actualizacion_resumen' => ['required', 'string'],
            'actualizacion_imagen_destacada' => ['nullable'],
            'actualizacion_categoria_id' => ['required', 'integer'],
            'actualizacion_estado' => [
                'nullable',
                Rule::in($this->estadosPermitidos()),
            ],
            'imagenes_quill' => ['nullable', 'string'],
        ]);

        try {
            DB::beginTransaction();

            $estado = $datosValidados['actualizacion_estado'] ?? 'borrador';

            $datosParaGuardar = [
                'actualizacion_titulo' => $datosValidados['actualizacion_titulo'],
                'actualizacion_version' => $datosValidados['actualizacion_version'] ?? '1',
                'actualizacion_contenido' => $this->normalizarContenido(
                    $datosValidados['actualizacion_contenido']
                ),
                'actualizacion_resumen' => $datosValidados['actualizacion_resumen'],
                'actualizacion_categoria_id' => $datosValidados['actualizacion_categoria_id'],

                /*
                |--------------------------------------------------------------------------
                | Datos seguros desde la sesión SSO
                |--------------------------------------------------------------------------
                |
                | No se reciben desde Vue para evitar que alguien manipule el autor
                | o el área desde el frontend.
                |
                */

                'actualizacion_usuario_id_autor' => $usuario->usuario_id,
                'actualizacion_area_servicio_id' => $usuario->area_servicio_id,

                'actualizacion_estado' => $estado,
                'actualizacion_fecha_creacion' => now(),
                'actualizacion_fecha_publicacion' => $estado === 'publicado'
                    ? now()
                    : null,
            ];

            if ($request->hasFile('actualizacion_imagen_destacada')) {
                $request->validate([
                    'actualizacion_imagen_destacada' => [
                        'image',
                        'mimes:jpeg,png,jpg,webp,gif',
                        'max:2048',
                    ],
                ]);

                $datosParaGuardar['actualizacion_imagen_destacada'] =
                    $request
                    ->file('actualizacion_imagen_destacada')
                    ->store('blog/portadas', 'public');
            } elseif ($request->filled('actualizacion_imagen_destacada')) {
                $datosParaGuardar['actualizacion_imagen_destacada'] =
                    $request->input('actualizacion_imagen_destacada');
            }

            $actualizacion = UpdateBlog::create($datosParaGuardar);

            if ($request->filled('imagenes_quill')) {
                $rutasImagenes = json_decode($request->input('imagenes_quill'), true);

                if (is_array($rutasImagenes)) {
                    foreach ($rutasImagenes as $ruta) {
                        $actualizacion->imagenes()->create([
                            'ruta_imagen' => $ruta,
                        ]);
                    }
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Registro guardado con éxito.',
                'data' => new ActualizacionResource(
                    $actualizacion->fresh([
                        'areaServicio',
                        'categoria',
                        'imagenes',
                        'usuarioAutor',
                        'ultimaRevisionObservacion.supervisor',
                    ])
                ),
            ], 201);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'error' => 'Hubo un problema al guardar los datos.',
                'detalle' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, int $id)
    {
        $usuario = $request->user();

        $actualizacion = UpdateBlog::findOrFail($id);
        $estadoAnterior = (string) $actualizacion->actualizacion_estado;

        if (! $this->puedeEditar($usuario, $actualizacion)) {
            abort(403, 'No tienes permiso para editar este registro.');
        }

        $data = $request->validate([
            'actualizacion_titulo' => ['sometimes', 'required', 'string', 'max:255'],
            'actualizacion_version' => ['sometimes', 'nullable', 'string', 'max:50'],
            'actualizacion_imagen_destacada' => ['sometimes', 'nullable'],
            'actualizacion_resumen' => ['sometimes', 'required', 'string'],
            'actualizacion_contenido' => ['sometimes', 'required'],
            'actualizacion_categoria_id' => ['sometimes', 'required', 'integer'],
            'actualizacion_area_servicio_id' => ['sometimes', 'required', 'integer'],
            'actualizacion_estado' => ['sometimes', 'required', Rule::in($this->estadosPermitidos())],
            'actualizacion_fecha_publicacion' => ['nullable', 'date'],
            'actualizacion_es_correccion' => ['sometimes', 'boolean'],
        ]);

        $correccionSolicitada = filter_var(
            $data['actualizacion_es_correccion'] ?? false,
            FILTER_VALIDATE_BOOLEAN
        );

        unset($data['actualizacion_es_correccion']);

        if (array_key_exists('actualizacion_contenido', $data)) {
            $data['actualizacion_contenido'] = $this->normalizarContenido(
                $data['actualizacion_contenido']
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Campos no editables desde update
        |--------------------------------------------------------------------------
        |
        | El autor siempre se conserva. El área sí se permite porque el formulario
        | de edición la muestra y la envía como actualizacion_area_servicio_id.
        |
        */

        unset($data['actualizacion_usuario_id_autor']);

        if ($request->hasFile('actualizacion_imagen_destacada')) {
            $request->validate([
                'actualizacion_imagen_destacada' => [
                    'image',
                    'mimes:jpeg,png,jpg,webp,gif',
                    'max:2048',
                ],
            ]);

            $data['actualizacion_imagen_destacada'] =
                $request
                ->file('actualizacion_imagen_destacada')
                ->store('blog/portadas', 'public');
        }

        if (array_key_exists('actualizacion_estado', $data)) {
            if (! $this->puedeCambiarEstado($usuario, $actualizacion, $data['actualizacion_estado'])) {
                abort(403, 'No tienes permiso para cambiar el estado de este registro.');
            }

            if (
                $data['actualizacion_estado'] === 'publicado' &&
                empty($data['actualizacion_fecha_publicacion']) &&
                ! $actualizacion->actualizacion_fecha_publicacion
            ) {
                $data['actualizacion_fecha_publicacion'] = now();
            }
        }

        if (! isset($data['actualizacion_version'])) {
            $data['actualizacion_version'] =
                (string) (((int) $actualizacion->actualizacion_version) + 1);
        }

        $actualizacion->update($data);
        $actualizacion->refresh();

        if ($this->debeNotificarCorreccion($estadoAnterior, $actualizacion, $correccionSolicitada)) {
            $this->crearNotificacionCorreccion($actualizacion, $usuario, $estadoAnterior);
        }

        return response()->json([
            'message' => 'Actualización editada correctamente.',
            'data' => new ActualizacionResource(
                $actualizacion->fresh([
                    'areaServicio',
                    'categoria',
                    'imagenes',
                    'usuarioAutor',
                    'ultimaRevisionObservacion.supervisor',
                ])
            ),
        ]);
    }

    public function cambiarEstado(Request $request, int $id)
    {
        $usuario = $request->user();

        if (! $request->filled('actualizacion_estado') && $request->filled('estado')) {
            $request->merge([
                'actualizacion_estado' => $request->input('estado'),
            ]);
        }

        $actualizacion = UpdateBlog::findOrFail($id);

        $data = $request->validate([
            'actualizacion_estado' => [
                'required',
                Rule::in($this->estadosPermitidos()),
            ],
        ]);

        if (! $this->puedeCambiarEstado($usuario, $actualizacion, $data['actualizacion_estado'])) {
            abort(403, 'No tienes permiso para cambiar el estado de este registro.');
        }

        $actualizacion->actualizacion_estado = $data['actualizacion_estado'];

        if (
            $data['actualizacion_estado'] === 'publicado' &&
            ! $actualizacion->actualizacion_fecha_publicacion
        ) {
            $actualizacion->actualizacion_fecha_publicacion = now();
        }

        $actualizacion->actualizacion_version =
            (string) (((int) $actualizacion->actualizacion_version) + 1);

        $actualizacion->save();

        return response()->json([
            'message' => 'Estado actualizado correctamente.',
            'data' => new ActualizacionResource(
                $actualizacion->fresh([
                    'areaServicio',
                    'categoria',
                    'imagenes',
                    'usuarioAutor',
                    'ultimaRevisionObservacion.supervisor',
                ])
            ),
        ]);
    }

    public function marcarRevision(Request $request, int $id)
    {
        $usuario = $request->user();

        $actualizacion = UpdateBlog::findOrFail($id);

        $observacionRevision = trim((string) $request->input(
            'observacion_revision',
            $request->input('observacion', '')
        ));

        $request->merge([
            'observacion_revision' => $observacionRevision,
        ]);

        $data = $request->validate([
            'observacion_revision' => ['required', 'string', 'min:10', 'max:2000'],
        ], [
            'observacion_revision.required' => 'Debes escribir el motivo por el que el registro pasa a revisión.',
            'observacion_revision.min' => 'El motivo de revisión debe tener al menos 10 caracteres.',
            'observacion_revision.max' => 'El motivo de revisión no puede superar 2000 caracteres.',
        ]);

        if (! $this->puedeCambiarEstado($usuario, $actualizacion, 'revision')) {
            abort(403, 'No tienes permiso para marcar este registro como revisión.');
        }

        DB::transaction(function () use ($actualizacion, $usuario, $data) {
            $estadoAnterior = $actualizacion->actualizacion_estado;

            $actualizacion->actualizacion_estado = 'revision';
            $actualizacion->actualizacion_version =
                (string) (((int) $actualizacion->actualizacion_version) + 1);
            $actualizacion->save();

            $actualizacion->revisionObservaciones()->create([
                'usuario_id_supervisor' => $usuario->usuario_id,
                'observacion' => $data['observacion_revision'],
                'estado_anterior' => $estadoAnterior,
                'estado_nuevo' => 'revision',
            ]);

            $this->crearNotificacionRevision(
                $actualizacion,
                $usuario,
                $data['observacion_revision'],
                $estadoAnterior
            );
        });

        return response()->json([
            'message' => 'Registro marcado como revisión correctamente.',
            'data' => new ActualizacionResource(
                $actualizacion->fresh([
                    'areaServicio',
                    'categoria',
                    'imagenes',
                    'usuarioAutor',
                    'ultimaRevisionObservacion.supervisor',
                ])
            ),
        ]);
    }

    public function activar(Request $request, int $id)
    {
        $request->merge([
            'actualizacion_estado' => 'publicado',
        ]);

        return $this->cambiarEstado($request, $id);
    }

    public function inactivar(Request $request, int $id)
    {
        $request->merge([
            'actualizacion_estado' => 'inactivo',
        ]);

        return $this->cambiarEstado($request, $id);
    }

    public function getAreas()
    {
        $areas = Area::select(
            'area_servicio_id',
            'area_servicio_nombre'
        )
            ->orderBy('area_servicio_nombre', 'asc')
            ->get();

        return response()->json([
            'data' => $areas,
        ]);
    }

    public function getCategorias()
    {
        $categorias = Category::select(
            'categoria_actualizacion_id',
            'categoria_actualizacion_nombre'
        )
            ->orderBy('categoria_actualizacion_nombre', 'asc')
            ->get();

        return response()->json([
            'data' => $categorias,
        ]);
    }

    public function getStatus()
    {
        $estados = collect($this->estadosPermitidos())
            ->map(fn (string $estado) => [
                'id' => $estado,
                'nombre' => ucfirst($estado),
            ])
            ->values();

        return response()->json([
            'data' => $estados,
        ]);
    }

    public function subirImagenEditor(Request $request)
    {
        $request->validate([
            'imagen' => [
                'required',
                'image',
                'mimes:jpeg,png,jpg,webp,gif',
                'max:5120',
            ],
        ]);

        $path = $request->file('imagen')->store('blog_images', 'public');

        return response()->json([
            'url' => asset('storage/' . $path),
            'path' => $path,
        ]);
    }

    public function subirImagenPortada(Request $request)
    {
        $request->validate([
            'imagen' => [
                'required',
                'image',
                'mimes:jpeg,png,jpg,webp,gif',
                'max:5120',
            ],
        ]);

        $path = $request->file('imagen')->store('blog/portadas', 'public');

        return response()->json([
            'url' => asset('storage/' . $path),
            'path' => $path,
        ]);
    }

    private function estadosPermitidos(): array
    {
        return [
            'borrador',
            'revision',
            'publicado',
            'inactivo',
        ];
    }

    private function puedeVer($usuario, UpdateBlog $actualizacion): bool
    {
        if ($actualizacion->actualizacion_estado === 'publicado') {
            return true;
        }

        if ($this->esAdmin($usuario)) {
            return true;
        }

        if ($this->esPropietario($usuario, $actualizacion)) {
            return true;
        }

        if ($this->esJefeArea($usuario) && $this->esAreaSupervisada($usuario, $actualizacion)) {
            return true;
        }

        return false;
    }

    private function puedeEditar($usuario, UpdateBlog $actualizacion): bool
    {
        if ($this->esAdmin($usuario)) {
            return true;
        }

        return $this->esPropietario($usuario, $actualizacion);
    }

    private function puedeCambiarEstado($usuario, UpdateBlog $actualizacion, string $nuevoEstado): bool
    {
        if ($this->esAdmin($usuario)) {
            return true;
        }

        if ($this->esPropietario($usuario, $actualizacion)) {
            return true;
        }

        return $nuevoEstado === 'revision' &&
            $this->esJefeArea($usuario) &&
            $this->esAreaSupervisada($usuario, $actualizacion);
    }

    private function esAdmin($usuario): bool
    {
        return strtoupper((string) $usuario->usuario_grupo) === 'ADMIN';
    }

    private function esJefeArea($usuario): bool
    {
        // if (in_array('blog.supervisar_area', session('tz_permisos', []), true)) {
        //     return true;
        // }

        if (method_exists($usuario, 'esJefeArea') && $usuario->esJefeArea()) {
            return true;
        }

        return ! empty($this->areasSupervisadas($usuario));
    }

    private function esPropietario($usuario, UpdateBlog $actualizacion): bool
    {
        return (int) $actualizacion->actualizacion_usuario_id_autor ===
            (int) $usuario->usuario_id;
    }

    private function esAreaSupervisada($usuario, UpdateBlog $actualizacion): bool
    {
        return in_array(
            (int) $actualizacion->actualizacion_area_servicio_id,
            $this->areasSupervisadas($usuario),
            true
        );
    }

    private function areasSupervisadas($usuario): array
    {
        $areas = [];

        if (method_exists($usuario, 'areasSupervisadas')) {
            $areasDesdeUsuario = $usuario->areasSupervisadas();

            if ($areasDesdeUsuario instanceof \Illuminate\Support\Collection) {
                $areasDesdeUsuario = $areasDesdeUsuario->all();
            }

            $areas = array_merge($areas, (array) $areasDesdeUsuario);
        }

        // if (in_array('blog.supervisar_area', session('tz_permisos', []), true)) {
        //     $areas[] = $usuario->area_servicio_id;
        // }

        return collect($areas)
            ->filter(fn ($areaId) => $areaId !== null && $areaId !== '')
            ->map(fn ($areaId) => (int) $areaId)
            ->unique()
            ->values()
            ->all();
    }

    private function crearNotificacionRevision(
        UpdateBlog $actualizacion,
        $usuario,
        string $observacion,
        string $estadoAnterior
    ): void {
        $autorId = (int) $actualizacion->actualizacion_usuario_id_autor;
        $actorId = (int) $usuario->usuario_id;

        if ($autorId <= 0 || $autorId === $actorId) {
            return;
        }

        BlogNotification::create([
            'usuario_id_destino' => $autorId,
            'usuario_id_actor' => $actorId,
            'actualizacion_id' => $actualizacion->id,
            'tipo' => 'revision',
            'titulo' => 'Registro marcado para revisión',
            'mensaje' => $observacion,
            'data' => [
                'estado_anterior' => $estadoAnterior,
                'estado_nuevo' => 'revision',
                'ruta_sugerida' => [
                    'name' => 'mis-registros-show',
                    'params' => [
                        'id' => $actualizacion->id,
                    ],
                ],
            ],
        ]);
    }

    private function debeNotificarCorreccion(
        string $estadoAnterior,
        UpdateBlog $actualizacion,
        bool $correccionSolicitada
    ): bool {
        return (
            $correccionSolicitada || $estadoAnterior === 'revision'
        ) && $estadoAnterior === 'revision'
            && (string) $actualizacion->actualizacion_estado === 'publicado';
    }

    private function crearNotificacionCorreccion(
        UpdateBlog $actualizacion,
        $usuario,
        string $estadoAnterior
    ): void {
        $areaId = (int) $actualizacion->actualizacion_area_servicio_id;
        $actorId = (int) $usuario->usuario_id;

        if ($areaId <= 0) {
            return;
        }

        $supervisorRevisionId = $actualizacion->revisionObservaciones()
            ->value('usuario_id_supervisor');

        $jefesDestino = JefeArea::activos()
            ->where('jefe_area', $areaId)
            ->pluck('id_usuario')
            ->push($supervisorRevisionId)
            ->filter(fn ($usuarioId) => (int) $usuarioId > 0)
            ->map(fn ($usuarioId) => (int) $usuarioId)
            ->unique()
            ->values();

        if ($jefesDestino->isEmpty()) {
            return;
        }

        foreach ($jefesDestino as $usuarioDestinoId) {
            if ($usuarioDestinoId === $actorId) {
                continue;
            }

            BlogNotification::create([
                'usuario_id_destino' => $usuarioDestinoId,
                'usuario_id_actor' => $actorId,
                'actualizacion_id' => $actualizacion->id,
                'tipo' => 'correccion',
                'titulo' => 'Registro corregido',
                'mensaje' => sprintf(
                    'El registro "%s" fue corregido y publicado nuevamente.',
                    $actualizacion->actualizacion_titulo
                ),
                'data' => [
                    'estado_anterior' => $estadoAnterior,
                    'estado_nuevo' => 'publicado',
                    'area_servicio_id' => $areaId,
                    'ruta_sugerida' => [
                        'name' => 'supervision-show',
                        'params' => [
                            'id' => $actualizacion->id,
                        ],
                    ],
                ],
            ]);
        }
    }

    private function normalizarContenido($contenido): string
    {
        if (is_array($contenido)) {
            return json_encode($contenido, JSON_UNESCAPED_UNICODE);
        }

        return (string) $contenido;
    }
}
