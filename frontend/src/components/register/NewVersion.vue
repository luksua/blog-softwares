<template>
  <div class="new-version-container">
    <form @submit.prevent="guardarRegistro" class="form-container-x">
      <!-- COLUMNA IZQUIERDA: campos del formulario -->
      <div class="left-column">
        <!-- Barra de autosave -->
        <div class="autosave-bar">
          <div class="autosave-estado">
            <span v-if="autoguardando" class="autosave-guardando">
              <span class="autosave-dot pulsando"></span>
              Guardando borrador...
            </span>

            <span v-else-if="ultimoGuardado" class="autosave-ok">
              <span class="autosave-dot ok"></span>
              Borrador guardado a las {{ ultimoGuardado }}
            </span>

            <span v-else class="autosave-vacio">
              El borrador se guardará automáticamente
            </span>
          </div>

          <button v-if="hayBorradorGuardado" type="button" class="btn-descartar"
            @click="descartarBorrador(true); limpiarFormulario()">
            Descartar borrador
          </button>
        </div>

        <!-- Título -->
        <div class="form-group">
          <label for="titulo" class="form-label fw-bold text-primary">
            Título <span class="text-danger">*</span>
          </label>

          <input ref="tituloInput" type="text" id="titulo" class="form-control" v-model="registro.titulo"
            placeholder="Ingrese el título de la actualización" required />
        </div>

        <!-- Versión y portada -->
        <div class="row">
          <div class="col-12 col-sm-6 mb-3">
            <label for="version" class="form-label fw-bold text-primary">
              Número de Versión
            </label>

            <input type="text" id="version" class="form-control" v-model="registro.version" placeholder="Ej: 1.0.0"
              required />
          </div>

          <div class="col-12 col-sm-6 mb-3">
            <label for="miniatura" class="form-label fw-bold text-primary">
              Portada
            </label>

            <input type="file" id="miniatura" class="form-control" accept="image/*" @change="manejarArchivoMiniatura" />

            <div v-if="previewMiniatura" class="mt-2 text-center">
              <img :src="previewMiniatura" alt="Vista previa" class="img-thumbnail"
                style="max-height: 120px; border-radius: 8px;" />
            </div>
          </div>
        </div>

        <!-- Resumen -->
        <div class="form-group">
          <label for="resumen" class="form-label fw-bold text-primary">
            Resumen
          </label>
          <textarea id="resumen" class="form-control" :class="{ 'is-invalid': resumenExcedeLimite }"
            v-model="registro.resumen" rows="2" placeholder="Breve descripción de la actualización..."
            required></textarea>

          <div v-if="resumenExcedeLimite" class="invalid-feedback d-block">
            El resumen no puede superar los {{ LIMITE_RESUMEN }} caracteres.
          </div>

          <div class="form-text text-end" :class="{ 'text-danger fw-bold': registro.resumen.length > 800 }">
            {{ registro.resumen.length }}/800 caracteres
          </div>
        </div>
        <!-- Categorías y Área -->
        <div class="row">
          <!-- Área -->
          <div class="col-12 col-md-6 mb-3">
            <label for="area_servicio" class="form-label fw-bold text-primary">
              Área <span class="text-danger">*</span>
            </label>

            <select id="area_servicio" class="form-select" v-model="registro.area_servicio_id" required>
              <option value="" disabled>Selecciona un área...</option>

              <option v-for="area in listaAreas" :key="area.area_servicio_id" :value="area.area_servicio_id">
                {{ area.area_servicio_nombre }}
              </option>
            </select>
          </div>
          <!-- Categorías -->
          <div class="col-12 col-md-6 mb-3">
            <label class="form-label fw-bold text-primary">
              Categorías <span class="text-danger">*</span>
              <small class="text-muted">(máximo 3)</small>
            </label>

            <div ref="categoriaSelectRef" class="categoria-select-wrapper" :class="{
              open: selectAbierto,
              'has-selection': categoriasSeleccionadas.length > 0
            }" @click.stop>
              <div class="categoria-select-trigger" @click.stop="toggleSelect">
                <div v-if="categoriasSeleccionadas.length === 0" class="select-placeholder">
                  <i class="bi bi-tag"></i>
                  <span>Selecciona hasta 3 categorías</span>
                </div>

                <div v-else class="select-selected">
                  <div class="selected-tags">
                    <span v-for="cat in categoriasSeleccionadas.slice(0, 2)" :key="cat.id" class="selected-tag">
                      {{ cat.nombre }}
                    </span>

                    <span v-if="categoriasSeleccionadas.length > 2" class="selected-more">
                      +{{ categoriasSeleccionadas.length - 2 }}
                    </span>
                  </div>
                </div>

                <i class="bi" :class="selectAbierto ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
              </div>

              <!-- Dropdown de opciones -->
              <div v-if="selectAbierto" class="categoria-select-dropdown">
                <div class="dropdown-search" v-if="listaCategorias.length > 5">
                  <i class="bi bi-search"></i>

                  <input type="text" v-model="busquedaCategoria" placeholder="Buscar categoría..." @click.stop />
                </div>

                <div class="dropdown-options">
                  <button type="button" v-for="categoria in categoriasFiltradas"
                    :key="categoria.categoria_actualizacion_id" class="dropdown-option" :class="{
                      selected: categoriaSeleccionada(Number(categoria.categoria_actualizacion_id)),
                      disabled:
                        !categoriaSeleccionada(Number(categoria.categoria_actualizacion_id)) &&
                        categoriasSeleccionadas.length >= 3
                    }" :disabled="!categoriaSeleccionada(Number(categoria.categoria_actualizacion_id)) &&
                      categoriasSeleccionadas.length >= 3
                      " @click="toggleCategoria(Number(categoria.categoria_actualizacion_id))">
                    <span class="option-name">
                      {{ categoria.categoria_actualizacion_nombre }}
                    </span>

                    <span class="option-check">
                      <i v-if="categoriaSeleccionada(Number(categoria.categoria_actualizacion_id))"
                        class="bi bi-check-lg"></i>
                    </span>
                  </button>

                  <div v-if="categoriasFiltradas.length === 0" class="dropdown-empty">
                    No se encontraron categorías
                  </div>
                </div>
              </div>
            </div>

            <!-- Contador -->
            <div class="categoria-counter">
              <span class="counter-text">
                {{ categoriasSeleccionadas.length }}/3 categorías
              </span>

              <span v-if="categoriasSeleccionadas.length >= 3" class="counter-warning">
                <i class="bi bi-exclamation-triangle-fill"></i>
                Límite alcanzado
              </span>
            </div>
          </div>
        </div>

        <!-- Estado y fecha -->
        <div class="row">
          <div class="col-12 col-md-6 mb-3">
            <label for="estado" class="form-label fw-bold text-primary">
              Estado
            </label>

            <select id="estado" class="form-select" v-model="registro.estado" required>
              <option v-for="estado in listaEstados" :key="estado.id" :value="estado.id">
                {{ estado.nombre }}
              </option>
            </select>
          </div>

          <div class="col-12 col-md-6 mb-3">
            <label v-if="esProgramado" for="fecha_programada" class="form-label fw-bold text-primary">
              Fecha y hora de publicación <span class="text-danger">*</span>
            </label>
            <label v-else for="fecha_publicacion" class="form-label fw-bold text-primary">
              Fecha
            </label>

            <input v-if="esProgramado" type="datetime-local" id="fecha_programada" class="form-control"
              v-model="registro.fecha_programada" :min="fechaMinimaProgramada" required />
            <input v-else type="date" id="fecha_publicacion" class="form-control" v-model="registro.fecha_publicacion"
              required disabled />

            <small v-if="esProgramado" class="form-text text-muted">
              El registro se publicará automáticamente en la fecha y hora indicadas.
            </small>
          </div>
        </div>

        <!-- Usuario oculto -->
        <input type="number" id="usuario_id_autor" class="form-control" v-model="registro.usuario_id_autor" disabled
          hidden required />
      </div>

      <!-- COLUMNA DERECHA: editor de contenido -->
      <div class="editor-column">
        <div class="editor-column-header">
          <label class="form-label fw-bold text-primary mb-0">
            Contenido <span class="text-danger">*</span>
          </label>

          <!-- <div class="pestanas-editor" role="tablist">
            <button type="button" class="pestana-btn" :class="{ activa: pestanaActiva === 'editor' }"
              role="tab" :aria-selected="pestanaActiva === 'editor'" @click="pestanaActiva = 'editor'">
              <i class="bi bi-pencil-square"></i>
              Editor
            </button>

            <button type="button" class="pestana-btn" :class="{ activa: pestanaActiva === 'vista-previa' }"
              role="tab" :aria-selected="pestanaActiva === 'vista-previa'" @click="abrirVistaPrevia">
              <i class="bi bi-eye"></i>
              Vista previa
            </button>
          </div> -->
        </div>

        <div class="editor-wrapper" v-show="pestanaActiva === 'editor'">
          <div id="editorjs"></div>
        </div>

        <VistaPreviaRegistro
          v-if="pestanaActiva === 'vista-previa'"
          :titulo="registro.titulo"
          :version="registro.version"
          :resumen="registro.resumen"
          :area-nombre="areaSeleccionadaNombre"
          :categorias="categoriasSeleccionadas.map(c => c.nombre)"
          :imagen-url="previewMiniatura"
          :fecha-texto="fechaTextoPreview"
          :contenido-html="contenidoPreviewHtml"
        />
      </div>
      
      <!-- Barra final de acciones: siempre al final del formulario -->
      <div class="actions">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" @click="emit('cerrar')">
          <i class="bi bi-x-circle me-1"></i>
          Cancelar
        </button>

        <button type="submit" class="btn btn-primary" :disabled="enviando">
          <span v-if="enviando" class="spinner-border spinner-border-sm me-2" role="status"></span>

          <i v-else class="bi bi-send-check me-1"></i>

          {{ enviando ? 'Guardando...' : 'Publicar Registro' }}
        </button>
      </div>
    </form>
  </div>
