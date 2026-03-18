<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Update extends Model
{
    //
    protected $primaryKey = 'id';

    protected $fillable = [
        'update_titulo',
        'update_contenido',
        'update_resumen',
        'update_imagen_destacada',
        'update_area_servicio_id',
        'update_estado',
        'update_fecha_creacion',
        'update_fecha_publicacion',
        'update_usuario_id_autor',
    ];
}
