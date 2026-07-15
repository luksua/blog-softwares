import { shallowRef } from 'vue'
import EditorJS from '@editorjs/editorjs'
import Header from '@editorjs/header'
import ImageTool from '@editorjs/image'
import ListTool from '@editorjs/list'
import api from '../api/api'

export interface OpcionesIniciarEditor {
  /** ID del elemento holder (string) o el propio elemento DOM. */
  holder: string | HTMLElement
  /** Contenido inicial (formato de EditorJS), para el caso de edición. */
  data?: any
  placeholder?: string
  headerLevels?: number[]
  defaultLevel?: number
  onReady?: () => void
  onChange?: () => void
}

/**
 * Arma el bloque `tools` de EditorJS (Header, List, Image con subida al backend)
 */
function construirHerramientas(headerLevels: number[], defaultLevel: number) {
  return {
    header: {
      class: Header as any,
      inlineToolbar: true,
      config: {
        placeholder: 'Escribe un subtítulo',
        levels: headerLevels,
        defaultLevel,
      },
    },
    list: ListTool,
    image: {
      class: ImageTool,
      config: {
        uploader: {
          async uploadByFile(file: File) {
            try {
              const formData = new FormData()
              formData.append('imagen', file)
              const respuesta = await api.post('/subir-imagen-blog', formData)
              return { success: 1, file: { url: respuesta.data.url } }
            } catch (error) {
              console.error('Error subiendo imagen:', error)
              return { success: 0 }
            }
          },
        },
      },
    },
  }
}

/**
 * Maneja el ciclo de vida de una instancia de EditorJS: crearla, y
 * destruirla de forma segura (tanto para reemplazarla por otra —caso
 * EditVersion, que recarga contenido— como al desmontar el componente).
 */
export function useEditorJS() {
  const editor = shallowRef<EditorJS | null>(null)

  async function destruir() {
    if (editor.value) {
      try {
        await editor.value.destroy()
      } catch {
        // El editor ya pudo haberse destruido (p. ej. el holder ya no existe en el DOM).
      }
      editor.value = null
    }
  }

  async function iniciar(opciones: OpcionesIniciarEditor): Promise<EditorJS> {
    await destruir()

    const instancia = new EditorJS({
      holder: opciones.holder,
      data: opciones.data,
      placeholder: opciones.placeholder,
      onReady: opciones.onReady,
      onChange: opciones.onChange,
      tools: construirHerramientas(opciones.headerLevels ?? [2, 3, 4], opciones.defaultLevel ?? 2),
    })

    editor.value = instancia
    return instancia
  }

  return { editor, iniciar, destruir }
}
