<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Category extends Model
{
    protected $table = 'act_categorias';

    protected $primaryKey = 'categoria_actualizacion_id';

    protected $fillable = [
        'categoria_actualizacion_nombre',
        'categoria_actualizacion_activa',
        'categoria_actualizacion_orden',
        'categoria_actualizacion_icono',
    ];

    public function actualizaciones()
    {
        return $this->belongsToMany(
            UpdateBlog::class,
            'actualizaciones_blog_categorias',
            'categoria_actualizacion_id',
            'actualizacion_id',
            'categoria_actualizacion_id',
            'id'
        )->withTimestamps('created_at', 'updated_at');
    }
}
