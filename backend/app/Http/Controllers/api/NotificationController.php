<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $usuario = $request->user();

        return Notification::query()
            ->where('usuario_id', $usuario->usuario_id)
            ->latest()
            ->limit(20)
            ->get();
    }

    public function countNoLeidas(Request $request)
    {
        $usuario = $request->user();

        $total = Notification::query()
            ->where('usuario_id', $usuario->usuario_id)
            ->where('leido', false)
            ->count();

        return response()->json([
            'total' => $total,
        ]);
    }

    public function marcarLeida(Request $request, Notification $notificacion)
    {
        $usuario = $request->user();

        abort_if(
            (int) $notificacion->usuario_id !== (int) $usuario->usuario_id,
            403,
            'No puedes modificar esta notificación.'
        );

        $notificacion->update([
            'leido' => true,
            'leido_at' => now(),
        ]);

        return response()->json([
            'message' => 'Notificación marcada como leída.',
        ]);
    }

    public function marcarTodasLeidas(Request $request)
    {
        $usuario = $request->user();

        Notification::query()
            ->where('usuario_id', $usuario->usuario_id)
            ->where('leido', false)
            ->update([
                'leido' => true,
                'leido_at' => now(),
            ]);

        return response()->json([
            'message' => 'Notificaciones marcadas como leídas.',
        ]);
    }
}