</template>

<script setup lang="ts">
import { reactive, ref, onMounted, onBeforeUnmount, watch, nextTick, computed, toRef } from 'vue'
import { storeToRefs } from 'pinia'
import api from '../../api/api'
import type { NewVersion } from '../../types/newVersion'
import { useAreasStore } from '../../stores/areas'
import { useCategoriasStore } from '../../stores/categorias'
import { toast } from 'vue-sonner'
import { Modal } from 'bootstrap'

import { useEditorJS } from '../../composables/useEditorJS'
import { useCategoriaSelector, normalizarCategoriaIds } from '../../composables/useCategoriaSelector'
import { useFechaProgramada } from '../../composables/useFechaProgramada'
import { useImagenDestacada } from '../../composables/useImagenDestacada'
import { useVistaPrevia } from '../../composables/useVistaPrevia'
import VistaPreviaRegistro from './VistaPreviaRegistro.vue'

const tituloInput = ref<HTMLInputElement | null>(null)

// ── Clave del borrador ────────────────────────────────────────────
const DRAFT_KEY = 'draft_nueva_actualizacion'

// ── Archivos ──────────────────────────────────────────────────────
const {
  archivo: archivoMiniatura,
  preview: previewMiniatura,
  seleccionarArchivo: manejarArchivoMiniatura,
  quitarImagen: quitarImagenMiniatura,
} = useImagenDestacada({
  onError: (mensaje) => toast.warning(mensaje),
})

