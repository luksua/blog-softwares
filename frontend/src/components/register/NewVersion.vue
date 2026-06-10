<template>
  <div>
    <form @submit.prevent="guardarRegistro" class="form-container-x">
      <!-- Indicador de autosave -->
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

      <div class="mb-3 pt-2">
        <label for="titulo" class="form-label fw-bold text-primary">Título *</label>
        <input ref="tituloInput" type="text" id="titulo" class="form-control" v-model="registro.titulo" required />
      </div>

      <div class="row">
        <div class="col-md-6 mb-3">
          <label for="version" class="form-label fw-bold text-primary">Número de Versión</label>
          <input type="text" id="version" class="form-control" v-model="registro.version" required />
        </div>
        <div class="col-md-6 mb-3">
          <label for="miniatura" class="form-label fw-bold text-primary">Portada</label>
          <input type="file" id="miniatura" class="form-control" accept="image/*" @change="manejarArchivoMiniatura" />
          <div v-if="previewMiniatura" class="mt-2 text-center">
            <img :src="previewMiniatura" alt="Vista previa" class="img-thumbnail"
              style="max-height: 120px; border-radius: 8px;" />
          </div>
        </div>
      </div>

      <div class="mb-3">
        <label for="resumen" class="form-label fw-bold text-primary">Resumen</label>
        <textarea id="resumen" class="form-control" v-model="registro.resumen" rows="2" required></textarea>
      </div>

      <div class="editor-column">
        <label class="form-label fw-bold text-primary">Contenido *</label>
        <div class="editor-wrapper">
          <div id="editorjs"></div>
        </div>
      </div>

      <div class="row">

        <div class="col-md-6 mb-3">
          <label class="form-label fw-bold text-primary">
            Categorías *
            <small class="text-muted">(máximo 3)</small>
          </label>

          <!-- Select moderno -->
          <div class="categoria-select-wrapper"
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
                    disabled: !categoriaSeleccionada(Number(categoria.categoria_actualizacion_id)) && categoriasSeleccionadas.length >= 3
                  }" @click="toggleCategoria(Number(categoria.categoria_actualizacion_id))"
                  :disabled="!categoriaSeleccionada(Number(categoria.categoria_actualizacion_id)) && categoriasSeleccionadas.length >= 3">
                  <span class="option-name">{{ categoria.categoria_actualizacion_nombre }}</span>
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

          <!-- Etiquetas de seleccionadas debajo -->
          <!-- <div v-if="categoriasSeleccionadas.length > 0" class="categorias-seleccionadas">
            <span class="seleccionadas-label">Seleccionadas:</span>
            <div class="seleccionadas-labels">
              <span v-for="categoria in categoriasSeleccionadas" :key="categoria.id" class="label-categoria">
                {{ categoria.nombre }}
                <button type="button" class="label-remove" @click="toggleCategoria(categoria.id)">
                  <i class="bi bi-x"></i>
                </button>
              </span>
            </div>
          </div> -->

          <!-- Contador -->
          <div class="categoria-counter">
            <span class="counter-text">{{ categoriasSeleccionadas.length }}/3 categorías</span>
            <span v-if="categoriasSeleccionadas.length >= 3" class="counter-warning">
              <i class="bi bi-exclamation-triangle-fill"></i> Límite alcanzado
            </span>
          </div>
        </div>

        <div class="col-md-6 mb-3">
          <label for="area_servicio" class="form-label fw-bold text-primary">Área *</label>
          <select id="area_servicio" class="form-select" v-model="registro.area_servicio_id" required>
            <option value="" disabled>Selecciona un área...</option>
            <option v-for="area in listaAreas" :key="area.area_servicio_id" :value="area.area_servicio_id">
              {{ area.area_servicio_nombre }}
            </option>
          </select>
        </div>

        <input type="number" id="usuario_id_autor" class="form-control" v-model="registro.usuario_id_autor" disabled
          hidden required />

      </div>

      <div class="row">
        <div class="col-md-6 mb-3">
          <label for="estado" class="form-label fw-bold text-primary">Estado</label>
          <select id="estado" class="form-select" v-model="registro.estado" required>
            <option v-for="estado in listaEstados" :key="estado.id" :value="estado.id">
              {{ estado.nombre }}
            </option>
          </select>
        </div>
        <div class="col-md-6 mb-3">
          <label for="fecha_publicacion" class="form-label fw-bold text-primary">Fecha:</label>
          <input type="date" id="fecha_publicacion" class="form-control" v-model="registro.fecha_publicacion" required
            disabled />
        </div>
      </div>


      <div class="d-flex justify-content-end gap-2 mt-4">
        <button type="button" class="btn btn-outline-secondary " data-bs-dismiss="modal"><i
            class="bi bi-x-circle me-1"></i> Cancelar</button>
        <button type="submit" class="btn-primary" :disabled="enviando">
          {{ enviando ? 'Guardando...' : 'Publicar Registro' }}
        </button>
      </div>
    </form>
  </div>
