<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PhotoUpdate extends Model
{
    // nombre exacto de la tabla
    protected $table = 'imagenes_actualizaciones';

    protected $fillable = [
        'actualizacion_id',
        'ruta_imagen'
    ];

    public function actualizacion()
    {
        // El segundo parámetro es la llave foránea en esta tabla
        return $this->belongsTo(UpdateBlog::class, 'actualizacion_id');
    }
}