// ── Registro ──────────────────────────────────────────────────────
const idUsuarioLogueado = 1
const errores = ref<Record<string, string[]>>({})

const registroVacio = () => ({
  titulo: '',
  version: '',
  contenido: '',
  resumen: '',
  imagen_destacada: '',
  area_servicio_id: '' as any,
  actualizacion_categoria_ids: [] as number[],
  usuario_id_autor: idUsuarioLogueado,
  estado: 'borrador',
  fecha_creacion: new Date().toISOString().split('T')[0],
  fecha_publicacion: new Date().toISOString().split('T')[0],
  fecha_programada: '',
  imagenes_quill: []
})

const registro = reactive<NewVersion>(registroVacio())

const esProgramado = computed(() => registro.estado === 'programado')

const { fechaMinimaProgramada, validarFechaProgramada } = useFechaProgramada()

watch(esProgramado, (activo) => {
  if (activo && !registro.fecha_programada) {
    registro.fecha_programada = fechaMinimaProgramada.value
  }
})


// ── Autosave ──────────────────────────────────────────────────────
const hayBorradorGuardado = ref(false)
const ultimoGuardado = ref<string | null>(null)
const autoguardando = ref(false)

let autoguardadoTimeout: ReturnType<typeof setTimeout> | null = null

const cargarBorrador = () => {
  try {
    const raw = localStorage.getItem(DRAFT_KEY)
    if (!raw) return

    const draft = JSON.parse(raw)

    Object.assign(registro, {
      titulo: draft.titulo || '',
      version: draft.version || '',
      resumen: draft.resumen || '',
      area_servicio_id: draft.area_servicio_id || '',
      actualizacion_categoria_ids: normalizarCategoriaIds(draft.actualizacion_categoria_ids || draft.actualizacion_categoria_id),
      estado: draft.estado || 'borrador',
      fecha_publicacion: draft.fecha_publicacion || new Date().toISOString().split('T')[0],
      fecha_programada: draft.fecha_programada || '',
    })

    ultimoGuardado.value = draft.guardadoEn || null
    hayBorradorGuardado.value = true

    if (draft.editorBlocks?.length && editorInstance.value) {
      editorInstance.value.render({ blocks: draft.editorBlocks })
    }

    // toast.info('Borrador restaurado.')
  } catch (e) {
    console.warn('No se pudo restaurar el borrador:', e)
  }
}

const LIMITE_RESUMEN = 800

const resumenExcedeLimite = computed(() => {
  return registro.resumen.length > LIMITE_RESUMEN
})

const guardarBorrador = async () => {
  autoguardando.value = true

  try {
    let editorBlocks: any[] = []

    if (editorInstance.value) {
      const output = await editorInstance.value.save()
      editorBlocks = output.blocks
    }

    const draft = {
      titulo: registro.titulo,
      version: registro.version,
      resumen: registro.resumen,
      area_servicio_id: registro.area_servicio_id,
      actualizacion_categoria_ids: registro.actualizacion_categoria_ids,
      estado: registro.estado,
      fecha_publicacion: registro.fecha_publicacion,
      fecha_programada: registro.fecha_programada,
      editorBlocks,
      guardadoEn: new Date().toLocaleTimeString('es-ES', {
        hour: '2-digit', minute: '2-digit', second: '2-digit'
      })
    }

    localStorage.setItem(DRAFT_KEY, JSON.stringify(draft))
    ultimoGuardado.value = draft.guardadoEn
    hayBorradorGuardado.value = true
  } catch (e) {
    console.warn('Error al autoguardar:', e)
  } finally {
    autoguardando.value = false
  }
}

