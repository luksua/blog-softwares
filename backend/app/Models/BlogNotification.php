<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlogNotification extends Model
{
    protected $table = 'actualizaciones_blog_notificaciones';

    protected $fillable = [
        'usuario_id_destino',
        'usuario_id_actor',
        'actualizacion_id',
        'tipo',
        'titulo',
        'mensaje',
        'data',
        'leida_en',
    ];

    protected $casts = [
        'data' => 'array',
        'leida_en' => 'datetime',
    ];

    public function actualizacion(): BelongsTo
    {
        return $this->belongsTo(UpdateBlog::class, 'actualizacion_id', 'id');
    }

    public function usuarioDestino(): BelongsTo
    {
        return $this->belongsTo(UsuarioIntranet::class, 'usuario_id_destino', 'usuario_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(UsuarioIntranet::class, 'usuario_id_actor', 'usuario_id');
    }

    public function scopeNoLeidas($query)
    {
        return $query->whereNull('leida_en');
    }
}
