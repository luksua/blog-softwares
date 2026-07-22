<template>
  <div class="edit-version-container">
    <!-- Estado de carga -->
    <div v-if="cargando" class="text-center py-5">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Cargando...</span>
      </div>
      <p class="mt-2 text-secondary">Cargando actualización...</p>
    </div>

    <div v-else class="edit-modal-content">
      <form @submit.prevent="guardarCambios" class="edit-form-grid">
        <!-- COLUMNA IZQUIERDA: campos -->
        <div class="left-column">
          <!-- Alerta de corrección -->
          <div v-if="modoCorreccion" class="alert alert-warning mb-4">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <strong>Corrección pendiente.</strong>
            Realiza los ajustes solicitados. Al enviar la corrección, el registro quedará publicado y se notificará al
            supervisor del área.
          </div>

          <!-- Título -->
          <div class="form-group mb-4">
            <label class="form-label fw-bold text-primary">
              Título <span class="text-danger">*</span>
            </label>
            <input v-model="form.titulo" type="text" class="form-control"
              :class="{ 'is-invalid': errores.titulo || errores.actualizacion_titulo }" ref="inputTitulo"
              placeholder="Ingrese el título de la actualización" />
            <div v-if="errores.titulo || errores.actualizacion_titulo" class="invalid-feedback">
              {{ errores.titulo?.[0] || errores.actualizacion_titulo?.[0] }}
            </div>
          </div>

          <!-- Versión e imagen -->
          <div class="row">
            <!-- Versión -->
            <div class="col-12 col-sm-6 mb-4">
              <label class="form-label fw-bold text-primary">Versión</label>
              <input v-model="form.version" type="text" class="form-control"
                :class="{ 'is-invalid': errores.version || errores.actualizacion_version }" placeholder="Ej: 1.0.0" />
              <div v-if="errores.version || errores.actualizacion_version" class="invalid-feedback">
                {{ errores.version?.[0] || errores.actualizacion_version?.[0] }}
              </div>
            </div>

            <!-- Imagen destacada -->
            <div class="col-12 col-sm-6 mb-4">
              <label class="form-label fw-bold text-primary">Imagen destacada (Portada)</label>

              <div v-if="previewImagen || form.imagen_destacada" class="mb-3">
                <div class="position-relative d-inline-block">
                  <img :src="previewImagen || obtenerUrlImagen(form.imagen_destacada)" alt="Portada actual"
                    class="img-thumbnail" style="max-height: 150px; border-radius: 8px;" />

                  <button v-if="previewImagen || form.imagen_destacada" type="button"
                    class="btn btn-sm btn-danger position-absolute top-0 end-0 rounded-circle"
                    style="transform: translate(50%, -50%);" @click="eliminarImagen">
                    <i class="bi bi-x"></i>
                  </button>
                </div>

                <div class="form-text mt-2">
                  {{ previewImagen ? 'Nueva imagen seleccionada' : 'Imagen actual' }}
                </div>
              </div>

              <input type="file" class="form-control" :class="{ 'is-invalid': errores.imagen_destacada }"
                accept="image/*" @change="manejarImagen" />

              <div v-if="errores.imagen_destacada" class="invalid-feedback">
                {{ errores.imagen_destacada?.[0] }}
              </div>
            </div>
          </div>

          <!-- Resumen -->
          <div class="form-group">
            <label for="resumen" class="form-label fw-bold text-primary">
              Resumen
            </label>
            <textarea v-model="form.resumen" class="form-control"
              :class="{ 'is-invalid': errores.resumen || errores.actualizacion_resumen || resumenExcedeLimite }"
              rows="3" placeholder="Breve descripción de la actualización..."></textarea>

            <div class="form-text text-end" :class="{ 'text-danger fw-bold': resumenExcedeLimite }">
              {{ form.resumen.length }}/800 caracteres
            </div>

            <div v-if="resumenExcedeLimite" class="invalid-feedback d-block">
              El resumen no puede superar los 800 caracteres.
            </div>
          </div>

          <!-- Área y categorías -->
          <div class="row">
            <!-- Área -->
            <div class="col-12 col-md-6 mb-4">
              <label for="area" class="form-label fw-bold text-primary">
                Área <span class="text-danger">*</span>
              </label>

              <select id="area" class="form-select"
                :class="{ 'is-invalid': errores.area_servicio_id || errores.actualizacion_area_servicio_id }"
                v-model="form.area_servicio_id" required>
                <option value="" disabled>Selecciona un área...</option>
                <option v-for="area in listaAreas" :key="area.area_servicio_id" :value="area.area_servicio_id">
                  {{ area.area_servicio_nombre }}
                </option>
              </select>

              <div v-if="errores.area_servicio_id || errores.actualizacion_area_servicio_id" class="invalid-feedback">
                {{ errores.area_servicio_id?.[0] || errores.actualizacion_area_servicio_id?.[0] }}
              </div>
            </div>

            <!-- Categorías -->
            <div class="col-12 col-md-6 mb-4">
              <label class="form-label fw-bold text-primary">
                Categorías <span class="text-danger">*</span>
                <small class="text-muted">(máximo 3)</small>
              </label>

              <div ref="categoriaSelectRef" class="categoria-select-wrapper"
                :class="{ open: selectAbierto, 'has-selection': categoriasSeleccionadas.length > 0 }">
                <div class="categoria-select-trigger" @click="toggleSelect">
                  <div class="select-placeholder" v-if="categoriasSeleccionadas.length === 0">
                    <i class="bi bi-tag"></i>
                    <span>Selecciona hasta 3 categorías</span>
                  </div>

                  <div class="select-selected" v-else>
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
                      }" @click="toggleCategoria(Number(categoria.categoria_actualizacion_id))" :disabled="!categoriaSeleccionada(Number(categoria.categoria_actualizacion_id)) &&
                        categoriasSeleccionadas.length >= 3
                        ">
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

              <div v-if="errores.actualizacion_categoria_ids || errores.actualizacion_categoria_id"
                class="invalid-feedback d-block">
                {{ errores.actualizacion_categoria_ids?.[0] || errores.actualizacion_categoria_id?.[0] }}
              </div>
            </div>
          </div>


          <!-- Estado y fechas -->
          <div class="row">
            <!-- Estado -->
            <div class="col-12 col-md-6 mb-4">
              <template v-if="!modoCorreccion">
                <label class="form-label fw-bold text-primary">Estado</label>

                <select v-model="form.estado" class="form-select"
                  :class="{ 'is-invalid': errores.estado || errores.actualizacion_estado }">
                  <option value="borrador">Borrador</option>
                  <option value="revision">Revisión</option>
                  <option value="publicado">Publicado</option>
                  <option value="programado">Programado</option>
                  <option value="inactivo">Inactivo</option>
                </select>

                <div v-if="errores.estado || errores.actualizacion_estado" class="invalid-feedback">
                  {{ errores.estado?.[0] || errores.actualizacion_estado?.[0] }}
                </div>
              </template>

              <template v-else>
                <label class="form-label fw-bold text-warning">Estado de la corrección</label>

                <div class="alert alert-warning mb-0 py-2">
                  <i class="bi bi-send-check me-2"></i>
                  Se enviará como <strong>Publicado</strong>
                </div>
              </template>
            </div>

            <!-- Fechas -->
            <div class="col-12 col-md-6 mb-4">
              <template v-if="estadoParaVista === 'programado'">
                <label class="form-label fw-bold text-primary">
                  Fecha y hora de publicación <span class="text-danger">*</span>
                </label>

                <input v-model="form.fecha_programada" type="datetime-local" class="form-control border-primary"
                  :min="fechaMinimaProgramada"
                  :class="{ 'is-invalid': errores.fecha_publicacion || errores.actualizacion_fecha_publicacion }"
                  required />

                <div v-if="errores.fecha_publicacion || errores.actualizacion_fecha_publicacion"
                  class="invalid-feedback">
                  {{ errores.fecha_publicacion?.[0] || errores.actualizacion_fecha_publicacion?.[0] }}
                </div>

                <div class="form-text text-primary fw-bold mt-1">
                  <i class="bi bi-clock-fill me-1"></i>
                  El registro se publicará automáticamente en esta fecha y hora.
                </div>
              </template>

              <template v-else-if="estadoParaVista !== 'publicado'">
                <label class="form-label fw-bold ">Fecha de creación</label>

                <input v-model="form.fecha_creacion" type="date" class="form-control bg-light" readonly disabled />

                <div class="form-text text-secondary mt-1">
                  <i class="bi bi-info-circle me-1"></i>
                  Registro no publicado. Mostrando fecha de creación.
                </div>
              </template>

              <template v-else>
                <label class="form-label fw-bold text-primary">Fecha de publicación</label>

                <input v-model="form.fecha_publicacion" type="date" class="form-control border-primary bg-light"
                  :class="{ 'is-invalid': errores.fecha_publicacion || errores.actualizacion_fecha_publicacion }"
                  readonly />

                <div v-if="errores.fecha_publicacion || errores.actualizacion_fecha_publicacion"
                  class="invalid-feedback">
                  {{ errores.fecha_publicacion?.[0] || errores.actualizacion_fecha_publicacion?.[0] }}
                </div>

                <div class="form-text text-primary fw-bold mt-1">
                  <i class="bi bi-check-circle-fill me-1"></i>
                  El registro se publicará con esta fecha.
                </div>
              </template>
            </div>
          </div>

        </div>

        <!-- COLUMNA DERECHA: editor -->
        <div class="editor-column">
          <div class="editor-column-header">
            <label class="form-label fw-bold text-primary mb-0">Contenido</label>
          </div>

          <div class="editor-wrapper" v-show="pestanaActiva === 'editor'">
            <div ref="editorHolder" id="editorjs" class="editor-container"></div>
          </div>

          <VistaPreviaRegistro
            v-if="pestanaActiva === 'vista-previa'"
            :titulo="form.titulo"
            :version="form.version"
            :resumen="form.resumen"
            :area-nombre="areaSeleccionadaNombre"
            :categorias="categoriasSeleccionadas.map(c => c.nombre)"
            :imagen-url="imagenUrlPreview"
            :fecha-texto="fechaTextoPreview"
            :contenido-html="contenidoPreviewHtml"
          />
        </div>

        <!-- ACCIONES: fuera de la columna izquierda para que siempre queden al final -->
        <div class="form-footer">
          <transition name="fade">
            <div v-if="mensajeOk" class="alert alert-success form-success-message">
              <i class="bi bi-check-circle-fill me-2"></i>
              {{ mensajeOk }}
            </div>
          </transition>

          <div class="form-actions">
            <button type="button" class="btn btn-outline-secondary" @click="cerrarModal" :disabled="guardando">
              <i class="bi bi-x-circle me-1"></i>
              Cancelar
            </button>

            <button type="submit" class="btn btn-primary" :disabled="guardando || !formularioValido">
              <span v-if="guardando" class="spinner-border spinner-border-sm me-2" role="status"></span>

              <i v-else :class="modoCorreccion ? 'bi bi-send-check' : 'bi bi-save'" class="me-1"></i>

              {{ textoBotonGuardar }}
            </button>
          </div>
        </div>
      </form>
    </div>
  </div>
