<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UpdateBlog extends Model
{
    // nombre de la tabla en la bd
    protected $table = 'actualizaciones_blog';

    const CREATED_AT = 'fecha_creacion';

    protected $primaryKey = 'id';

    protected $fillable = [
        'update_titulo',
        'update_contenido',
        'update_resumen',
        'update_imagen_destacada',
        'update_area_servicio_id',
        'update_estado',
        'update_fecha_creacion',
        'update_fecha_actualizacion',
        'update_fecha_publicacion',
        'update_usuario_id_autor',
    ];

    public function photos()
    {
        return $this->hasMany(PhotoUpdate::class, 'actualizacion_id');
    }
}
