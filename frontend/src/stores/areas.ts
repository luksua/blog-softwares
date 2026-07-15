import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '../api/api'
import type { Area } from '../types/areas'

export const useAreasStore = defineStore('areas', () => {
  const areas = ref<Area[]>([])
  const loading = ref(false)
  const error = ref('')
  const loaded = ref(false)

  // Promesa en curso: si dos componentes piden fetchAreas() casi al
  // mismo tiempo (p. ej. List.vue y NewVersion.vue montando juntos),
  // el segundo espera la misma petición en vez de disparar otra.
  let fetchEnCurso: Promise<void> | null = null

  // force=true ignora la caché y vuelve a pedir al backend
  const fetchAreas = async (force = false) => {
    if (loaded.value && !force) return
    if (fetchEnCurso && !force) return fetchEnCurso

    loading.value = true
    error.value = ''

    fetchEnCurso = (async () => {
      try {
        const res = await api.get('/area-servicio')

        areas.value = Array.isArray(res.data?.data)
          ? res.data.data
          : Array.isArray(res.data)
            ? res.data
            : []

        loaded.value = true
      } catch (err) {
        error.value = 'Error al cargar las áreas de servicio.'
        console.error('Error al cargar áreas:', err)
      } finally {
        loading.value = false
        fetchEnCurso = null
      }
    })()

    return fetchEnCurso
  }

  return { areas, loading, error, loaded, fetchAreas }
})