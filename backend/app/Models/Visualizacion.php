<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Visualizacion extends Model
{
    protected $table = 'actualizaciones_blog_visualizaciones';

    public $timestamps = false;

    protected $fillable = [
        'actualizacion_id',
        'usuario_id',
        'area_servicio_id',
        'visualizado_at',
        'ip',
        'user_agent',
    ];

    protected $casts = [
        'visualizado_at' => 'datetime',
    ];
}