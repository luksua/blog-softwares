<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class UsuarioIntranet extends Authenticatable
{
    protected $connection = 'asotraum_calidad';

    protected $table = 'usuario';

    protected $primaryKey = 'usuario_id';

    public $timestamps = false;

    protected $hidden = [
        'usuario_clave',
        'remember_token',
    ];

    protected $fillable = [
        'usuario_usuario',
        'usuario_nombre1',
        'usuario_nombre2',
        'usuario_apellido1',
        'usuario_apellido2',
        'usuario_grupo',
        'cargo_id',
        'area_servicio_id',
    ];

    public function esAdmin(): bool
    {
        return strtoupper((string) $this->usuario_grupo) === 'ADMIN';
    }

    public function esJefeArea(): bool
    {
        return $this->jefaturasActivas()->exists();
    }

    public function areasSupervisadas()
    {
        return $this->jefaturasActivas()
            ->pluck('jefe_area')
            ->filter()
            ->map(fn ($areaId) => (int) $areaId)
            ->unique()
            ->values();
    }

    public function areaServicio()
    {
        return $this->belongsTo(
            Area::class,
            'area_servicio_id',
            'area_servicio_id'
        );
    }

    public function jefaturas()
    {
        return $this->hasMany(
            JefeArea::class,
            'id_usuario',
            'usuario_id'
        );
    }

    public function jefaturasActivas()
    {
        return $this->jefaturas()->activos();
    }
}