</template>

<script setup lang="ts">
import { reactive, ref, onMounted, onBeforeUnmount, watch, nextTick, computed } from 'vue'
import api from '../../api/api'
import type { NewVersion } from '../../types/newVersion'
import type { Area } from '../../types/areas'
import { toast } from 'vue-sonner'
import { Modal } from 'bootstrap'

import EditorJS from '@editorjs/editorjs'
import Header from '@editorjs/header'
import ImageTool from '@editorjs/image'
import List from '@editorjs/list'
import type { Category } from '../../types/categorias'

const tituloInput = ref<HTMLInputElement | null>(null)

// ── Clave del borrador ────────────────────────────────────────────
const DRAFT_KEY = 'draft_nueva_actualizacion'

// ── Archivos ──────────────────────────────────────────────────────
const archivoMiniatura = ref<File | null>(null)
const previewMiniatura = ref<string | null>(null)

const manejarArchivoMiniatura = (event: Event) => {
  const target = event.target as HTMLInputElement
  if (target.files && target.files.length > 0) {
    const file = target.files[0]
    archivoMiniatura.value = file
    previewMiniatura.value = URL.createObjectURL(file)
  } else {
    archivoMiniatura.value = null
    previewMiniatura.value = null
  }
}

// ── Registro ──────────────────────────────────────────────────────
const idUsuarioLogueado = 1

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
  imagenes_quill: []
})

const registro = reactive<NewVersion>(registroVacio())


const normalizarCategoriaIds = (valor: any): number[] => {
  const ids = Array.isArray(valor) ? valor : (valor ? [valor] : [])

  return ids
    .map((id) => Number(id))
    .filter((id, index, array) => Number.isFinite(id) && id > 0 && array.indexOf(id) === index)
    .slice(0, 3)
}

const categoriaSeleccionada = (categoriaId: number) => {
  return registro.actualizacion_categoria_ids.includes(categoriaId)
}

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
  }),
  () => { programarAutosave() },
  { deep: true }
)

// ── Listas ────────────────────────────────────────────────────────
const listaAreas = ref<Area[]>([])
const listaCategorias = ref<Category[]>([])
const listaEstados = ref<{ id: string; nombre: string }[]>([])
const enviando = ref(false)
const emit = defineEmits(['cerrar', 'recargar-lista'])

// ── Editor.js ─────────────────────────────────────────────────────
const editorInstance = ref<EditorJS | null>(null)

const enfocarTitulo = async () => {
  await nextTick()
  tituloInput.value?.focus()
}

onMounted(async () => {
  try {
    const resAreas = await api.get('/area-servicio')
    listaAreas.value = resAreas.data.data
  } catch (error) {
  }

  try {
    const resCategorias = await api.get('/categorias')
    listaCategorias.value = resCategorias.data.data
  } catch (error) {
    console.error('Error al cargar las categorías:', error)
  }

  try {
    const resEstados = await api.get('/estados-actualizacion')
    listaEstados.value = resEstados.data.data
  } catch (error) {
    console.error('Error al cargar los estados:', error)
  }

  editorInstance.value = new EditorJS({
    holder: 'editorjs',
    placeholder: 'Escribe tu actualización aquí. Puedes arrastrar imágenes...',
    onReady: () => {
      cargarBorrador()
    },
    onChange: () => {
      programarAutosave()
    },
    tools: {
      header: {
        class: Header as any,
        inlineToolbar: true,
        config: {
          placeholder: 'Escribe un subtítulo',
          levels: [2, 3, 4, 5, 6],
          defaultLevel: 2,
        },
      },
      list: List,
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
            }
          }
        }
      }
    }
  })
  const modalEl = document.getElementById('modalNuevoRegistro')

  modalEl?.addEventListener('shown.bs.modal', enfocarTitulo)
})

