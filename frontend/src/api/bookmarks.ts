import api from './api'
import type { Version } from '../types/version'

function normalizarIds(payload: unknown): number[] {
  const data = (payload as { data?: unknown })?.data ?? payload

  if (!Array.isArray(data)) {
    return []
  }

  return data
    .map((id) => Number(id))
    .filter((id) => Number.isFinite(id))
}

function normalizarActualizaciones(payload: unknown): Version[] {
  const data = (payload as { data?: unknown })?.data ?? payload

  if (!Array.isArray(data)) {
    return []
  }

  return data.filter((item): item is Version => Boolean(item && typeof item === 'object' && 'id' in item))
}

export async function obtenerIdsBookmarks(): Promise<number[]> {
  const response = await api.get('/bookmarks/ids')
  return normalizarIds(response.data)
}

export async function obtenerBookmarks(): Promise<Version[]> {
  const response = await api.get('/bookmarks')
  return normalizarActualizaciones(response.data)
}

export async function guardarBookmark(id: number): Promise<void> {
  await api.post(`/bookmarks/${id}`)
}

export async function quitarBookmark(id: number): Promise<void> {
  await api.delete(`/bookmarks/${id}`)
}

export async function limpiarBookmarks(): Promise<void> {
  await api.delete('/bookmarks')
}