const descartarBorrador = (mostrarToast = false) => {
  localStorage.removeItem(DRAFT_KEY)
  hayBorradorGuardado.value = false
  ultimoGuardado.value = null
  if (mostrarToast) toast.info('Borrador descartado.')
}

const programarAutosave = () => {
  if (autoguardadoTimeout) clearTimeout(autoguardadoTimeout)
  autoguardadoTimeout = setTimeout(() => {
    guardarBorrador()
  }, 1500)
}

watch(
  () => ({
    titulo: registro.titulo,
    version: registro.version,
    resumen: registro.resumen,
    area_servicio_id: registro.area_servicio_id,
    actualizacion_categoria_ids: registro.actualizacion_categoria_ids,
    estado: registro.estado,
    fecha_publicacion: registro.fecha_publicacion,
    fecha_programada: registro.fecha_programada,
  }),
  () => { programarAutosave() },
  { deep: true }
)

// ── Listas ────────────────────────────────────────────────────────
const areasStore = useAreasStore()
const categoriasStore = useCategoriasStore()
const { areas: listaAreas } = storeToRefs(areasStore)
const { categorias: listaCategorias } = storeToRefs(categoriasStore)
const listaEstados = ref<{ id: string; nombre: string }[]>([])
const enviando = ref(false)
const emit = defineEmits(['cerrar', 'recargar-lista'])

const {
  wrapperRef: categoriaSelectRef,
  selectAbierto,
  busquedaCategoria,
  categoriasFiltradas,
  categoriasSeleccionadas,
  categoriaSeleccionada,
  toggleCategoria,
  toggleSelect,
} = useCategoriaSelector(toRef(registro, 'actualizacion_categoria_ids'), listaCategorias, {
  onMaxSeleccionAlcanzado: () => toast.warning('Solo puedes seleccionar máximo 3 categorías'),
})

// ── Editor.js ─────────────────────────────────────────────────────
const { editor: editorInstance, iniciar: iniciarEditor, destruir: destruirEditor } = useEditorJS()

// ── Vista previa ──────────────────────────────────────────────────
const { generarHtmlContenido } = useVistaPrevia()
const pestanaActiva = ref<'editor' | 'vista-previa'>('editor')
const contenidoPreviewHtml = ref('')

const areaSeleccionadaNombre = computed(() => {
  const area = listaAreas.value.find(
    (a) => String(a.area_servicio_id) === String(registro.area_servicio_id)
  )
  return area?.area_servicio_nombre || ''
})

const fechaTextoPreview = computed(() => {
  if (esProgramado.value) {
    if (!registro.fecha_programada) return 'Se publicará cuando definas una fecha programada'
    const fecha = new Date(registro.fecha_programada)
    return `Programado para el ${fecha.toLocaleDateString('es-ES', { year: 'numeric', month: 'long', day: 'numeric' })} a las ${fecha.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' })}`
  }
  const fecha = new Date(registro.fecha_publicacion)
  return `Publicado el ${fecha.toLocaleDateString('es-ES', { year: 'numeric', month: 'long', day: 'numeric' })}`
})

/**
 * Toma el contenido actual del editor (sin guardarlo en el backend) y arma
 * el HTML de vista previa, tal cual se vería públicamente el registro.
 */
const abrirVistaPrevia = async () => {
  if (editorInstance.value) {
    try {
      const outputData = await editorInstance.value.save()
      contenidoPreviewHtml.value = generarHtmlContenido(outputData)
    } catch (error) {
      console.error('Error al generar la vista previa:', error)
      toast.warning('No se pudo generar la vista previa del contenido.')
    }
  }
  pestanaActiva.value = 'vista-previa'
}

const enfocarTitulo = async () => {
  await nextTick()
  tituloInput.value?.focus()
}

onMounted(async () => {
  areasStore.fetchAreas()
  categoriasStore.fetchCategorias()

  try {
    const resEstados = await api.get('/estados-actualizacion')
    listaEstados.value = resEstados.data.data
  } catch (error) {
    console.error('Error al cargar los estados:', error)
  }

  await iniciarEditor({
    holder: 'editorjs',
    placeholder: 'Escribe tu actualización aquí. Puedes arrastrar imágenes...',
    headerLevels: [2, 3, 4, 5, 6],
    onReady: () => {
      cargarBorrador()
    },
    onChange: () => {
      programarAutosave()
    },
  })
  const modalEl = document.getElementById('modalNuevoRegistro')

  modalEl?.addEventListener('shown.bs.modal', enfocarTitulo)
})

