<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BlogNotification;
use App\Models\Observaciones;
use Illuminate\Http\Request;

class BlogNotificationController extends Controller
{
    public function index(Request $request)
    {
        $usuarioId = $request->user()->usuario_id;
        $soloNoLeidas = filter_var($request->input('no_leidas', false), FILTER_VALIDATE_BOOLEAN);

        $query = BlogNotification::with([
            'actualizacion:id,actualizacion_titulo,actualizacion_estado,actualizacion_version,actualizacion_usuario_id_autor',
            'actor:usuario_id,usuario_usuario,usuario_nombre1,usuario_nombre2,usuario_apellido1,usuario_apellido2',
        ])
            ->where('usuario_id_destino', $usuarioId)
            ->latest();

        if ($soloNoLeidas) {
            $query->noLeidas();
        }

        $perPage = min((int) $request->input('per_page', 10), 50);
        $notificaciones = $query->paginate($perPage);

        return response()->json([
            'data' => $notificaciones->getCollection()->map(fn (BlogNotification $notificacion) => $this->formatear($notificacion))->values(),
            'meta' => [
                'current_page' => $notificaciones->currentPage(),
                'last_page' => $notificaciones->lastPage(),
                'per_page' => $notificaciones->perPage(),
                'total' => $notificaciones->total(),
                'no_leidas' => $this->contarNoLeidas($usuarioId),
            ],
        ]);
    }

    public function indexObservaciones(Request $request)
    {
        $usuarioId = $request->user()->usuario_id;

        $query = Observaciones::with([
            'actualizacion:id,actualizacion_titulo,actualizacion_estado,actualizacion_version,actualizacion_usuario_id_autor'
        ])
            ->where('estado_nuevo', 'revision')
            ->where('usuario_id_destino', $usuarioId)
            ->latest();

        $observaciones = $query;

        return response()->json([
            'data' => $observaciones->getCollection()->map(fn (Observaciones $observacion) => $this->formatear($observacion))->values(),
        ]);
    }

    public function contador(Request $request)
    {
        return response()->json([
            'data' => [
                'no_leidas' => $this->contarNoLeidas($request->user()->usuario_id),
            ],
        ]);
    }

    public function marcarLeida(Request $request, int $id)
    {
        $notificacion = BlogNotification::where('usuario_id_destino', $request->user()->usuario_id)
            ->findOrFail($id);

        if (! $notificacion->leida_en) {
            $notificacion->forceFill([
                'leida_en' => now(),
            ])->save();
        }

        return response()->json([
            'message' => 'Notificación marcada como leída.',
            'data' => $this->formatear($notificacion->fresh(['actualizacion', 'actor'])),
        ]);
    }

    public function marcarTodasLeidas(Request $request)
    {
        BlogNotification::where('usuario_id_destino', $request->user()->usuario_id)
            ->whereNull('leida_en')
            ->update([
                'leida_en' => now(),
                'updated_at' => now(),
            ]);

        return response()->json([
            'message' => 'Notificaciones marcadas como leídas.',
            'data' => [
                'no_leidas' => 0,
            ],
        ]);
    }

    private function contarNoLeidas(int $usuarioId): int
    {
        return BlogNotification::where('usuario_id_destino', $usuarioId)
            ->whereNull('leida_en')
            ->count();
    }

    private function formatear(BlogNotification $notificacion): array
    {
        return [
            'id' => $notificacion->id,
            'tipo' => $notificacion->tipo,
            'titulo' => $notificacion->titulo,
            'mensaje' => $notificacion->mensaje,
            'data' => $notificacion->data ?? [],
            'leida_en' => $notificacion->leida_en,
            'created_at' => $notificacion->created_at,
            'actualizacion_id' => $notificacion->actualizacion_id,
            'actualizacion' => $notificacion->relationLoaded('actualizacion') && $notificacion->actualizacion ? [
                'id' => $notificacion->actualizacion->id,
                'actualizacion_titulo' => $notificacion->actualizacion->actualizacion_titulo,
                'actualizacion_estado' => $notificacion->actualizacion->actualizacion_estado,
                'actualizacion_version' => $notificacion->actualizacion->actualizacion_version,
                'actualizacion_usuario_id_autor' => $notificacion->actualizacion->actualizacion_usuario_id_autor,
            ] : null,
            'actor' => $notificacion->relationLoaded('actor') && $notificacion->actor ? [
                'usuario_id' => $notificacion->actor->usuario_id,
                'usuario_usuario' => $notificacion->actor->usuario_usuario,
                'usuario_nombre1' => $notificacion->actor->usuario_nombre1,
                'usuario_nombre2' => $notificacion->actor->usuario_nombre2,
                'usuario_apellido1' => $notificacion->actor->usuario_apellido1,
                'usuario_apellido2' => $notificacion->actor->usuario_apellido2,
            ] : null,
        ];
    }
}
