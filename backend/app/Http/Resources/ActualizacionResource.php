<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Services\EditorJsRenderer;

class ActualizacionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $renderer = app(EditorJsRenderer::class);

        return [
            'id' => $this->id,
            'actualizacion_version' => $this->actualizacion_version,
            'actualizacion_titulo' => $this->actualizacion_titulo,
            'actualizacion_fecha_publicacion' => $this->actualizacion_fecha_publicacion,
            'actualizacion_imagen_destacada' => $this->actualizacion_imagen_destacada,
            'actualizacion_resumen' => $this->actualizacion_resumen,

            // JSON original, por si lo necesitas
            'actualizacion_contenido' => $this->actualizacion_contenido,

            // HTML listo para Vue
            'actualizacion_contenido_html' => $renderer->render($this->actualizacion_contenido),
        ];
    }
}