onBeforeUnmount(() => {
  const modalEl = document.getElementById('modalNuevoRegistro')
  modalEl?.removeEventListener('shown.bs.modal', enfocarTitulo)

  guardarBorrador()
  if (autoguardadoTimeout) clearTimeout(autoguardadoTimeout)
  destruirEditor()
})

// ── Guardar registro ──────────────────────────────────────────────
const guardarRegistro = async () => {
  if (registro.resumen.length > LIMITE_RESUMEN) {
    errores.value.resumen = [`El resumen no puede superar los ${LIMITE_RESUMEN} caracteres.`]
    return
  }

  let outputData

  if (editorInstance.value) {
    outputData = await editorInstance.value.save()
  }

  if (!outputData || outputData.blocks.length === 0) {
    toast.warning('El contenido no puede estar vacío.')
    return
  }

  enviando.value = true

  try {
const formData = new FormData()
    
    // Aseguramos que siempre sea un string con || ''
    formData.append('actualizacion_titulo', registro.titulo || '')
    formData.append('actualizacion_version', registro.version || '')
    formData.append('actualizacion_contenido', JSON.stringify(outputData))
    formData.append('actualizacion_resumen', registro.resumen || '')
    formData.append('actualizacion_area_servicio_id', String(registro.area_servicio_id))

    const categoriaIds = registro.actualizacion_categoria_ids.slice(0, 3)

    if (categoriaIds.length === 0) {
      toast.warning('Selecciona al menos una categoría.')
      enviando.value = false
      return
    }

    formData.append('actualizacion_categoria_id', String(categoriaIds[0]))
    categoriaIds.forEach((categoriaId) => {
      formData.append('actualizacion_categoria_ids[]', String(categoriaId))
    })
    formData.append('actualizacion_usuario_id_autor', String(registro.usuario_id_autor))
    formData.append('actualizacion_estado', registro.estado || '')

    if (esProgramado.value) {
      const errorFecha = validarFechaProgramada(registro.fecha_programada)
      if (errorFecha) {
        toast.warning(errorFecha)
        enviando.value = false
        return
      }
      
      // Aquí probablemente estaba el error principal, le agregamos || ''
      formData.append('actualizacion_fecha_publicacion', registro.fecha_programada || '')
    } else {
      formData.append('actualizacion_fecha_publicacion', registro.fecha_publicacion || '')
    }

    if (archivoMiniatura.value) {
      formData.append('actualizacion_imagen_destacada', archivoMiniatura.value)
    }

    const respuesta = await api.post('/actualizaciones', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })

    // El interceptor ya muestra toast.success si el backend retorna message.
    // Solo mostramos el manual como respaldo cuando no viene message.
    if (!respuesta.data?.message) {
      toast.success('¡Registro publicado correctamente!')
    }

    descartarBorrador()
    limpiarFormulario()
    emit('recargar-lista')
    emit('cerrar')

    // ← Cerrar el modal correctamente via Bootstrap:
    const modalEl = document.getElementById('modalNuevoRegistro') // el id de tu modal
    if (modalEl) {
      const modal = Modal.getInstance(modalEl)
      modal?.hide()
    }

  } catch {
    // El interceptor de api.ts ya gestiona y muestra los errores
  } finally {
    enviando.value = false
  }
}

// ── Limpiar ───────────────────────────────────────────────────────
const limpiarFormulario = () => {
  Object.assign(registro, registroVacio())

  pestanaActiva.value = 'editor'
  contenidoPreviewHtml.value = ''

  quitarImagenMiniatura()

  const inputMiniatura = document.getElementById('miniatura') as HTMLInputElement
  if (inputMiniatura) inputMiniatura.value = ''

  if (editorInstance.value) editorInstance.value.clear()
}

// const selectAbierto = ref(false)
// const busquedaCategoria = ref('')

// // Categorías filtradas por búsqueda
// const categoriasFiltradas = computed(() => {
//   if (!busquedaCategoria.value) return listaCategorias.value
//   const busqueda = busquedaCategoria.value.toLowerCase()
//   return listaCategorias.value.filter(cat =>
//     cat.categoria_actualizacion_nombre.toLowerCase().includes(busqueda)
//   )
// })

// // Computed para obtener las categorías seleccionadas con sus detalles
// const categoriasSeleccionadas = computed(() => {
//   return registro.actualizacion_categoria_ids
//     .map(id => {
//       const categoria = listaCategorias.value.find(c => Number(c.categoria_actualizacion_id) === id)
//       return categoria ? {
//         id: Number(categoria.categoria_actualizacion_id),
//         nombre: categoria.categoria_actualizacion_nombre
//       } : null
//     })
//     .filter(c => c !== null)
// })

// const toggleSelect = () => {
//   selectAbierto.value = !selectAbierto.value
//   if (!selectAbierto.value) {
//     busquedaCategoria.value = ''
//   }
// }

// const toggleCategoria = (categoriaId: number) => {
//   const index = registro.actualizacion_categoria_ids.indexOf(categoriaId)

