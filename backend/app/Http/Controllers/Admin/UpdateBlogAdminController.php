<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Area;
use Illuminate\Http\Request;
use App\Models\UpdateBlog;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class UpdateBlogAdminController extends Controller
{
    public function store(Request $request)
    {
        // 1. Validación de los datos que llegan desde el FormData de Vue
        $request->validate([
            'actualizacion_titulo'            => 'required|string|max:255',
            'actualizacion_version'           => 'required|string|max:255',
            'actualizacion_contenido'         => 'required|string',
            'actualizacion_resumen'           => 'required|string',
            'actualizacion_imagen_destacada'  => 'nullable|string',
            'actualizacion_area_servicio_id'  => 'required|integer',
            'actualizacion_usuario_id_autor'  => 'required|integer',
            'actualizacion_estado'            => 'required|in:borrador,revision,publicado,inactivo',

            // Validamos el campo de las rutas de imágenes que manda Quill desde Vue
            'imagenes_quill'                  => 'nullable|string',
        ]);

        try {
            // Iniciamos la transacción de seguridad
            DB::beginTransaction();

            // 2. Crear el registro principal en 'actualizaciones_blog'
            // Ignoramos 'imagenes_quill' para que Laravel no intente meter el JSON en la tabla principal
            $actualizacion = UpdateBlog::create($request->except(['imagenes', 'imagenes_quill']));

            // 3. GUARDAR LAS RUTAS DE LAS IMÁGENES DE QUILL
            if ($request->filled('imagenes_quill')) {

                // Decodificamos el arreglo JSON que Vue nos mandó
                $rutasImagenes = json_decode($request->imagenes_quill, true);

                if (is_array($rutasImagenes)) {
                    foreach ($rutasImagenes as $ruta) {
                        // Guardamos la ruta limpia en la base de datos usando la relación
                        $actualizacion->imagenes()->create([
                            'ruta_imagen' => $ruta
                        ]);
                    }
                }
            }

            // Si todo salió bien, confirmamos los cambios en la BD
            DB::commit();

            // 4. Retornar respuesta al frontend, cargando el registro junto con sus imágenes
            return response()->json([
                'mensaje' => 'Registro e imágenes guardados con éxito',
                'data' => $actualizacion->load('imagenes')
            ], 201);
        } catch (\Exception $e) {
            // Si algo falla, deshacemos cualquier cambio en la base de datos
            DB::rollBack();

            return response()->json([
                'error' => 'Hubo un problema al guardar los datos.',
                'detalle' => $e->getMessage()
            ], 500);
        }
    }

    public function index()
    {
        // Traemos todas las actualizaciones, ordenadas por las más recientes primero,
        // y le pedimos a Laravel que incluya las imágenes asociadas.
        // NOTA: Como este es el AdminController, está bien traer todos los estados.
        $actualizaciones = UpdateBlog::with('imagenes')
            ->orderBy('id', 'desc')
            ->paginate(8);

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

    public function subirImagenQuill(Request $request)
    {
        $request->validate([
            // Cambiamos 'image' por 'file' y quitamos 'mimes' momentáneamente para probar
            'imagen' => 'required|file|max:5120', 
        ]);

        if ($request->hasFile('imagen')) {
            $path = $request->file('imagen')->store('blog_images', 'public');

            return response()->json([
                'url' => asset('storage/' . $path),
                'path' => $path 
            ]);
        }
        // ...
    }

    public function show($id)
    {
        // Buscamos la actualización por su ID. Si no existe, devuelve error 404.
        $actualizacion = UpdateBlog::with('imagenes')->findOrFail($id);

        return response()->json([
            'data' => $actualizacion
        ]);
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
