<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\UpdateBlog;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class UpdateBlogController extends Controller
{
    public function store(Request $request)
    {
        // 1. Validación de los datos que llegan desde el FormData de Vue
        $request->validate([
            'titulo'            => 'required|string|max:255',
            'contenido'         => 'required|string',
            'resumen'           => 'required|string',
            'imagen_destacada'  => 'nullable|string', // Asumo que envías una URL estática por ahora
            'area_servicio_id'  => 'required|integer',
            'usuario_id_autor'  => 'required|integer',
            'estado'            => 'required|in:borrador,publicado',
            'fecha_publicacion' => 'required|date',
            
            // Validación para el arreglo de múltiples imágenes
            'imagenes'          => 'nullable|array',
            'imagenes.*'        => 'image|mimes:jpeg,png,jpg,webp|max:2048' // Máximo 2MB por imagen
        ]);

        try {
            // Iniciamos la transacción de seguridad
            DB::beginTransaction();

            // 2. Crear el registro principal en 'actualizaciones_blog'
            // Usamos except('imagenes') para que Laravel no intente guardar el array de archivos en la tabla principal
            $actualizacion = UpdateBlog::create($request->except('imagenes'));

            // 3. Procesar las imágenes (si el usuario adjuntó alguna)
            if ($request->hasFile('imagenes')) {
                foreach ($request->file('imagenes') as $archivoImagen) {
                    
                    // A. Guardar el archivo físico en el servidor (storage/app/public/galeria_blog)
                    $ruta = $archivoImagen->store('galeria_blog', 'public');

                    // B. Guardar la ruta en la base de datos usando la relación que creamos en el modelo
                    $actualizacion->imagenes()->create([
                        'ruta_imagen' => $ruta
                    ]);
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
            
            // Opcional: Podrías agregar lógica aquí para borrar las imágenes físicas que sí se alcanzaron a subir antes del error
            
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
        $actualizaciones = UpdateBlog::with('photos')
                            ->orderBy('id', 'desc')
                            ->get();

        return response()->json($actualizaciones);
    }
}