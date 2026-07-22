import axios from './api' // ajusta al cliente que ya uses en notificaciones.ts

export interface ObservacionPendiente {
  id: number
  observacion: string | null
  estado_anterior: string | null
  estado_nuevo: string | null
  created_at: string
  actualizacion_id: number
  actualizacion: {
    id: number
    actualizacion_titulo: string
    actualizacion_estado: string
    actualizacion_version: string
  } | null
}

export const listarObservacionesPendientes = async () => {
  const { data } = await axios.get('/observaciones-pendientes')
  return data as { data: ObservacionPendiente[]; meta: { total: number } }
}

export const contarObservacionesPendientes = async () => {
  const { data } = await axios.get('/observaciones-pendientes/contador')
  return data as { data: { pendientes: number } }
}