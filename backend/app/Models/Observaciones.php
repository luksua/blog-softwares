<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Observaciones extends Model
{
    protected $table = 'actualizaciones_blog_revision_observaciones';

    protected $fillable = [
        'usuario_id_supervisor',
        'actualizacion_id',
        'observacion',
        'estado_anterior',
        'estado_nuevo',
    ];

    public function actualizacion(): BelongsTo
    {
        return $this->belongsTo(UpdateBlog::class, 'actualizacion_id', 'id');
    }

    public function usuarioSupervisor(): BelongsTo
    {
        return $this->belongsTo(JefeArea::class, 'usuario_id_supervisor', 'jefe_id');
    }

    public function scopePendientesDe(Builder $query, int $usuarioAutorId): Builder
    {
        return $query->whereHas('actualizacion', function (Builder $q) use ($usuarioAutorId) {
            $q->where('actualizacion_estado', 'revision')
                ->where('actualizacion_usuario_id_autor', $usuarioAutorId);
        });
    }
}