//   if (index > -1) {
//     registro.actualizacion_categoria_ids.splice(index, 1)
//   } else {
//     if (registro.actualizacion_categoria_ids.length < 3) {
//       registro.actualizacion_categoria_ids.push(categoriaId)
//     } else {
//       toast.warning('Solo puedes seleccionar máximo 3 categorías')
//     }
//   }
// }

// Cerrar select al hacer click fuera
const cerrarSelectAlClickFuera = (event: MouseEvent) => {
  const wrapper = document.querySelector('.categoria-select-wrapper')
  if (wrapper && !wrapper.contains(event.target as Node)) {
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
</script>
<style scoped>
/* ─── Colores y utilidades ──────────────────────────────── */
.text-primary {
  color: var(--primary) !important;
}

.btn-primary {
  background-color: var(--primary);
  border-color: var(--primary);
  color: #fff;
  padding: 0.375rem 0.75rem;
  border-radius: 0.375rem;
  transition: all 0.2s ease;
}

.btn-primary:hover:not(:disabled) {
  background-color: var(--secondary);
  border-color: var(--secondary);
}

.btn-outline-secondary {
  color: var(--secondary);
  border-color: var(--secondary);
}

.btn-outline-secondary:hover {
  background-color: var(--secondary);
  border-color: var(--secondary);
  color: #fff;
}

.form-control:focus,
.form-select:focus {
  border-color: var(--primary);
  box-shadow: 0 0 0 0.2rem rgba(7, 126, 157, 0.25);
}

/* ─── Layout principal: campos | editor + acciones al final ───────────────── */
.form-container-x {
  display: grid;
  grid-template-columns: minmax(390px, 0.92fr) minmax(460px, 1.08fr);
  grid-template-rows: minmax(0, 1fr) auto;
  grid-template-areas:
    "left editor"
    "actions actions";
  column-gap: 32px;
  row-gap: 18px;
  align-items: stretch;
  height: calc(100dvh - 150px);
  max-height: calc(100dvh - 150px);
  min-height: 0;
  overflow: hidden;
}

.left-column {
  grid-area: left;
  display: flex;
  flex-direction: column;
  gap: 12px;
  min-width: 0;
  min-height: 0;
  padding-right: 4px;
  overflow-y: auto;
  overflow-x: hidden;
}

.left-column .mb-3,
.left-column .row,
.left-column .form-group {
  margin-bottom: 0 !important;
}

.left-column::-webkit-scrollbar {
  width: 6px;
}

.left-column::-webkit-scrollbar-track {
  background: #f1f5f9;
  border-radius: 999px;
}

.left-column::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 999px;
}

.left-column::-webkit-scrollbar-thumb:hover {
  background: var(--primary);
}

.actions {
  grid-area: actions;
  display: flex;
  justify-content: flex-end;
  align-items: center;
  gap: 12px;
  margin-top: 0 !important;
  padding: 14px 0 0;
  border-top: 1px solid #e2e8f0;
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.82), #ffffff 42%);
}

.actions .btn {
  min-height: 42px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

/* ─── Columna del editor ────────────────────────────────── */
.editor-column {
  grid-area: editor;
  display: flex;
  flex-direction: column;
  min-width: 0;
  min-height: 0;
  overflow: hidden;
}

.editor-column-header {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  margin-bottom: 8px;
}

.pestanas-editor {
  display: inline-flex;
  gap: 4px;
  padding: 3px;
  background: #f1f5f9;
  border-radius: 10px;
}

.pestana-btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 12px;
  color: #64748b;
  font-size: 0.8rem;
  font-weight: 600;
  background: transparent;
  border: 0;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s ease;
}

.pestana-btn:hover {
  color: var(--primary);
}

.pestana-btn.activa {
  color: var(--primary);
  background: #ffffff;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
}

.editor-wrapper {
  flex: 1;
  min-height: 0;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  background: #ffffff;
  padding: 20px 24px;
  overflow-y: auto;
  overflow-x: hidden;
}

.editor-wrapper:focus-within {
  border-color: var(--primary);
  box-shadow: 0 0 0 3px rgba(7, 126, 157, 0.12);
}

.editor-column > :deep(.vista-previa-container) {
  flex: 1;
  min-height: 0;
}

/* ─── Columna del editor ────────────────────────────────── */
.editor-column {
  display: flex;
  flex-direction: column;
  min-width: 0;
  min-height: 0;
  overflow: hidden;
}

.editor-wrapper {
  flex: 1;
  min-height: 0;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  background: #ffffff;
  padding: 20px 24px;
  overflow-y: auto;
  overflow-x: hidden;
}

.editor-wrapper:focus-within {
  border-color: var(--primary);
  box-shadow: 0 0 0 3px rgba(7, 126, 157, 0.12);
}

/* ─── Editor.js interno ────────────────────────────────── */
:deep(.codex-editor) {
  padding: 0;
}

:deep(.codex-editor__redactor) {
  min-height: 100%;
  padding-bottom: 40px !important;
}

