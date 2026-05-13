<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ActualizacionResource;
use App\Models\Area;
use App\Models\Category;
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

        $query = UpdateBlog::with([
            'areaServicio',
            'categoria',
            'imagenes',
        ]);

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

            if (! $this->esAdmin($usuario) && ! $this->esJefeArea()) {
                abort(403, 'No tienes permiso para supervisar registros.');
            }

            if (! $this->esAdmin($usuario)) {
                $query->where(
                    'actualizacion_area_servicio_id',
                    $usuario->area_servicio_id
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
                Rule::in([
                    'borrador',
                    'revision',
                    'publicado',
                    'inactivo',
                ]),
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
            'actualizacion_fecha_publicacion' => ['nullable', 'date'],
        ]);

        if (array_key_exists('actualizacion_contenido', $data)) {
            $data['actualizacion_contenido'] = $this->normalizarContenido(
                $data['actualizacion_contenido']
            );
        }

        /*
        |--------------------------------------------------------------------------
        | No permitimos cambiar autor ni área desde update
        |--------------------------------------------------------------------------
        |
        | Si después necesitas que ADMIN pueda mover registros de área,
        | eso debería ser una función separada.
        |
        */

        unset(
            $data['actualizacion_usuario_id_autor'],
            $data['actualizacion_area_servicio_id']
        );

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

        if (! isset($data['actualizacion_version'])) {
            $data['actualizacion_version'] =
                (string) (((int) $actualizacion->actualizacion_version) + 1);
        }

        $actualizacion->update($data);

        return response()->json([
            'message' => 'Actualización editada correctamente.',
            'data' => new ActualizacionResource(
                $actualizacion->fresh([
                    'areaServicio',
                    'categoria',
                    'imagenes',
                ])
            ),
        ]);
    }

    public function cambiarEstado(Request $request, int $id)
    {
        $usuario = $request->user();

        $actualizacion = UpdateBlog::findOrFail($id);

        if (! $this->puedeCambiarEstado($usuario, $actualizacion)) {
            abort(403, 'No tienes permiso para cambiar el estado de este registro.');
        }

        $data = $request->validate([
            'actualizacion_estado' => [
                'required',
                Rule::in([
                    'borrador',
                    'revision',
                    'publicado',
                    'inactivo',
                ]),
            ],
        ]);

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
        $resultado = DB::select(
            'SHOW COLUMNS FROM actualizaciones_blog WHERE Field = "actualizacion_estado"'
        );

        if (empty($resultado)) {
            return response()->json([
                'data' => [],
            ]);
        }

        $columna = $resultado[0];

        preg_match('/^enum\((.*)\)$/', $columna->Type, $matches);

        $estados = [];

        if (isset($matches[1])) {
            foreach (explode(',', $matches[1]) as $value) {
                $valorLimpio = trim($value, "'");

                $estados[] = [
                    'id' => $valorLimpio,
                    'nombre' => ucfirst($valorLimpio),
                ];
            }
        }

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

        if ($this->esJefeArea() && $this->esMismaArea($usuario, $actualizacion)) {
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

    private function puedeCambiarEstado($usuario, UpdateBlog $actualizacion): bool
    {
        if ($this->esAdmin($usuario)) {
            return true;
        }

        return $this->esPropietario($usuario, $actualizacion);
    }

    private function esAdmin($usuario): bool
    {
        return strtoupper((string) $usuario->usuario_grupo) === 'ADMIN';
    }

    private function esJefeArea(): bool
    {
        return in_array(
            'blog.supervisar_area',
            session('tz_permisos', []),
            true
        );
    }

    private function esPropietario($usuario, UpdateBlog $actualizacion): bool
    {
        return (int) $actualizacion->actualizacion_usuario_id_autor ===
            (int) $usuario->usuario_id;
    }

    private function esMismaArea($usuario, UpdateBlog $actualizacion): bool
    {
        return (int) $actualizacion->actualizacion_area_servicio_id ===
            (int) $usuario->area_servicio_id;
    }

    private function normalizarContenido($contenido): string
    {
        if (is_array($contenido)) {
            return json_encode($contenido, JSON_UNESCAPED_UNICODE);
        }

        return (string) $contenido;
    }
}