</template>


<script setup lang="ts">
import { computed, reactive, ref, watch, nextTick, onBeforeUnmount, onMounted, toRef } from 'vue'
import { storeToRefs } from 'pinia'
import api from '../../api/api'
import { useAreasStore } from '../../stores/areas'
import { useCategoriasStore } from '../../stores/categorias'
import { useEditorJS } from '../../composables/useEditorJS'
import { useCategoriaSelector, normalizarCategoriaIds } from '../../composables/useCategoriaSelector'
import { useFechaProgramada } from '../../composables/useFechaProgramada'
import { useImagenDestacada, resolverUrlImagen } from '../../composables/useImagenDestacada'
import VistaPreviaRegistro from './VistaPreviaRegistro.vue'

// Props
const props = withDefaults(defineProps<{
  id: string | number
  modoCorreccion?: boolean
}>(), {
  modoCorreccion: false,
})

// Emits
const emit = defineEmits<{
  (e: 'guardado'): void
  (e: 'cerrar'): void
}>()

// Refs
const inputTitulo = ref<HTMLInputElement | null>(null)
const editorHolder = ref<HTMLElement | null>(null)
const { editor, iniciar: iniciarEditor, destruir: destruirEditor } = useEditorJS()

// Estado
const cargando = ref(true)
const guardando = ref(false)
const mensajeOk = ref('')
const errores = ref<Record<string, string[]>>({})
const areasStore = useAreasStore()
const categoriasStore = useCategoriasStore()
const { areas: listaAreas } = storeToRefs(areasStore)
const { categorias: listaCategorias } = storeToRefs(categoriasStore)

