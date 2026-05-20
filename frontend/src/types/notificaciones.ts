export interface Notificacion {
  id: number
  usuario_id: number
  actualizacion_id: number | null
  creado_por_usuario_id: number | null
  tipo: string
  titulo: string
  mensaje: string | null
  leido: boolean
  leido_at: string | null
  created_at: string
  updated_at: string
}