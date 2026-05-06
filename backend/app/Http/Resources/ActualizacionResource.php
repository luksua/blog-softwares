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
            'actualizacion_fecha_creacion' => $this->actualizacion_fecha_creacion,
            'actualizacion_imagen_destacada' => $this->actualizacion_imagen_destacada,
            'actualizacion_resumen' => $this->actualizacion_resumen,
            'actualizacion_contenido' => $this->actualizacion_contenido,
            'actualizacion_estado' => $this->actualizacion_estado,

            'actualizacion_contenido_html' => $renderer->render($this->actualizacion_contenido),

            'actualizacion_area_servicio_id' => $this->actualizacion_area_servicio_id,
            'actualizacion_categoria_id' => $this->actualizacion_categoria_id,

            'area_servicio' => $this->whenLoaded('areaServicio', function () {
                return [
                    'area_servicio_id' => $this->areaServicio?->area_servicio_id,
                    'area_servicio_nombre' => $this->areaServicio?->area_servicio_nombre,
                    'area_servicio_correo' => $this->areaServicio?->area_servicio_correo,
                ];
            }),

            'categoria' => $this->whenLoaded('categoria', function () {
                return [
                    'categoria_actualizacion_id' => $this->categoria?->categoria_actualizacion_id,
                    'categoria_actualizacion_nombre' => $this->categoria?->categoria_actualizacion_nombre,
                ];
            }),
        ];
    }
}