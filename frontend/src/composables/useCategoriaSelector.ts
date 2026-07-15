import { computed, onBeforeUnmount, onMounted, ref, type Ref } from 'vue'
import type { Category } from '../types/categorias'

/**
 * Antes esta lógica (abrir/cerrar el dropdown, filtrar por búsqueda,
 * marcar/desmarcar categorías, cerrar al hacer click fuera) estaba
 * copiada casi línea por línea en NewVersion.vue y EditVersion.vue.
 * Ahora vive una sola vez acá.
 *
 * @param categoriaIds  Ref al array de IDs seleccionados (usar `toRef(registro, 'actualizacion_categoria_ids')`
 *                       o `toRef(form, 'categoria_ids')` para pasar una propiedad de un `reactive()`).
 * @param listaCategorias Ref con el catálogo completo de categorías disponibles.
 * @param opciones.maxSeleccion Máximo de categorías que se pueden elegir a la vez (por defecto 3).
 * @param opciones.onMaxSeleccionAlcanzado Callback opcional cuando se intenta superar el máximo (p. ej. mostrar un toast).
 *
 * El composable expone `wrapperRef`: hay que asignarlo con `ref="..."`
 * (con el alias que se le dé al desestructurar) al div contenedor del
 * dropdown en el template, así se detecta un click fuera sin depender
 * de que la clase CSS del wrapper sea única en toda la página.
 */
export function useCategoriaSelector(
  categoriaIds: Ref<number[]>,
  listaCategorias: Ref<Category[]>,
  opciones: {
    maxSeleccion?: number
    onMaxSeleccionAlcanzado?: () => void
  } = {}
) {
  const maxSeleccion = opciones.maxSeleccion ?? 3

  const selectAbierto = ref(false)
  const busquedaCategoria = ref('')
  const wrapperRef = ref<HTMLElement | null>(null)

  const categoriasFiltradas = computed(() => {
    const categorias = listaCategorias.value || []

    if (!busquedaCategoria.value.trim()) return categorias

    const busqueda = busquedaCategoria.value.toLowerCase().trim()
    return categorias.filter((cat) =>
      String(cat.categoria_actualizacion_nombre || '').toLowerCase().includes(busqueda)
    )
  })

  const categoriasSeleccionadas = computed(() => {
    return categoriaIds.value
      .map((id) => {
        const categoria = listaCategorias.value.find(
          (c) => Number(c.categoria_actualizacion_id) === id
        )
        return categoria
          ? { id: Number(categoria.categoria_actualizacion_id), nombre: categoria.categoria_actualizacion_nombre }
          : null
      })
      .filter((c): c is { id: number; nombre: string } => c !== null)
  })

  const categoriaSeleccionada = (categoriaId: number) => {
    return categoriaIds.value.includes(categoriaId)
  }

  const toggleCategoria = (categoriaId: number) => {
    const index = categoriaIds.value.indexOf(categoriaId)

    if (index > -1) {
      categoriaIds.value.splice(index, 1)
      return
    }

    if (categoriaIds.value.length < maxSeleccion) {
      categoriaIds.value.push(categoriaId)
    } else {
      opciones.onMaxSeleccionAlcanzado?.()
    }
  }

  const toggleSelect = () => {
    selectAbierto.value = !selectAbierto.value
    if (!selectAbierto.value) {
      busquedaCategoria.value = ''
    }
  }

  const cerrarSelectAlClickFuera = (event: MouseEvent) => {
    const target = event.target as Node
    if (wrapperRef.value && !wrapperRef.value.contains(target)) {
      selectAbierto.value = false
      busquedaCategoria.value = ''
    }
  }

  onMounted(() => {
    document.addEventListener('click', cerrarSelectAlClickFuera)
  })

  onBeforeUnmount(() => {
    document.removeEventListener('click', cerrarSelectAlClickFuera)
  })

  return {
    wrapperRef,
    selectAbierto,
    busquedaCategoria,
    categoriasFiltradas,
    categoriasSeleccionadas,
    categoriaSeleccionada,
    toggleCategoria,
    toggleSelect,
  }
}

/**
 * Normaliza cualquier valor recibido (un id suelto, un array, strings, etc.)
 * a un array de IDs numéricos válidos, sin duplicados y limitado a `max`.
 */
export function normalizarCategoriaIds(valor: any, max = 3): number[] {
  const ids = Array.isArray(valor) ? valor : valor ? [valor] : []

  return ids
    .map((id) => Number(id))
    .filter((id, index, array) => Number.isFinite(id) && id > 0 && array.indexOf(id) === index)
    .slice(0, max)
}

