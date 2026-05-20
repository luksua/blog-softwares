<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RevisionObservacion extends Model
{
    protected $table = 'actualizaciones_blog_revision_observaciones';

    protected $fillable = [
        'actualizacion_id',
        'usuario_id_supervisor',
        'observacion',
        'estado_anterior',
        'estado_nuevo',
    ];

    public function actualizacion(): BelongsTo
    {
        return $this->belongsTo(UpdateBlog::class, 'actualizacion_id', 'id');
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(UsuarioIntranet::class, 'usuario_id_supervisor', 'usuario_id');
    }
}
