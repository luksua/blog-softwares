<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Job extends Model
{
    // nombre de la tabla en la bd
    protected $table = 'cargos';

    // llave primaria
    protected $primaryKey = 'cargo_id';

    // campos bd
    protected $fillable = [
        'cargo_nombre',
        'cargo_area',
    ];
}

// id, titulo, contenido, resumen, miniatura, area_servicio, usuario_id_autor, estado, fecha_creacion, fecha_publicacion