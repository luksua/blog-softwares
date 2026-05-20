<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $table = 'actualizaciones_blog_notificaciones';

    protected $fillable = [
        'usuario_id',
        'actualizacion_id',
        'creado_por_usuario_id',
        'tipo',
        'titulo',
        'mensaje',
        'leido',
        'leido_at',
    ];

    protected $casts = [
        'leido' => 'boolean',
        'leido_at' => 'datetime',
    ];
} 