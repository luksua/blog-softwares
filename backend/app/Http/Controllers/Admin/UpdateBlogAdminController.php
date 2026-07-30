<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Area;
use Illuminate\Http\Request;
use App\Models\UpdateBlog;
use Illuminate\Support\Facades\DB;
use App\Models\Category;

class UpdateBlogAdminController extends Controller
{
    public function store(Request $request)
    {
        // 1. Validación de los datos
        $this->prepararCategoriaIds($request);

        $datosValidados = $request->validate([
            'actualizacion_titulo'            => 'required|string|max:255',
            'actualizacion_version'           => 'required|string|max:255',
            'actualizacion_contenido'         => 'required|string', // Aquí viene el JSON de Editor.js como string
            'actualizacion_resumen'           => 'required|string|max:800',

            'actualizacion_imagen_destacada'  => 'nullable|image|mimes:jpeg,png,jpg,webp,gif|max:2048',

            'actualizacion_area_servicio_id'  => 'required|integer',
            'actualizacion_categoria_ids' => 'required|array|min:1|max:3',
            'actualizacion_categoria_ids.*' => 'required|integer|distinct|exists:act_categorias,categoria_actualizacion_id',
            'actualizacion_usuario_id_autor'  => 'required|integer',
            'actualizacion_estado'            => 'required|in:borrador,revision,publicado,inactivo',
            'imagenes_quill'                  => 'nullable|string', // Mantenido si aún guardas trackers manuales
        ]);

        try {
            DB::beginTransaction();

            // 2. Preparamos los datos base (excluyendo los campos conflictivos temporalmente)
            $datosParaGuardar = collect($datosValidados)
                ->except(['actualizacion_imagen_destacada', 'imagenes_quill', 'actualizacion_categoria_ids'])
                ->toArray();

            $datosParaGuardar['actualizacion_categoria_id'] = $datosValidados['actualizacion_categoria_ids'][0];

            if ($request->hasFile('actualizacion_imagen_destacada')) {
                // Esto guarda el archivo en 'storage/app/public/blog/portadas'
                // y devuelve la ruta (ej: "blog/portadas/AJs83...jpg")
                $rutaImagen = $request->file('actualizacion_imagen_destacada')->store('blog/portadas', 'public');

                // Inyectamos ESA RUTA de texto en nuestro array de datos para la BD
                $datosParaGuardar['actualizacion_imagen_destacada'] = $rutaImagen;
            }

            // 3. Crear el registro principal en 'actualizaciones_blog'
            $actualizacion = UpdateBlog::create($datosParaGuardar);
            $this->sincronizarCategorias($actualizacion, $datosValidados['actualizacion_categoria_ids']);

            if ($request->filled('imagenes_quill')) {
                $rutasImagenes = json_decode($request->imagenes_quill, true);

                if (is_array($rutasImagenes)) {
                    foreach ($rutasImagenes as $ruta) {
                        $actualizacion->imagenes()->create([
                            'ruta_imagen' => $ruta
                        ]);
                    }
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Registro guardado con éxito',
                'data' => $actualizacion->load(['imagenes', 'categoria', 'categorias'])
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'error' => 'Hubo un problema al guardar los datos.',
                'detalle' => $e->getMessage()
            ], 500);
        }
    }

    public function index(Request $request)
    {
        $query = UpdateBlog::with(['areaServicio', 'imagenes', 'categoria', 'categorias'])
            ->orderByDesc('id');

        if ($request->filled('busqueda')) {
            $query->where('actualizacion_titulo', 'like', '%' . $request->busqueda . '%');
        }

        if ($request->filled('fecha_desde')) {
            $query->whereDate('actualizacion_fecha_publicacion', '>=', $request->fecha_desde);
        }

        if ($request->filled('fecha_hasta')) {
            $query->whereDate('actualizacion_fecha_publicacion', '<=', $request->fecha_hasta);
        }

        if ($request->filled('estado')) {
            $query->where('actualizacion_estado', $request->estado);
        }

        if ($request->filled('area_servicio_id')) {
            $query->where('actualizacion_area_servicio_id', (int) $request->area_servicio_id);
        }

        if ($request->filled('actualizacion_categoria_id')) {
            $categoriaId = (int) $request->actualizacion_categoria_id;

            $query->where(function ($q) use ($categoriaId) {
                $q->where('actualizacion_categoria_id', $categoriaId)
                    ->orWhereHas('categorias', function ($subQuery) use ($categoriaId) {
                        $subQuery->where('act_categorias.categoria_actualizacion_id', $categoriaId);
                    });
            });
        }

        $actualizaciones = $query->paginate(10);

        return response()->json($actualizaciones);
    }

    public function getAreas()
    {
        $areas = Area::select('area_servicio_id', 'area_servicio_nombre')
            ->orderBy('area_servicio_nombre', 'asc')
            ->get();

        return response()->json([
            'data' => $areas
        ]);
    }

