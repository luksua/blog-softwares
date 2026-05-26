export interface Notificacion {
  id: number
  usuario_id_destino: number
  usuario_id_actor: number | null
  actualizacion_id: number | null
  tipo: string
  titulo: string
  mensaje: string | null
  data?: Record<string, any> | null
  leida_en: string | null
  created_at: string
  updated_at: string
}
