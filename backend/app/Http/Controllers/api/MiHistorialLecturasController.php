<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UpdateBlog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MiHistorialLecturasController extends Controller
{
    public function __invoke(Request $request)
    {
        $usuarioId = (int) $request->user()->usuario_id;

        /*
         * Agrupa las visualizaciones para que una publicación aparezca
         * una sola vez, mostrando la última vez que se leyó.
         */
        $ultimasLecturas = DB::table(
            'actualizaciones_blog_visualizaciones'
        )
            ->selectRaw('
                actualizacion_id,
                MAX(visualizado_at) AS ultima_visualizacion,
                COUNT(*) AS veces_visualizado
            ')
            ->where('usuario_id', $usuarioId)
            ->groupBy('actualizacion_id');

        /*
         * UpdateBlog::query() conserva los scopes normales del blog.
         * Así no se muestran publicaciones que el usuario ya no pueda abrir.
         */
        $historial = UpdateBlog::query()
            ->joinSub(
                $ultimasLecturas,
                'historial',
                function ($join) {
                    $join->on(
                        'historial.actualizacion_id',
                        '=',
                        'actualizaciones_blog.id'
                    );
                }
            )
            ->select([
                'actualizaciones_blog.id',
                'actualizaciones_blog.actualizacion_titulo',
                'actualizaciones_blog.actualizacion_resumen',
                'actualizaciones_blog.actualizacion_imagen_destacada',
                'actualizaciones_blog.actualizacion_version',
                'actualizaciones_blog.actualizacion_fecha_publicacion',
                'actualizaciones_blog.actualizacion_area_servicio_id',
                'historial.ultima_visualizacion',
                'historial.veces_visualizado',
            ])
            ->orderByDesc('historial.ultima_visualizacion')
            ->paginate(20);

        return response()->json($historial);
    }
}