// Formulario
const form = reactive({
  titulo: '',
  version: '',
  contenido: '',
  resumen: '',
  imagen_destacada: '',
  area_servicio_id: null as number | null,
  categoria_ids: [] as number[],
  usuario_id_autor: null as number | null,
  estado: 'borrador',
  fecha_creacion: '',
  fecha_publicacion: '',
  fecha_programada: ''
})

const LIMITE_RESUMEN = 800

const resumenExcedeLimite = computed(() => {
  return form.resumen.length > LIMITE_RESUMEN
})

// Computed
const modoCorreccion = computed(() => props.modoCorreccion)
const estadoParaVista = computed(() => props.modoCorreccion ? 'publicado' : form.estado)

const { fechaMinimaProgramada, validarFechaProgramada } = useFechaProgramada()

const {
  archivo: archivoImagen,
  preview: previewImagen,
  seleccionarArchivo: manejarImagenBase,
  quitarImagen: eliminarImagenPreview,
} = useImagenDestacada({
  onError: (mensaje) => { errores.value.imagen_destacada = [mensaje] },
})

const textoBotonGuardar = computed(() => {
  if (guardando.value) return 'Procesando...'
  return props.modoCorreccion ? 'Enviar corrección' : 'Guardar cambios'
})

const {
  wrapperRef: 
  selectAbierto,
  busquedaCategoria,
  categoriasFiltradas,
  categoriasSeleccionadas,
  categoriaSeleccionada,
  toggleCategoria,
  toggleSelect,
} = useCategoriaSelector(toRef(form, 'categoria_ids'), listaCategorias)

