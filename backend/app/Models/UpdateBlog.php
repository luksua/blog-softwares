<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class UpdateBlog extends Model
{
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
        'actualizacion_fecha_publicacion',
        'actualizacion_categoria_id', // compatibilidad: primera categoría seleccionada
        'actualizacion_lecturas',
    ];

    protected $casts = [
        'actualizacion_fecha_publicacion' => 'datetime',
        'actualizacion_activo' => 'boolean',
        'actualizacion_lecturas' => 'integer',
    ];

    public function imagenes(): HasMany
    {
        return $this->hasMany(PhotoUpdate::class, 'actualizacion_id');
    }

    public function usuarioAutor(): BelongsTo
    {
        return $this->belongsTo(UsuarioIntranet::class, 'actualizacion_usuario_id_autor', 'usuario_id');
    }

    public function revisionObservaciones(): HasMany
    {
        return $this->hasMany(RevisionObservacion::class, 'actualizacion_id', 'id')
            ->latest('created_at');
    }

    public function ultimaRevisionObservacion(): HasOne
    {
        return $this->hasOne(RevisionObservacion::class, 'actualizacion_id', 'id')
            ->latestOfMany();
    }

    public function bookmarks(): HasMany
    {
        return $this->hasMany(Bookmark::class, 'actualizacion_id', 'id');
    }

    public function areaServicio(): BelongsTo
    {
        return $this->belongsTo(Area::class, 'actualizacion_area_servicio_id');
    }

    /**
     * Categoría principal/legacy. Se conserva para no romper pantallas existentes.
     */
    public function categoria(): BelongsTo
    {
        return $this->belongsTo(
            Category::class,
            'actualizacion_categoria_id',
            'categoria_actualizacion_id'
        );
    }

    /**
     * Un registro puede tener de 1 a 3 categorías.
     */
    public function categorias()
    {
        return $this->belongsToMany(
            Category::class,
            'actualizaciones_blog_categorias',
            'actualizacion_id',
            'categoria_actualizacion_id',
            'id',
            'categoria_actualizacion_id'
        )->withTimestamps('created_at', 'updated_at');
    }
}
