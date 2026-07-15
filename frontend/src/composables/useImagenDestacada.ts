import { onBeforeUnmount, ref } from 'vue'

export interface OpcionesImagenDestacada {
  /** Tamaño máximo permitido, en MB. Por defecto 5. */
  maxSizeMB?: number
  /** Se llama cuando el archivo elegido no pasa la validación. */
  onError?: (mensaje: string) => void
}

/**
 * Selección + preview + validación de la imagen destacada de un registro.
 * Unifica lo que antes eran dos implementaciones distintas: NewVersion.vue
 * (sin validar tamaño/tipo) y EditVersion.vue (sí validaba). Con esto,
 * ambos formularios quedan con el mismo comportamiento.
 *
 * También libera automáticamente la URL del preview (`URL.revokeObjectURL`)
 * al reemplazar la imagen o al desmontar el componente, para no dejar
 * fugas de memoria con los `ObjectURL` creados.
 */
export function useImagenDestacada(opciones: OpcionesImagenDestacada = {}) {
  const maxSizeMB = opciones.maxSizeMB ?? 5
  const maxBytes = maxSizeMB * 1024 * 1024

  const archivo = ref<File | null>(null)
  const preview = ref<string | null>(null)

  const revocarPreview = () => {
    if (preview.value) {
      URL.revokeObjectURL(preview.value)
    }
  }

  const seleccionarArchivo = (event: Event) => {
    const target = event.target as HTMLInputElement
    const file = target.files?.[0]

    if (!file) {
      archivo.value = null
      revocarPreview()
      preview.value = null
      return
    }

    if (file.size > maxBytes) {
      opciones.onError?.(`La imagen no debe superar los ${maxSizeMB}MB`)
      return
    }

    if (!file.type.startsWith('image/')) {
      opciones.onError?.('Solo se permiten archivos de imagen')
      return
    }

    revocarPreview()
    archivo.value = file
    preview.value = URL.createObjectURL(file)
  }

  const quitarImagen = () => {
    revocarPreview()
    archivo.value = null
    preview.value = null
  }

  onBeforeUnmount(() => {
    revocarPreview()
  })

  return { archivo, preview, seleccionarArchivo, quitarImagen }
}

/**
 * Resuelve la URL completa de una imagen guardada en el backend a partir
 * de la ruta relativa que devuelve la API (antes solo existía en
 * EditVersion.vue).
 */
export function resolverUrlImagen(ruta: string, backendUrl?: string): string {
  if (!ruta) return ''
  if (ruta.startsWith('http')) return ruta

  const base = backendUrl || import.meta.env.VITE_BACKEND_URL || 'http://localhost:8000'

  if (ruta.startsWith('/storage/')) return `${base}${ruta}`
  if (ruta.startsWith('storage/')) return `${base}/${ruta}`

  return `${base}/storage/${ruta}`
}
