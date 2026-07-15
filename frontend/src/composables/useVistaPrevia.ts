import edjsHTML from 'editorjs-html'

/**
 * Convierte la salida de EditorJS (`{ blocks: [...] }`) al mismo HTML que
 * después renderiza la vista pública del registro (ver
 * `components/home/ListVersion.vue`, que pinta `actualizacion_contenido_html`
 * dentro de un contenedor `.editorjs-editor`).
 *
 * En la vista pública ese HTML lo genera el backend al publicar. Acá, como
 * el registro todavía no se guardó, lo generamos en el cliente con
 * `editorjs-html` para poder mostrar una vista previa fiel sin necesidad de
 * persistir nada todavía.
 */
export function useVistaPrevia() {
  const parser = edjsHTML()

  /**
   * @param outputData Resultado de `editor.save()` (objeto con `blocks`).
   * @returns HTML listo para inyectar en un contenedor `.editorjs-editor`.
   */
  const generarHtmlContenido = (outputData: { blocks: any[] } | null | undefined): string => {
    if (!outputData || !Array.isArray(outputData.blocks) || outputData.blocks.length === 0) {
      return ''
    }

    try {
      const resultado = parser.parse(outputData as any)
      // Según la versión, `parse` puede devolver un string o un array de strings por bloque.
      return Array.isArray(resultado) ? resultado.join('') : String(resultado)
    } catch (error) {
      console.error('Error al generar la vista previa del contenido:', error)
      return '<p class="vista-previa-error">No se pudo generar la vista previa del contenido.</p>'
    }
  }

  return { generarHtmlContenido }
}
