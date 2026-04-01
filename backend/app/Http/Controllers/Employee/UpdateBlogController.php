<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\UpdateBlog;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class UpdateBlogController extends Controller
{
    public function index()
    {
        // Traemos solo las actualizaciones PUBLICADAS, ordenadas por las más recientes,
        // y le pedimos a Laravel que incluya las imágenes asociadas.
        $actualizaciones = UpdateBlog::with('imagenes')
            ->where('actualizacion_estado', 'publicado') // <-- Agregas esta línea
            ->orderBy('id', 'desc')
            ->paginate(5);

        return response()->json($actualizaciones);
    }
}
