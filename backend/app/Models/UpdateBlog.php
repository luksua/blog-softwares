<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UpdateBlog extends Model
{
    // nombre de la tabla en la bd
    protected $table = 'actualizaciones_blog';

    const UPDATED_AT = null;

    const CREATED_AT = 'actualizacion_fecha_creacion';

    protected $primaryKey = 'id';

    protected $fillable = [
        'actualizacion_titulo',
        'actualizacion_version',
        'actualizacion_contenido',
        'actualizacion_resumen',
        'actualizacion_imagen_destacada',
        'actualizacion_area_servicio_id',
        'actualizacion_usuario_id_autor',
        'actualizacion_estado',
        // 'actualizacion_fecha_creacion',
        // 'actualizacion_fecha_actualizacion',
        'actualizacion_fecha_publicacion',
        
    ];

    public function imagenes()
    {
        return $this->hasMany(PhotoUpdate::class, 'actualizacion_id');
    }
}