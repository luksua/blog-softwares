import api from './api'

export interface BlogNotification {
  id: number
  tipo: string
  titulo: string
  mensaje: string | null
  data: Record<string, any> | null
  leida_en: string | null
  created_at: string
  actualizacion_id: number | null
  actualizacion?: {
    id: number
    actualizacion_titulo?: string
    actualizacion_estado?: string
    actualizacion_version?: string
    actualizacion_usuario_id_autor?: number
  } | null
  actor?: Record<string, any> | null
}

export async function listarNotificaciones(perPage = 10) {
  const response = await api.get('/notificaciones', {
    params: {
      per_page: perPage,
    },
  })

  return response.data
}

export async function listarObservaciones() {
  const response = await api.get('/notificaciones/observaciones')

  return response.data
}

export async function obtenerContadorNotificaciones() {
  const response = await api.get('/notificaciones/contador')

  return Number(response.data?.data?.no_leidas ?? 0)
}

export async function marcarNotificacionLeida(id: number) {
  const response = await api.post(`/notificaciones/${id}/leida`)

  return response.data
}

export async function marcarTodasNotificacionesLeidas() {
  const response = await api.post('/notificaciones/marcar-todas-leidas')

  return response.data
}
