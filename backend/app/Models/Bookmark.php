<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Bookmark extends Model
{
    protected $table = 'actualizaciones_blog_guardados';

    protected $fillable = [
        'usuario_id',
        'actualizacion_id',
    ];

    protected $casts = [
        'usuario_id' => 'integer',
        'actualizacion_id' => 'integer',
    ];

    public function actualizacion(): BelongsTo
    {
        return $this->belongsTo(UpdateBlog::class, 'actualizacion_id', 'id');
    }
}
