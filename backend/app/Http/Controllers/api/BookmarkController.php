<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ActualizacionResource;
use App\Models\Bookmark;
use App\Models\UpdateBlog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookmarkController extends Controller
{
    public function index(Request $request)
    {
        $usuarioId = (int) $request->user()->usuario_id;

        $bookmarks = Bookmark::query()
            ->with([
                'actualizacion.areaServicio',
                'actualizacion.categoria',
                'actualizacion.imagenes',
            ])
            ->where('usuario_id', $usuarioId)
            ->latest()
            ->get();

        $actualizaciones = $bookmarks
            ->pluck('actualizacion')
            ->filter(fn ($actualizacion) => $actualizacion && $actualizacion->actualizacion_estado === 'publicado')
            ->values();

        return ActualizacionResource::collection($actualizaciones);
    }

    public function ids(Request $request): JsonResponse
    {
        $usuarioId = (int) $request->user()->usuario_id;

        $ids = Bookmark::query()
            ->where('usuario_id', $usuarioId)
            ->pluck('actualizacion_id')
            ->map(fn ($id) => (int) $id)
            ->values();

        return response()->json([
            'data' => $ids,
        ]);
    }

    public function store(Request $request, int $actualizacion): JsonResponse
    {
        $usuarioId = (int) $request->user()->usuario_id;

        UpdateBlog::query()
            ->whereKey($actualizacion)
            ->where('actualizacion_estado', 'publicado')
            ->firstOrFail();

        Bookmark::query()->firstOrCreate([
            'usuario_id' => $usuarioId,
            'actualizacion_id' => $actualizacion,
        ]);

        return response()->json([
            'saved' => true,
            'actualizacion_id' => $actualizacion,
        ]);
    }

    public function destroy(Request $request, int $actualizacion): JsonResponse
    {
        $usuarioId = (int) $request->user()->usuario_id;

        Bookmark::query()
            ->where('usuario_id', $usuarioId)
            ->where('actualizacion_id', $actualizacion)
            ->delete();

        return response()->json([
            'saved' => false,
            'actualizacion_id' => $actualizacion,
        ]);
    }

    public function destroyAll(Request $request): JsonResponse
    {
        $usuarioId = (int) $request->user()->usuario_id;

        Bookmark::query()
            ->where('usuario_id', $usuarioId)
            ->delete();

        return response()->json([
            'saved' => false,
            'data' => [],
        ]);
    }
}
