<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class Area extends Model
{
    use HasFactory;

    // nombre de la tabla en la bd
    protected $table = 'area_servicio';

    // llave primaria
    protected $primaryKey = 'area_servicio_id';

    // campos bd
    protected $fillable = [
        'area_servicio_nombre',
        'area_servicio_correo',
    ];

    public function getAreaServicioNombreAttribute($value)
    {
        if (!$value) {
            return null;
        }

        // 1. Pasar todo a minúsculas
        $texto = mb_strtolower(trim($value), 'UTF-8');

        // 2. Poner solo la primera letra en mayúscula
        $texto = Str::ucfirst($texto);

        // 3. Excepciones que deben ir en mayúscula
        $excepciones = [
            'uci' => 'UCI',
        ];

        foreach ($excepciones as $buscar => $reemplazar) {
            $texto = preg_replace(
                '/\b' . preg_quote($buscar, '/') . '\b/ui',
                $reemplazar,
                $texto
            );
        }

        return $texto;
    }
}