onBeforeUnmount(() => {
  const modalEl = document.getElementById('modalNuevoRegistro')
  modalEl?.removeEventListener('shown.bs.modal', enfocarTitulo)

  guardarBorrador()
  if (autoguardadoTimeout) clearTimeout(autoguardadoTimeout)
  if (editorInstance.value) editorInstance.value.destroy()
})

// ── Guardar registro ──────────────────────────────────────────────
const guardarRegistro = async () => {
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
    formData.append('actualizacion_titulo', registro.titulo)
    formData.append('actualizacion_version', registro.version)
    formData.append('actualizacion_contenido', JSON.stringify(outputData))
    formData.append('actualizacion_resumen', registro.resumen)
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
    formData.append('actualizacion_estado', registro.estado)
    formData.append('actualizacion_fecha_publicacion', registro.fecha_publicacion || '')

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

  archivoMiniatura.value = null
  previewMiniatura.value = null

  const inputMiniatura = document.getElementById('miniatura') as HTMLInputElement
  if (inputMiniatura) inputMiniatura.value = ''

  if (editorInstance.value) editorInstance.value.clear()
}

const selectAbierto = ref(false)
const busquedaCategoria = ref('')

// Categorías filtradas por búsqueda
const categoriasFiltradas = computed(() => {
  if (!busquedaCategoria.value) return listaCategorias.value
  const busqueda = busquedaCategoria.value.toLowerCase()
  return listaCategorias.value.filter(cat =>
    cat.categoria_actualizacion_nombre.toLowerCase().includes(busqueda)
  )
})

// Computed para obtener las categorías seleccionadas con sus detalles
const categoriasSeleccionadas = computed(() => {
  return registro.actualizacion_categoria_ids
    .map(id => {
      const categoria = listaCategorias.value.find(c => Number(c.categoria_actualizacion_id) === id)
      return categoria ? {
        id: Number(categoria.categoria_actualizacion_id),
        nombre: categoria.categoria_actualizacion_nombre
      } : null
    })
    .filter(c => c !== null)
})

const toggleSelect = () => {
  selectAbierto.value = !selectAbierto.value
  if (!selectAbierto.value) {
    busquedaCategoria.value = ''
  }
}

const toggleCategoria = (categoriaId: number) => {
  const index = registro.actualizacion_categoria_ids.indexOf(categoriaId)

  if (index > -1) {
    registro.actualizacion_categoria_ids.splice(index, 1)
  } else {
    if (registro.actualizacion_categoria_ids.length < 3) {
      registro.actualizacion_categoria_ids.push(categoriaId)
    } else {
      toast.warning('Solo puedes seleccionar máximo 3 categorías')
    }
  }
}

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
:root {
  --primary: #077E9D;
  --secondary: #025B7D;
  --warning: #FCBB1C;
}

.text-primary {
  color: var(--primary) !important;
}

.btn-primary {
  background-color: var(--primary);
  border-color: var(--primary);
  color: white;
  padding: 0.375rem 0.75rem;
  border-radius: 0.375rem;
  transition: all 0.2s ease;
}

.btn-primary:hover:not(:disabled) {
  background-color: var(--secondary);
  border-color: var(--secondary);
}

.btn-secondary {
  background-color: #6c757d;
  border-color: #6c757d;
  color: white;
  padding: 0.375rem 0.75rem;
  border-radius: 0.375rem;
  transition: all 0.2s ease;
}

.btn-secondary:hover {
  background-color: #5a6268;
  border-color: #545b62;
}

