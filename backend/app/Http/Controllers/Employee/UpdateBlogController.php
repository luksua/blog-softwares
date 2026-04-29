<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\UpdateBlog;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use App\Models\Area;
use App\Http\Resources\ActualizacionResource;

class UpdateBlogController extends Controller
{
    public function index(Request $request)
    {
        $query = UpdateBlog::with('areaServicio')
            ->where('actualizacion_estado', 'publicado');

        if ($request->filled('busqueda')) {
            $query->where(function ($q) use ($request) {
                $q->where('actualizacion_titulo', 'like', "%{$request->busqueda}%")
                    ->orWhere('actualizacion_resumen', 'like', "%{$request->busqueda}%");
            });
        }

        if ($request->filled('area_servicio_id')) {
            $query->where('actualizacion_area_servicio_id', $request->area_servicio_id);
        }

        if ($request->filled('fecha_desde')) {
            $query->whereDate('actualizacion_fecha_publicacion', '>=', $request->fecha_desde);
        }

        if ($request->filled('fecha_hasta')) {
            $query->whereDate('actualizacion_fecha_publicacion', '<=', $request->fecha_hasta);
        }

        match ($request->input('orden', 'recientes')) {
            'antiguos' => $query->oldest('actualizacion_fecha_publicacion'),
            'az' => $query->orderBy('actualizacion_titulo', 'asc'),
            'za' => $query->orderBy('actualizacion_titulo', 'desc'),
            default => $query->latest('actualizacion_fecha_publicacion'),
        };

        $perPage = $request->input('per_page', 9);

        return response()->json($query->paginate($perPage));
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

    public function show($id)
    {
        $actualizacion = UpdateBlog::with('areaServicio')
            ->where('actualizacion_estado', 'publicado')
            ->findOrFail($id);

        return new ActualizacionResource($actualizacion);
    }
}
