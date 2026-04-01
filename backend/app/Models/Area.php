<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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
}
