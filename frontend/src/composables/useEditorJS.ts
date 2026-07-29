import { shallowRef } from 'vue'

import EditorJS, {
  type EditorConfig,
  type OutputData,
} from '@editorjs/editorjs'

import Header from '@editorjs/header'
import ImageTool from '@editorjs/image'
import ListTool from '@editorjs/list'

import api from '../api/api'

export interface OpcionesIniciarEditor {
  holder: string | HTMLElement
  data?: OutputData
  placeholder?: string
  headerLevels?: number[]
  defaultLevel?: number
  onReady?: () => void | Promise<void>
  onChange?: () => void | Promise<void>
}

function construirHerramientas(
  headerLevels: number[],
  defaultLevel: number,
): EditorConfig['tools'] {
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

    list: {
      class: ListTool as any,
      inlineToolbar: true,

      config: {
        defaultStyle: 'unordered',
      },
    },

    image: {
      class: ImageTool as any,

      config: {
        uploader: {
          async uploadByFile(file: File) {
            try {
              const formData = new FormData()

              formData.append(
                'imagen',
                file,
              )

              const respuesta =
                await api.post(
                  '/subir-imagen-blog',
                  formData,
                  {
                    headers: {
                      'Content-Type':
                        'multipart/form-data',
                    },
                  },
                )

              return {
                success: 1,

                file: {
                  url:
                    respuesta.data.url,
                },
              }
            } catch (error) {
              console.error(
                'Error subiendo imagen:',
                error,
              )

              return {
                success: 0,
              }
            }
          },
        },
      },
    },
  }
}

export function useEditorJS() {
  const editor =
    shallowRef<EditorJS | null>(null)

  const destruir =
    async (): Promise<void> => {
      const instanciaActual =
        editor.value

      editor.value = null

      if (!instanciaActual) {
        return
      }

      try {
        await instanciaActual.isReady
      } catch {
        /*
         * La inicialización pudo fallar.
         */
      }

      try {
        instanciaActual.destroy()
      } catch (error) {
        console.warn(
          'No se pudo destruir EditorJS:',
          error,
        )
      }
    }

  const iniciar = async (
    opciones: OpcionesIniciarEditor,
  ): Promise<EditorJS> => {
    await destruir()

    /*
     * Convertimos los datos a JSON plano para evitar
     * que EditorJS reciba Proxy de Vue.
     */
    const dataPlano: OutputData =
      JSON.parse(
        JSON.stringify(
          opciones.data ?? {
            blocks: [],
          },
        ),
      )

    const instancia =
      new EditorJS({
        holder: opciones.holder,

        data: dataPlano,

        placeholder:
          opciones.placeholder,

        tools:
          construirHerramientas(
            opciones.headerLevels ??
              [2, 3, 4],

            opciones.defaultLevel ??
              2,
          ),

        onReady: () => {
          void opciones.onReady?.()
        },

        onChange: () => {
          void opciones.onChange?.()
        },
      })

    editor.value = instancia

    try {
      await instancia.isReady
      return instancia
    } catch (error) {
      if (
        editor.value ===
        instancia
      ) {
        editor.value = null
      }

      console.error(
        'Error inicializando EditorJS:',
        error,
      )

      throw error
    }
  }

  return {
    editor,
    iniciar,
    destruir,
  }
}