const formularioValido = computed(() => {
  return form.titulo.trim() !== '' &&
    form.resumen.trim().length > 0 &&
    form.resumen.length <= LIMITE_RESUMEN &&
    form.area_servicio_id !== null &&
    form.categoria_ids.length > 0 &&
    form.categoria_ids.length <= 3
})

// Métodos
const manejarImagen = (event: Event) => {
  manejarImagenBase(event)
  if (archivoImagen.value) {
    delete errores.value.imagen_destacada
  }
}

const eliminarImagen = () => {
  eliminarImagenPreview()
  form.imagen_destacada = ''
}

const obtenerUrlImagen = (ruta: string) => resolverUrlImagen(ruta)

// ── Vista previa ──────────────────────────────────────────────────

const pestanaActiva = ref<'editor' | 'vista-previa'>('editor')
const contenidoPreviewHtml = ref('')

const areaSeleccionadaNombre = computed(() => {
  const area = listaAreas.value.find(
    (a) => String(a.area_servicio_id) === String(form.area_servicio_id)
  )
  return area?.area_servicio_nombre || ''
})

const imagenUrlPreview = computed(() => {
  return previewImagen.value || (form.imagen_destacada ? obtenerUrlImagen(form.imagen_destacada) : null)
})

const fechaTextoPreview = computed(() => {
  if (estadoParaVista.value === 'programado' || form.estado === 'programado') {
    if (!form.fecha_programada) return 'Se publicará cuando definas una fecha programada'
    const fecha = new Date(form.fecha_programada)
    return `Programado para el ${fecha.toLocaleDateString('es-ES', { year: 'numeric', month: 'long', day: 'numeric' })} a las ${fecha.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' })}`
  }
  if (!form.fecha_publicacion) return 'Sin fecha de publicación definida'
  const fecha = new Date(form.fecha_publicacion)
  return `Publicado el ${fecha.toLocaleDateString('es-ES', { year: 'numeric', month: 'long', day: 'numeric' })}`
})

/**
 * Toma el contenido actual del editor (sin guardar los cambios todavía)
 * y arma el HTML de vista previa, tal cual se vería públicamente.
 */

const formatearFechaParaInput = (fecha: any) => {
  if (!fecha) return ''
  const fechaObj = typeof fecha === 'number' && fecha < 10000000000
    ? new Date(fecha * 1000)
    : new Date(fecha)
  return isNaN(fechaObj.getTime()) ? '' : fechaObj.toISOString().split('T')[0]
}

// Igual que formatearFechaParaInput, pero conservando hora:minuto para
// inputs type="datetime-local" (que esperan la hora en horario local).
const formatearFechaHoraParaInput = (fecha: any) => {
  if (!fecha) return ''
  const fechaObj = typeof fecha === 'number' && fecha < 10000000000
    ? new Date(fecha * 1000)
    : new Date(fecha)

  if (isNaN(fechaObj.getTime())) return ''

  const conOffsetLocal = new Date(fechaObj.getTime() - fechaObj.getTimezoneOffset() * 60000)
  return conOffsetLocal.toISOString().slice(0, 16)
}

