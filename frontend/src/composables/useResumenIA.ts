import { ref } from 'vue'
import api from '../api/api'
import { toast } from 'vue-sonner'

/**
 * Composable para generar el resumen de una publicación usando IA,
 * a partir del contenido actual del editor (EditorJS).
 */
export function useResumenIA() {
    const generandoResumen = ref(false)

    /**
     * @param editorInstance Instancia activa de EditorJS (editor.value)
     * @param titulo Título actual del registro, para dar contexto a la IA
     * @returns El resumen generado, o null si hubo un error o falta contenido
     */
    const generarResumen = async (
        editorInstance: any,
        titulo: string = ''
    ): Promise<string | null> => {
        if (!editorInstance) {
            toast.warning('El editor todavía no está listo.')
            return null
        }

        let outputData
        try {
            outputData = await editorInstance.save()
        } catch (error) {
            console.error('Error al leer el contenido del editor:', error)
            toast.warning('No se pudo leer el contenido para generar el resumen.')
            return null
        }

        if (!outputData?.blocks?.length) {
            toast.warning('Escribe contenido antes de generar el resumen.')
            return null
        }

        generandoResumen.value = true

        try {
            const respuesta = await api.post('/actualizaciones/generar-resumen', {
                contenido: outputData,
                titulo,
            })

            return respuesta.data?.resumen ?? null
        } catch (error: any) {
            const mensaje =
                error?.response?.data?.error ||
                'No se pudo generar el resumen con IA. Intenta nuevamente.'
            toast.warning(mensaje)
            return null
        } finally {
            generandoResumen.value = false
        }
    }

    return { generandoResumen, generarResumen }
}