.form-control:focus,
.form-select:focus {
  border-color: var(--primary);
  box-shadow: 0 0 0 0.2rem rgba(7, 126, 157, 0.25);
}

:deep(.codex-editor__redactor) {
  padding-bottom: 40px !important;
}

.editor-container {
  background-color: white;
  border-radius: 4px;
  min-height: 300px;
}

.autosave-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 10px 14px;
  background: #f8fafc;
  border: 1px solid #e1e7f0;
  border-radius: 10px;
  margin-bottom: 16px;
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

.btn-primary {
  background-color: var(--primary);
  border-color: var(--primary);
}

.btn-primary:hover {
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
  color: white;
}

/* Estilos del select moderno */
.categoria-select-wrapper {
  position: relative;
  margin-bottom: 12px;
}

.categoria-select-trigger {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 10px 12px;
  background: white;
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

/* Dropdown */
.categoria-select-dropdown {
  position: absolute;
  top: calc(100% + 4px);
  left: 0;
  right: 0;
  background: white;
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

/* Scrollbar personalizada */
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

/* Etiquetas de seleccionadas */
.categorias-seleccionadas {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 8px;
  margin-top: 8px;
  padding: 8px 0;
}

.seleccionadas-label {
  font-size: 0.7rem;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.seleccionadas-labels {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
}

.label-categoria {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 3px 6px 3px 8px;
  background: #f1f5f9;
  border-radius: 12px;
  font-size: 0.7rem;
  font-weight: 500;
  color: #334155;
  transition: all 0.2s ease;
}

.label-categoria:hover {
  background: #e2e8f0;
}

.label-remove {
  background: transparent;
  border: none;
  cursor: pointer;
  padding: 0;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 14px;
  height: 14px;
  border-radius: 50%;
  transition: all 0.2s ease;
}

.label-remove i {
  font-size: 0.6rem;
  color: #94a3b8;
}

.label-remove:hover i {
  color: #ef4444;
}

/* Contador */
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

/* Responsive */
@media (max-width: 640px) {
  .categorias-seleccionadas {
    flex-direction: column;
    align-items: flex-start;
  }

  .selected-tags {
    max-width: 200px;
  }

  .selected-tag {
    font-size: 0.65rem;
  }
}

/* ── Layout principal del formulario: 2 columnas ── */
.form-container-x {
  display: grid;
  grid-template-columns: 1fr 1fr;
  grid-template-rows: auto;
  gap: 0 32px;
  align-items: start;
}

/* ── Columna izquierda: todo menos el editor ── */
.autosave-bar {
  grid-column: 1;
}

/* Apilamos todos los campos directos del form en col 1 */
.form-container-x>.mb-3,
.form-container-x>.row {
  grid-column: 1;
}

/* ── Botones: abajo de la col izquierda ── */
.form-container-x>.d-flex {
  grid-column: 1;
  margin-top: auto;
}

/* ── Editor: columna derecha, ocupa toda la altura ── */
.editor-column {
  grid-column: 2;
  grid-row: 1 / -1;
  /* ocupa todas las filas */
  display: flex;
  flex-direction: column;
  gap: 8px;
  position: sticky;
  top: 0;
}

.editor-label {
  font-weight: 700;
  color: var(--primary);
  font-size: 0.9rem;
  margin-bottom: 4px;
}

.editor-wrapper {
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  background: white;
  min-height: 500px;
  flex: 1;
  overflow-y: auto;
  padding: 8px 0;
}

.editor-wrapper:focus-within {
  border-color: var(--primary);
  box-shadow: 0 0 0 3px rgba(7, 126, 157, 0.12);
}

/* ── Responsive: vuelve a 1 columna en móvil ── */
@media (max-width: 768px) {
  .form-container-x {
    grid-template-columns: 1fr;
  }

  .autosave-bar,
  .form-container-x>.mb-3,
  .form-container-x>.row,
  .form-container-x>.d-flex,
  .editor-column {
    grid-column: 1;
    grid-row: auto;
  }

  .editor-column {
    position: relative;
  }
}
</style>