const cargarListas = async () => {
  try {
    await Promise.all([
      areasStore.fetchAreas(),
      categoriasStore.fetchCategorias(),
    ])
  } catch (error) {
    console.error('Error al cargar catálogos:', error)
  }
}

const initEditor = async (initialData: any = {}) => {
  await iniciarEditor({
    holder: editorHolder.value!,
    data: initialData,
    headerLevels: [2, 3, 4],
  })
}

const cerrarModal = () => {
  if (!guardando.value) {
    emit('cerrar')
  }
}

const enfocarTitulo = async () => {
  await nextTick()
  setTimeout(() => {
    inputTitulo.value?.focus()
    inputTitulo.value?.select()
  }, 300)
}

const cargarRegistro = async () => {
  cargando.value = true
  errores.value = {}
  mensajeOk.value = ''
  archivoImagen.value = null
  previewImagen.value = null
  pestanaActiva.value = 'editor'
  contenidoPreviewHtml.value = ''

  try {
    const respuesta = await api.get(`/actualizaciones/${props.id}`)
    const data = respuesta.data.data

    form.titulo = data.actualizacion_titulo ?? ''
    form.version = data.actualizacion_version ?? ''
    form.imagen_destacada = data.actualizacion_imagen_destacada ?? ''
    form.resumen = data.actualizacion_resumen ?? ''
    form.estado = data.actualizacion_estado ?? 'borrador'
    form.fecha_publicacion = formatearFechaParaInput(data.actualizacion_fecha_publicacion)
    form.fecha_creacion = formatearFechaParaInput(data.actualizacion_fecha_creacion)
    form.fecha_programada = form.estado === 'programado'
      ? formatearFechaHoraParaInput(data.actualizacion_fecha_publicacion)
      : ''

    if (props.modoCorreccion && !form.fecha_publicacion) {
      form.fecha_publicacion = new Date().toISOString().split('T')[0]
    }

    form.area_servicio_id = data.actualizacion_area_servicio_id
      ? Number(data.actualizacion_area_servicio_id)
      : null

    form.categoria_ids = normalizarCategoriaIds(
      data.actualizacion_categoria_ids || data.actualizacion_categoria_id
    )

    const contenidoBD = data.actualizacion_contenido

    let editorData = {}
    if (contenidoBD) {
      try {
        editorData = typeof contenidoBD === 'string'
          ? JSON.parse(contenidoBD)
          : contenidoBD
      } catch (e) {
        console.error('Error al parsear el JSON del editor:', e)
      }
    }

    await nextTick()
    await initEditor(editorData)
  } catch (error) {
    console.error('Error al cargar registro:', error)
  } finally {
    cargando.value = false
    await nextTick()
    enfocarTitulo()
  }
}

const guardarCambios = async () => {
  if (form.resumen.length > LIMITE_RESUMEN) {
    errores.value.resumen = [`El resumen no puede superar los ${LIMITE_RESUMEN} caracteres.`]
    return
  }

  if (!formularioValido.value) return

  const estadoAEnviar = props.modoCorreccion ? 'publicado' : form.estado

  if (estadoAEnviar === 'programado') {
    const errorFecha = validarFechaProgramada(form.fecha_programada)
    if (errorFecha) {
      errores.value = { actualizacion_fecha_publicacion: [errorFecha] }
      return
    }
  }

  guardando.value = true
  errores.value = {}
  mensajeOk.value = ''

  let rutaImagen = form.imagen_destacada

  try {
    // Guardar contenido del editor
    let contenidoFinal = null
    if (editor.value) {
      contenidoFinal = await editor.value.save()
    }

    // Subir imagen destacada si hay una nueva
    if (archivoImagen.value) {
      const imgData = new FormData()
      imgData.append('imagen', archivoImagen.value)

      const res = await api.post('/subir-imagen-destacada', imgData, {
        headers: { 'Content-Type': 'multipart/form-data' }
      })

      rutaImagen = res.data.path
    }

    // Preparar payload
    const estadoFinal = props.modoCorreccion ? 'publicado' : form.estado
    const fechaPublicacionFinal = estadoFinal === 'publicado'
      ? (form.fecha_publicacion || new Date().toISOString().split('T')[0])
      : estadoFinal === 'programado'
        ? form.fecha_programada
        : form.fecha_publicacion

    const payload = {
      actualizacion_titulo: form.titulo,
      actualizacion_version: form.version,
      actualizacion_resumen: form.resumen,
      actualizacion_imagen_destacada: rutaImagen,
      actualizacion_estado: estadoFinal,
      actualizacion_fecha_publicacion: fechaPublicacionFinal,
      actualizacion_es_correccion: props.modoCorreccion,
      actualizacion_contenido: contenidoFinal,
      actualizacion_categoria_id: form.categoria_ids[0],
      actualizacion_categoria_ids: form.categoria_ids,
      actualizacion_area_servicio_id: form.area_servicio_id,
    }

    await api.put(`/actualizaciones/${props.id}`, payload)

    emit('guardado')

    // Ocultar mensaje después de 3 segundos
    setTimeout(() => {
      if (mensajeOk.value) mensajeOk.value = ''
    }, 3000)
  } catch (error: any) {
    console.error('Error al guardar:', error)

    if (error.response?.status === 422) {
      errores.value = error.response.data.errors || {}
    } else {
      mensajeOk.value = ''
      errores.value.general = ['Ocurrió un error al guardar. Por favor, intente nuevamente.']
    }
  } finally {
    guardando.value = false
  }
}

