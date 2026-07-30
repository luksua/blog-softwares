export interface LecturaReciente {
  id: number
  actualizacion_titulo: string
  actualizacion_resumen: string | null
  actualizacion_imagen_destacada: string | null
  actualizacion_version: string | null
  actualizacion_fecha_publicacion: string | null
  ultima_visualizacion: string
  veces_visualizado: number
}
