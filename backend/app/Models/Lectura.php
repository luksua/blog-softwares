<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LecturaBlog extends Model
{
    protected $table = 'actualizaciones_blog_lecturas';
    public $timestamps = false; // solo created_at, manejado manualmente

    protected $fillable = [
        'actualizacion_id',
        'usuario_id',
    ];

    protected $attributes = [];

    public function actualizacion(): BelongsTo
    {
        return $this->belongsTo(UpdateBlog::class, 'actualizacion_id', 'id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(UsuarioIntranet::class, 'usuario_id', 'usuario_id');
    }
}