:deep(.ce-block__content),
:deep(.ce-toolbar__content) {
  max-width: 100%;
  padding: 0;
}

:deep(.ce-block__content) {
  word-break: break-word;
  overflow-wrap: break-word;
}

/* ─── Scrollbar del editor ────────────────────────────── */
.editor-wrapper::-webkit-scrollbar {
  width: 8px;
}

.editor-wrapper::-webkit-scrollbar-track {
  background: #f1f5f9;
  border-radius: 999px;
}

.editor-wrapper::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 999px;
}

.editor-wrapper::-webkit-scrollbar-thumb:hover {
  background: var(--primary);
}


/* ─── Barra de autosave ────────────────────────────────── */
.autosave-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 10px 14px;
  background: #fdfeff;
  border: 1px solid #e1e7f0;
  border-radius: 10px;
  gap: 12px;
  flex-wrap: wrap;
}

.autosave-estado {
  font-size: 0.82rem;
  display: flex;
  align-items: center;
  gap: 8px;
}

.autosave-guardando {
  color: #f59e0b;
  display: flex;
  align-items: center;
  gap: 6px;
}

.autosave-ok {
  color: #16a34a;
  display: flex;
  align-items: center;
  gap: 6px;
}

.autosave-vacio {
  color: #94a3b8;
}

.autosave-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  display: inline-block;
  flex-shrink: 0;
}

.autosave-dot.pulsando {
  background: #f59e0b;
  animation: pulso 1s infinite;
}

.autosave-dot.ok {
  background: #16a34a;
}

@keyframes pulso {

  0%,
  100% {
    opacity: 1;
    transform: scale(1);
  }

  50% {
    opacity: 0.4;
    transform: scale(0.8);
  }
}

.btn-descartar {
  font-size: 0.8rem;
  padding: 5px 12px;
  border-radius: 8px;
  border: 1px solid #fca5a5;
  background: #fff1f2;
  color: #dc2626;
  cursor: pointer;
  transition: all 0.2s;
  white-space: nowrap;
}

.btn-descartar:hover {
  background: #fee2e2;
  border-color: #dc2626;
}

/* ─── Select de categorías (custom) ───────────────────── */
.categoria-select-wrapper {
  position: relative;
  margin-bottom: 12px;
}

.categoria-select-trigger {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 10px 12px;
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s ease;
  min-height: 42px;
}

.categoria-select-trigger:hover {
  border-color: var(--primary);
}

.categoria-select-wrapper.open .categoria-select-trigger {
  border-color: var(--primary);
  box-shadow: 0 0 0 2px rgba(7, 126, 157, 0.1);
}

.select-placeholder {
  display: flex;
  align-items: center;
  gap: 8px;
  color: #94a3b8;
  font-size: 0.85rem;
}

.select-placeholder i {
  font-size: 0.9rem;
}

.select-selected {
  flex: 1;
}

.selected-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 4px;
}

.selected-tag {
  background: rgba(7, 126, 157, 0.1);
  color: var(--primary);
  padding: 2px 8px;
  border-radius: 12px;
  font-size: 0.7rem;
  font-weight: 500;
}

.selected-more {
  background: #e2e8f0;
  color: #475569;
  padding: 2px 6px;
  border-radius: 12px;
  font-size: 0.7rem;
  font-weight: 500;
}

.categoria-select-trigger .bi-chevron-down,
.categoria-select-trigger .bi-chevron-up {
  color: #94a3b8;
  transition: transform 0.2s ease;
}

.categoria-select-dropdown {
  position: absolute;
  top: calc(100% + 4px);
  left: 0;
  right: 0;
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  z-index: 1060;
  overflow: hidden;
}

.dropdown-search {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 12px;
  border-bottom: 1px solid #e2e8f0;
}

.dropdown-search i {
  color: #94a3b8;
  font-size: 0.85rem;
}

.dropdown-search input {
  flex: 1;
  border: none;
  outline: none;
  font-size: 0.85rem;
  padding: 6px 0;
}

.dropdown-search input::placeholder {
  color: #cbd5e1;
}

.dropdown-options {
  max-height: 200px;
  overflow-y: auto;
}

.dropdown-option {
  display: flex;
  align-items: center;
  justify-content: space-between;
  width: 100%;
  padding: 10px 12px;
  background: transparent;
  border: none;
  cursor: pointer;
  transition: all 0.15s ease;
  text-align: left;
}

.dropdown-option:hover:not(.disabled) {
  background: #f8fafc;
}

.dropdown-option.selected {
  background: rgba(7, 126, 157, 0.08);
}

.dropdown-option.disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.option-name {
  font-size: 0.85rem;
  color: #334155;
}

.dropdown-option.selected .option-name {
  color: var(--primary);
  font-weight: 500;
}

.option-check i {
  color: var(--primary);
  font-size: 0.9rem;
}

.dropdown-empty {
  padding: 20px;
  text-align: center;
  color: #94a3b8;
  font-size: 0.8rem;
}

