<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class UsuarioIntranet extends Authenticatable
{
    protected $connection = 'asotraum_calidad';

    protected $table = 'usuario';

    protected $primaryKey = 'usuario_id';

    public $timestamps = false;

    protected $fillable = [

        'usuario_usuario',

        'usuario_nombre1',

        'usuario_nombre2',

        'usuario_apellido1',

        'usuario_apellido2',

        'usuario_grupo',

        'cargo_id',

        'area_servicio_id'
    ];
}