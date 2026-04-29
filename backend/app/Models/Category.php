<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    // nombre de la tabla en la bd
    protected $table = 'act_categorias';

    // llave primaria
    protected $primaryKey = 'categoria_id';

    // campos bd
    protected $fillable = [
        'categoria_nombre',
        'categoria_estado',
    ];
}
