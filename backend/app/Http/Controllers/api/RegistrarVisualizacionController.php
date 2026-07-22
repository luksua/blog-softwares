<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UpdateBlog;
use App\Models\Visualizacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RegistrarVisualizacionController extends Controller
{
    public function __invoke(Request $request, UpdateBlog $actualizacion)
    {
        $usuario = $request->user();

        /*
         * Aquí debes conservar la validación o policy que ya utilices
         * para determinar si el usuario puede abrir la publicación.
         */
        abort_unless($usuario, 401);

        $visualizacionReciente = Visualizacion::query()
            ->where('actualizacion_id', $actualizacion->id)
            ->where('usuario_id', $usuario->usuario_id)
            ->where('visualizado_at', '>=', now()->subMinutes(30))
            ->exists();

        if ($visualizacionReciente) {
            return response()->json([
                'data' => [
                    'registrada' => false,
                    'motivo' => 'visualizacion_reciente',
                ],
            ]);
        }

        DB::transaction(function () use (
            $request,
            $usuario,
            $actualizacion
        ) {
            Visualizacion::create([
                'actualizacion_id' => $actualizacion->id,
                'usuario_id' => $usuario->usuario_id,
                'area_servicio_id' => $usuario->area_servicio_id,
                'visualizado_at' => now(),
                'ip' => $request->ip(),
                'user_agent' => mb_substr(
                    (string) $request->userAgent(),
                    0,
                    1000
                ),
            ]);

            if (
                Schema::hasColumn(
                    'actualizaciones_blog',
                    'actualizacion_lecturas'
                )
            ) {
                UpdateBlog::withoutGlobalScopes()
                    ->whereKey($actualizacion->id)
                    ->increment('actualizacion_lecturas');
            }
        });

        return response()->json([
            'data' => [
                'registrada' => true,
            ],
        ], 201);
    }
}