.dropdown-options::-webkit-scrollbar {
  width: 6px;
}

.dropdown-options::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 4px;
}

.dropdown-options::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 4px;
}

.categoria-counter {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-top: 8px;
  padding-top: 6px;
  border-top: 1px solid #f1f5f9;
}

.counter-text {
  font-size: 0.7rem;
  color: #64748b;
}

.counter-warning {
  display: flex;
  align-items: center;
  gap: 4px;
  font-size: 0.7rem;
  color: var(--warning);
  background: rgba(252, 187, 28, 0.1);
  padding: 2px 8px;
  border-radius: 12px;
}

.counter-warning i {
  font-size: 0.65rem;
}

/* ─── MODAL (global) ───────────────────────────────────── */
:global(#modalNuevoRegistro .modal-dialog) {
  width: min(1400px, calc(100vw - 24px));
  max-width: min(1400px, calc(100vw - 24px));
  margin: 12px auto;
}

:global(#modalNuevoRegistro .modal-content) {
  max-height: calc(100dvh - 24px);
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

:global(#modalNuevoRegistro .modal-body) {
  flex: 1 1 auto;
  min-height: 0;
  overflow: hidden;
}

.new-version-container {
  width: 100%;
  height: 100%;
  min-height: 0;
  overflow: hidden;
}

/* ─── RESPONSIVE: TABLET Y LAPTOP PEQUEÑA (769px – 1180px) ────────────── */
@media (max-width: 1180px) and (min-width: 769px) {
  :global(#modalNuevoRegistro .modal-dialog) {
    width: calc(100vw - 32px);
    max-width: calc(100vw - 32px);
    margin: 16px auto;
  }

  :global(#modalNuevoRegistro .modal-content) {
    max-height: calc(100dvh - 32px);
  }

  :global(#modalNuevoRegistro .modal-body) {
    overflow-y: auto;
    overflow-x: hidden;
  }

  .new-version-container {
    height: auto;
    overflow: visible;
  }

  .form-container-x {
    display: flex;
    flex-direction: column;
    gap: 18px;
    height: auto;
    max-height: none;
    overflow: visible;
  }

  .left-column {
    order: 1;
    gap: 12px;
    padding-right: 0;
    overflow: visible;
  }

  .editor-column {
    order: 2;
    min-height: 420px;
  }

  .editor-wrapper {
    min-height: 420px;
    max-height: none;
    padding: 16px 20px;
  }

  .actions {
    order: 3;
    justify-content: flex-end;
    padding: 16px 0 2px;
    background: #ffffff;
  }
}

/* ─── RESPONSIVE: MÓVIL (< 768px) ────────────────────── */
@media (max-width: 768px) {
  :global(#modalNuevoRegistro .modal-dialog) {
    width: calc(100vw - 16px);
    max-width: calc(100vw - 16px);
    margin: 8px auto;
  }

  :global(#modalNuevoRegistro .modal-content) {
    max-height: calc(100dvh - 16px);
  }

  :global(#modalNuevoRegistro .modal-body) {
    overflow-y: auto;
    overflow-x: hidden;
    padding-bottom: 12px;
  }

  .new-version-container {
    height: auto;
    overflow: visible;
  }

  .form-container-x {
    display: flex;
    flex-direction: column;
    gap: 18px;
    height: auto;
    max-height: none;
    overflow: visible;
  }

  .left-column {
    order: 1;
    gap: 10px;
    padding-right: 0;
    overflow: visible;
  }

  .editor-column {
    order: 2;
    min-height: 320px;
  }

  .editor-wrapper {
    min-height: 320px;
    padding: 16px;
  }

  .actions {
    order: 3;
    flex-direction: column;
    align-items: stretch;
    gap: 10px;
    margin-top: 0 !important;
    padding: 16px 0 calc(8px + env(safe-area-inset-bottom));
    background: #ffffff;
  }

  .actions .btn,
  .actions .btn-primary,
  .actions .btn-outline-secondary {
    width: 100%;
    justify-content: center;
  }

  .autosave-bar {
    flex-direction: column;
    align-items: stretch;
    gap: 8px;
  }

  .btn-descartar {
    align-self: flex-end;
  }

  /* Ajustes de fuente y padding en campos */
  .form-label {
    font-size: 0.9rem;
  }

  .form-control,
  .form-select {
    font-size: 0.9rem;
    padding: 0.4rem 0.6rem;
  }
}

/* ─── MÓVIL MUY PEQUEÑO (< 480px) ────────────────────── */
@media (max-width: 480px) {
  .form-container-x {
    gap: 16px;
  }

  .editor-column {
    min-height: 260px;
  }

  .editor-wrapper {
    min-height: 260px;
    padding: 12px;
  }

  .autosave-estado {
    font-size: 0.75rem;
  }

  .btn-descartar {
    font-size: 0.7rem;
    padding: 4px 10px;
  }
}
</style>