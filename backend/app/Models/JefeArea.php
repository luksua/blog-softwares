<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JefeArea extends Model
{
    protected $connection = 'asotraum_calidad';

    protected $table = 'jefes';

    protected $primaryKey = 'jefe_id';

    public $timestamps = false;

    protected $fillable = [
        'id_usuario',
        'jefe_area',
        'jefe_correo',
        'jefe_wp_estado',
        'jefe_estado',
    ];

    public function scopeActivos($query)
    {
        return $query->where('jefe_estado', 'A');
    }

    public function usuario()
    {
        return $this->belongsTo(
            UsuarioIntranet::class,
            'id_usuario',
            'usuario_id'
        );
    }

    public function areaServicio()
    {
        return $this->belongsTo(
            Area::class,
            'jefe_area',
            'area_servicio_id'
        );
    }
}
