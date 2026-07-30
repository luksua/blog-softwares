import type { OutputBlockData } from '@editorjs/editorjs'

export type DatosIniciales = {
    titulo?: string
    version?: string
    resumen?: string
    area_servicio_id?: number | string
    actualizacion_categoria_ids?: number[]
    contenidoBlocks?: OutputBlockData[]
}

export type BorradorRegistro = {
    titulo?: string
    version?: string
    resumen?: string
    area_servicio_id?: number | string
    actualizacion_categoria_ids?: number[]
    actualizacion_categoria_id?: number | number[]
    estado?: string
    fecha_publicacion?: string
    fecha_programada?: string
    editorBlocks?: OutputBlockData[]
    guardadoEn?: string
}

export interface Heading {
  id: string
  text: string
  level: number
}

export interface DetalleRegistroVisualExpuesto {
  contenidoRef?:
    | HTMLElement
    | {
        value: HTMLElement | null
      }
    | null
}

export interface ModoVista {
  id: 'desktop' | 'tablet' | 'movil'
  etiqueta: string
  icono: string
  ancho: number
}

export type DatosDuplicadoEntrada = {
  titulo?: string
  resumen?: string
  area_servicio_id?: number | string
  actualizacion_categoria_ids?: number[]
  contenidoBlocks?: unknown
}

export type DatosDuplicado = {
  titulo?: string
  resumen?: string
  area_servicio_id?: number | string
  actualizacion_categoria_ids?: number[]
  contenidoBlocks?: OutputBlockData[]
}