// Lifecycle hooks
onMounted(async () => {
  await cargarListas()
  const modalEl = document.getElementById('modalEditarRegistro')
  modalEl?.addEventListener('shown.bs.modal', enfocarTitulo)
})

onBeforeUnmount(() => {
  const modalEl = document.getElementById('modalEditarRegistro')
  modalEl?.removeEventListener('shown.bs.modal', enfocarTitulo)

  destruirEditor()
})

// Watchers
watch(
  () => props.id,
  () => {
    if (props.id) cargarRegistro()
  },
  { immediate: true }
)

watch(() => form.estado, (nuevoEstado, viejoEstado) => {
  if (cargando.value) return

  if (nuevoEstado === 'publicado') {
    if (!form.fecha_publicacion || ['inactivo', 'borrador', 'revision', 'programado'].includes(viejoEstado || '')) {
      const hoy = new Date()
      form.fecha_publicacion = hoy.toISOString().split('T')[0]
    }
    form.fecha_programada = ''
  } else if (nuevoEstado === 'programado') {
    if (!form.fecha_programada) {
      form.fecha_programada = fechaMinimaProgramada.value
    }
  } else if (['inactivo', 'borrador'].includes(nuevoEstado)) {
    form.fecha_publicacion = ''
    form.fecha_programada = ''
  }
})
</script>

<style scoped>
/* ─── Colores y utilidades ──────────────────────────────── */
.text-primary {
  color: var(--primary) !important;
}

.text-secondary {
  color: var(--secondary) !important;
}

.text-warning {
  color: var(--warning) !important;
}

.border-primary {
  border-color: var(--primary) !important;
}

.btn-primary {
  background-color: var(--primary);
  border-color: var(--primary);
  transition: all 0.2s ease;
}

.btn-primary:hover:not(:disabled) {
  background-color: var(--secondary);
  border-color: var(--secondary);
  transform: translateY(-1px);
}

.btn-primary:active {
  transform: translateY(0);
}

.btn-outline-secondary {
  color: var(--secondary);
  border-color: var(--secondary);
  transition: all 0.2s ease;
}

.btn-outline-secondary:hover:not(:disabled) {
  background-color: var(--secondary);
  border-color: var(--secondary);
  color: #fff;
  transform: translateY(-1px);
}

.alert-warning {
  background-color: rgba(252, 187, 28, 0.1);
  border-left: 4px solid var(--warning);
  color: #856404;
}

/* ─── Layout del modal ───────────────────────────────────── */
:global(#modalEditarRegistro .modal-dialog) {
  width: min(1400px, calc(100vw - 24px));
  max-width: min(1400px, calc(100vw - 24px));
  margin: 12px auto;
}

.modal-content {
  margin: none;
}

