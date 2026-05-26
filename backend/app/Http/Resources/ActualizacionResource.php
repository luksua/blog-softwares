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
            'actualizacion_usuario_id_autor' => $this->actualizacion_usuario_id_autor,

            'actualizacion_contenido_html' => $this->when(
                $this->resource->wasRecentlyCreated === false && request()->routeIs('actualizaciones.show'),
                fn() => $renderer->render($this->actualizacion_contenido)
            ),

            'actualizacion_area_servicio_id' => $this->actualizacion_area_servicio_id,
            'actualizacion_categoria_id' => $this->actualizacion_categoria_id,

            'usuario_autor' => $this->whenLoaded('usuarioAutor', function () {
                return [
                    'usuario_id' => $this->usuarioAutor?->usuario_id,
                    'usuario_usuario' => $this->usuarioAutor?->usuario_usuario,
                    'usuario_login' => $this->usuarioAutor?->usuario_login,
                    'usuario_nombre' => $this->usuarioAutor?->usuario_nombre,
                    'usuario_nombre1' => $this->usuarioAutor?->usuario_nombre1,
                    'usuario_nombre2' => $this->usuarioAutor?->usuario_nombre2,
                    'usuario_apellido1' => $this->usuarioAutor?->usuario_apellido1,
                    'usuario_apellido2' => $this->usuarioAutor?->usuario_apellido2,
                    'area_servicio_id' => $this->usuarioAutor?->area_servicio_id,
                ];
            }),

            'revision_observacion' => $this->whenLoaded('ultimaRevisionObservacion', function () {
                return [
                    'id' => $this->ultimaRevisionObservacion?->id,
                    'observacion' => $this->ultimaRevisionObservacion?->observacion,
                    'estado_anterior' => $this->ultimaRevisionObservacion?->estado_anterior,
                    'estado_nuevo' => $this->ultimaRevisionObservacion?->estado_nuevo,
                    'created_at' => $this->ultimaRevisionObservacion?->created_at,
                    'supervisor' => $this->ultimaRevisionObservacion?->relationLoaded('supervisor') ? [
                        'usuario_id' => $this->ultimaRevisionObservacion?->supervisor?->usuario_id,
                        'usuario_usuario' => $this->ultimaRevisionObservacion?->supervisor?->usuario_usuario,
                        'usuario_nombre' => $this->ultimaRevisionObservacion?->supervisor?->usuario_nombre,
                        'usuario_nombre1' => $this->ultimaRevisionObservacion?->supervisor?->usuario_nombre1,
                        'usuario_nombre2' => $this->ultimaRevisionObservacion?->supervisor?->usuario_nombre2,
                        'usuario_apellido1' => $this->ultimaRevisionObservacion?->supervisor?->usuario_apellido1,
                        'usuario_apellido2' => $this->ultimaRevisionObservacion?->supervisor?->usuario_apellido2,
                    ] : null,
                ];
            }),

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