    public function getCategorias()
    {
        $categorias = Category::select('categoria_actualizacion_id', 'categoria_actualizacion_nombre', 'categoria_actualizacion_icono')
            ->where('categoria_actualizacion_activa', true)
            ->orderBy('categoria_actualizacion_orden', 'asc')
            ->orderBy('categoria_actualizacion_nombre', 'asc')
            ->get();

        return response()->json([
            'data' => $categorias
        ]);
    }

    public function getStatus()
    {
        $resultado = DB::select('SHOW COLUMNS FROM actualizaciones_blog WHERE Field = "actualizacion_estado"');

        if (empty($resultado)) {
            return response()->json(['data' => []]);
        }

        $columna = $resultado[0];

        preg_match('/^enum\((.*)\)$/', $columna->Type, $matches);

        $estados = [];

        if (isset($matches[1])) {
            foreach (explode(',', $matches[1]) as $value) {
                $valorLimpio = trim($value, "'");
                $estados[] = [
                    'id' => $valorLimpio,
                    'nombre' => ucfirst($valorLimpio)
                ];
            }
        }

        return response()->json([
            'data' => $estados
        ]);
    }

    public function subirImagenEditor(Request $request)
    {
        $request->validate([
            'imagen' => 'required|image|mimes:jpeg,png,jpg,webp,gif|max:5120',
        ]);

        if ($request->hasFile('imagen')) {
            // Guarda la imagen en storage/app/public/blog_images
            $path = $request->file('imagen')->store('blog_images', 'public');

            // Devuelve la URL completa para que Editor.js la dibuje en el texto
            return response()->json([
                'url' => asset('storage/' . $path),
                'path' => $path // Opcional, pero útil si luego quieres borrarla
            ]);
        }

        // Es buena práctica retornar un error si no llegó la imagen
        return response()->json(['error' => 'No se recibió ninguna imagen.'], 400);
    }

    public function subirImagen(Request $request)
    {
        $request->validate([
            'imagen' => 'required|image|mimes:jpeg,png,jpg,webp,gif|max:5120',
        ]);

        if ($request->hasFile('imagen')) {
            // Guarda la imagen en storage/app/public/blog_images
            $path = $request->file('imagen')->store('blog/portadas', 'public');

            // Devuelve la URL completa para que Editor.js la dibuje en el texto
            return response()->json([
                'url' => asset('storage/' . $path),
                'path' => $path // Opcional, pero útil si luego quieres borrarla
            ]);
        }

        // Es buena práctica retornar un error si no llegó la imagen
        return response()->json(['error' => 'No se recibió ninguna imagen.'], 400);
    }

    private function prepararCategoriaIds(Request $request): void
    {
        $ids = $request->input('actualizacion_categoria_ids');

        if (is_string($ids)) {
            $decoded = json_decode($ids, true);
            $ids = is_array($decoded) ? $decoded : explode(',', $ids);
        }

        if ($ids === null && $request->filled('actualizacion_categoria_id')) {
            $ids = [$request->input('actualizacion_categoria_id')];
        }

        if (! is_array($ids)) {
            return;
        }

        $ids = collect($ids)
            ->filter(fn ($id) => $id !== null && $id !== '')
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->take(3)
            ->values()
            ->all();

        $request->merge([
            'actualizacion_categoria_ids' => $ids,
            'actualizacion_categoria_id' => $ids[0] ?? null,
        ]);
    }

    private function sincronizarCategorias(UpdateBlog $actualizacion, array $categoriaIds): void
    {
        $categoriaIds = collect($categoriaIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->take(3)
            ->values()
            ->all();

        $actualizacion->categorias()->sync($categoriaIds);

        $primeraCategoriaId = $categoriaIds[0] ?? null;

        if ((int) $actualizacion->actualizacion_categoria_id !== (int) $primeraCategoriaId) {
            $actualizacion->actualizacion_categoria_id = $primeraCategoriaId;
            $actualizacion->save();
        }
    }

    // public function subirImagenQuill(Request $request)
    // {
    //     // 1. COMENTAMOS LA VALIDACIÓN TEMPORALMENTE
    //     // $request->validate([
    //     //     'imagen' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
    //     // ]);

    //     // 2. ATRAPAMOS EL ARCHIVO
    //     $archivo = $request->file('imagen');

    //     if ($archivo) {
    //         // 3. LE PREGUNTAMOS A PHP QUÉ PASÓ REALMENTE
    //         return response()->json([
    //             'diagnostico' => 'Archivo recibido, revisa estos datos:',
    //             'codigo_de_error_php' => $archivo->getError(), // 0 es perfecto. 1 es que pesa demasiado para PHP.
    //             'es_valido' => $archivo->isValid(), // ¿PHP lo considera un archivo sano?
    //             'tipo_mime' => $archivo->getClientMimeType(), // ¿Qué formato cree PHP que es?
    //             'peso_bytes' => $archivo->getSize()
    //         ], 422); // Devolvemos error 422 a propósito para que Vue lo atrape y lo veas en la consola
    //     }

    //     return response()->json(['error' => 'No llegó absolutamente ningún archivo a Laravel'], 400);
    // }
}