:global(#modalEditarRegistro .modal-content) {
  max-height: calc(100dvh - 24px);
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

:global(#modalEditarRegistro .modal-body) {
  flex: 1 1 auto;
  min-height: 0;
  overflow: hidden;
}

.edit-version-container {
  width: 100%;
  height: 100%;
  min-height: 0;
  padding: 0;
}

.edit-modal-content {
  height: 100%;
  min-height: 0;
}

/* ─── Grid de dos columnas + footer completo ───────────────── */
.edit-form-grid {
  display: grid;
  grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
  grid-template-rows: minmax(0, 1fr) auto;
  gap: 0 32px;
  align-items: stretch;
  height: calc(100dvh - 150px);
  max-height: calc(100dvh - 150px);
  min-height: 0;
  overflow: hidden;
}

.left-column {
  display: flex;
  flex-direction: column;
  gap: 12px;
  min-width: 0;
  min-height: 0;
  overflow-y: auto;
  overflow-x: hidden;
  padding-right: 6px;
}

.left-column .mb-4,
.left-column .form-group,
.left-column .row {
  margin-bottom: 0 !important;
}

.form-footer {
  grid-column: 1 / -1;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  margin-top: 18px;
  padding-top: 16px;
  border-top: 1px solid #e2e8f0;
  background: #ffffff;
}

.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  margin-left: auto;
}

.form-success-message {
  margin: 0;
  flex: 1;
}

