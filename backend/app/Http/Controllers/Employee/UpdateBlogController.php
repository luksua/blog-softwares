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
        // Traemos todas las actualizaciones, ordenadas por las más recientes primero,
        // y le pedimos a Laravel que incluya las imágenes asociadas.
        $actualizaciones = UpdateBlog::with('imagenes')
            ->orderBy('id', 'desc')
            ->paginate(9);

        return response()->json($actualizaciones);
    }
}
