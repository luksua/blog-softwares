<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Job extends Model
{
    // llave primaria
    protected $primaryKey = 'cargo_id';

    // campos bd
    protected $fillable = [
        'cargo_nombre',
        'cargo_area',
    ];
}
