<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Observaciones;
use Illuminate\Http\Request;

class ObservacionesPendientesController extends Controller
{
    public function index(Request $request)
    {
        $usuarioId = $request->user()->usuario_id;

        $observaciones = Observaciones::with([
                'actualizacion:id,actualizacion_titulo,actualizacion_estado,actualizacion_version,actualizacion_usuario_id_autor',
            ])
            ->pendientesDe($usuarioId)
            ->latest()
            ->get()
            ->unique('actualizacion_id')
            ->values();

        return response()->json([
            'data' => $observaciones->map(fn (Observaciones $o) => $this->formatear($o))->values(),
            'meta' => [
                'total' => $observaciones->count(),
            ],
        ]);
    }

    public function contador(Request $request)
    {
        $usuarioId = $request->user()->usuario_id;

        $total = Observaciones::pendientesDe($usuarioId)
            ->distinct('actualizacion_id')
            ->count('actualizacion_id');

        return response()->json([
            'data' => ['pendientes' => $total],
        ]);
    }

    private function formatear(Observaciones $observacion): array
    {
        return [
            'id' => $observacion->id,
            'observacion' => $observacion->observacion,
            'estado_anterior' => $observacion->estado_anterior,
            'estado_nuevo' => $observacion->estado_nuevo,
            'created_at' => $observacion->created_at,
            'actualizacion_id' => $observacion->actualizacion_id,
            'actualizacion' => $observacion->relationLoaded('actualizacion') && $observacion->actualizacion ? [
                'id' => $observacion->actualizacion->id,
                'actualizacion_titulo' => $observacion->actualizacion->actualizacion_titulo,
                'actualizacion_estado' => $observacion->actualizacion->actualizacion_estado,
                'actualizacion_version' => $observacion->actualizacion->actualizacion_version,
            ] : null,
        ];
    }
}