/* ─── Columna del editor ────────────────────────────────── */
.editor-column {
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

.editor-column > :deep(.vista-previa-container) {
  flex: 1;
  min-height: 0;
}

.editor-wrapper {
  flex: 1;
  min-height: 0;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  background: #ffffff;
  overflow-y: auto;
  overflow-x: hidden;
  padding: 20px 24px;
}

.editor-wrapper:focus-within {
  border-color: var(--primary);
  box-shadow: 0 0 0 3px rgba(7, 126, 157, 0.12);
}

.editor-container {
  min-height: 100%;
  border: 0 !important;
  padding: 0 !important;
  background: transparent !important;
}

/* Editor.js interno */
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

/* Scrollbar del editor */
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

/* ─── Select de categorías ───────────────────────────────── */
.categoria-select-wrapper {
  position: relative;
  margin-bottom: 12px;
  width: 100%;
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
  width: 100%;
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
  min-width: 0;
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
  white-space: nowrap;
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
  flex-shrink: 0;
}

/* Dropdown */
.categoria-select-dropdown {
  position: absolute;
  top: calc(100% + 4px);
  left: 0;
  right: 0;
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  z-index: 1000;
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
  background: transparent;
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

.dropdown-options::-webkit-scrollbar-thumb:hover {
  background: var(--primary);
}

.categoria-counter {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-top: 8px;
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

/* ─── Estilos generales ──────────────────────────────────── */
.form-group {
  margin-bottom: 1.5rem;
}

.form-label {
  margin-bottom: 0.5rem;
  font-size: 0.9rem;
}

.form-control:focus,
.form-select:focus {
  border-color: var(--primary);
  box-shadow: 0 0 0 0.2rem rgba(7, 126, 157, 0.25);
}

.img-thumbnail {
  transition: transform 0.2s ease;
  cursor: pointer;
}

.img-thumbnail:hover {
  transform: scale(1.05);
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

/* ─── RESPONSIVE: LAPTOP / TABLET HORIZONTAL (≤1199px) ───── */
@media (max-width: 1199px) {
  :global(#modalEditarRegistro .modal-dialog) {
    width: calc(100vw - 24px);
    max-width: calc(100vw - 24px);
    margin: 12px auto;
  }

  :global(#modalEditarRegistro .modal-body) {
    overflow-y: auto;
    overflow-x: hidden;
    -webkit-overflow-scrolling: touch;
  }

  .edit-version-container,
  .edit-modal-content {
    height: auto;
    min-height: 0;
  }

  .edit-form-grid {
    grid-template-columns: 1fr;
    grid-template-rows: auto auto auto;
    gap: 22px;
    height: auto;
    max-height: none;
    overflow: visible;
  }

  .left-column {
    overflow: visible;
    padding-right: 0;
  }

  .editor-column {
    min-height: 360px;
    overflow: visible;
  }

  .editor-wrapper {
    min-height: 360px;
    max-height: none;
  }

  .editor-container {
    min-height: 320px;
  }

  .form-footer {
    margin-top: 0;
    padding: 16px 0 4px;
  }

  .btn {
    padding: 8px 16px;
    font-size: 0.9rem;
  }
}

/* ─── RESPONSIVE: TABLET VERTICAL Y MÓVIL (≤768px) ───────── */
@media (max-width: 768px) {
  :global(#modalEditarRegistro .modal-dialog) {
    width: calc(100vw - 16px);
    max-width: calc(100vw - 16px);
    margin: 8px auto;
  }

  :global(#modalEditarRegistro .modal-content) {
    max-height: calc(100dvh - 16px);
  }

  .edit-form-grid {
    gap: 20px;
  }

  .editor-column {
    min-height: 300px;
  }

  .editor-wrapper {
    min-height: 300px;
    padding: 16px;
  }

  .editor-container {
    min-height: 280px;
  }

  .form-footer {
    flex-direction: column;
    align-items: stretch;
    gap: 12px;
    padding-top: 18px;
    padding-bottom: max(6px, env(safe-area-inset-bottom));
  }

  .form-actions {
    width: 100%;
    flex-direction: column-reverse;
    gap: 10px;
    margin-left: 0;
  }

  .form-actions .btn {
    width: 100%;
    justify-content: center;
  }

  .form-success-message {
    width: 100%;
  }

  .form-group {
    margin-bottom: 1rem;
  }

  .form-label {
    font-size: 0.85rem;
  }

  .form-control,
  .form-select {
    font-size: 0.9rem;
    padding: 8px 12px;
  }

  .categoria-select-trigger {
    padding: 8px 12px;
    min-height: 40px;
  }

  .selected-tag {
    font-size: 0.65rem;
    padding: 2px 6px;
  }

  .dropdown-option {
    padding: 8px 12px;
  }

  .option-name {
    font-size: 0.8rem;
  }
}

/* ─── MÓVIL PEQUEÑO (<576px) ───────────────────────────── */
@media (max-width: 576px) {
  .edit-form-grid {
    gap: 18px;
  }

  .editor-column {
    min-height: 250px;
  }

  .editor-wrapper {
    min-height: 250px;
    padding: 14px;
  }

  .editor-container {
    min-height: 230px;
  }

  .form-label {
    font-size: 0.8rem;
  }

  .form-control,
  .form-select {
    font-size: 0.85rem;
    padding: 6px 10px;
  }

  .categoria-select-trigger {
    padding: 8px 10px;
    min-height: 38px;
  }

  .select-placeholder {
    font-size: 0.75rem;
  }

  .select-placeholder i {
    font-size: 0.8rem;
  }

  .selected-tag {
    font-size: 0.6rem;
    padding: 2px 5px;
  }

  .selected-more {
    font-size: 0.6rem;
    padding: 2px 5px;
  }

  .dropdown-option {
    padding: 8px 10px;
  }

  .option-name {
    font-size: 0.75rem;
  }

  .option-check i {
    font-size: 0.8rem;
  }

  .categoria-counter {
    flex-direction: column;
    align-items: flex-start;
    gap: 6px;
  }

  .counter-warning {
    align-self: flex-start;
  }

  .alert {
    font-size: 0.85rem;
    padding: 10px 12px;
  }

  .alert i {
    font-size: 0.9rem;
  }

  .btn {
    font-size: 0.85rem;
    padding: 8px 12px;
  }
}

/* ─── MÓVIL MUY PEQUEÑO (<375px) ───────────────────────── */
@media (max-width: 375px) {
  .form-label {
    font-size: 0.75rem;
  }

  .form-control,
  .form-select {
    font-size: 0.8rem;
    padding: 6px 8px;
  }

  .categoria-select-trigger {
    padding: 6px 8px;
  }

  .select-placeholder {
    font-size: 0.7rem;
    gap: 6px;
  }

  .select-placeholder i {
    font-size: 0.75rem;
  }

  .selected-tag {
    font-size: 0.65rem;
    padding: 1px 4px;
  }

  .selected-more {
    font-size: 0.7rem;
    padding: 1px 4px;
  }

  .dropdown-option {
    padding: 6px 8px;
  }

  .option-name {
    font-size: 0.7rem;
  }

  .option-check i {
    font-size: 0.8rem;
  }

  .counter-text,
  .counter-warning {
    font-size: 0.65rem;
  }
}

/* ─── ORIENTACIÓN HORIZONTAL EN MÓVIL ──────────────────── */
@media (max-width: 768px) and (orientation: landscape) {
  .editor-column {
    min-height: 220px;
  }

  .editor-wrapper {
    min-height: 220px;
  }

  .editor-container {
    min-height: 200px;
  }

  .dropdown-options {
    max-height: 150px;
  }
}
</style>