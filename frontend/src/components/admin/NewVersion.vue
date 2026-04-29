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
        <label for="titulo" class="form-label fw-bold">Título *</label>
        <input type="text" id="titulo" class="form-control" v-model="registro.titulo" required />
      </div>

      <div class="row">
        <div class="col-md-6 mb-3">
          <label for="version" class="form-label fw-bold">Número de Versión</label>
          <input type="text" id="version" class="form-control" v-model="registro.version" required />
        </div>
        <div class="col-md-6 mb-3">
          <label for="miniatura" class="form-label fw-bold">Portada</label>
          <input type="file" id="miniatura" class="form-control" accept="image/*" @change="manejarArchivoMiniatura" />
          <div v-if="previewMiniatura" class="mt-2 text-center">
            <img :src="previewMiniatura" alt="Vista previa" class="img-thumbnail"
              style="max-height: 120px; border-radius: 8px;" />
          </div>
        </div>
      </div>

      <div class="mb-3">
        <label for="resumen" class="form-label fw-bold">Resumen</label>
        <textarea id="resumen" class="form-control" v-model="registro.resumen" rows="2" required></textarea>
      </div>

      <div class="mb-4">
        <label class="form-label fw-bold">Contenido *</label>
        <div id="editorjs" class="editor-container border p-3"></div>
      </div>

      <div class="row">
        <div class="col-md-6 mb-3">
          <label for="area_servicio" class="form-label fw-bold">Área *</label>
          <select id="area_servicio" class="form-select" v-model="registro.area_servicio_id" required>
            <option value="" disabled>Selecciona un área...</option>
            <option v-for="area in listaAreas" :key="area.area_servicio_id" :value="area.area_servicio_id">
              {{ area.area_servicio_nombre }}
            </option>
          </select>
        </div>

        <div class="col-md-6 mb-3">
          <label for="usuario_id_autor" class="form-label fw-bold">ID Autor:</label>
          <input type="number" id="usuario_id_autor" class="form-control" v-model="registro.usuario_id_autor"
            disabled required />
        </div>
      </div>

      <div class="row">
        <div class="col-md-6 mb-3">
          <label for="estado" class="form-label fw-bold">Estado</label>
          <select id="estado" class="form-select" v-model="registro.estado" required>
            <option v-for="estado in listaEstados" :key="estado.id" :value="estado.id">
              {{ estado.nombre }}
            </option>
          </select>
        </div>
        <div class="col-md-6 mb-3">
          <label for="fecha_publicacion" class="form-label fw-bold">Fecha:</label>
          <input type="date" id="fecha_publicacion" class="form-control" v-model="registro.fecha_publicacion"
            required disabled />
        </div>
      </div>

      <div class="d-flex justify-content-end gap-2 mt-4">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn-primary" :disabled="enviando">
          {{ enviando ? 'Guardando...' : 'Publicar Registro' }}
        </button>
      </div>
    </form>
  </div>
</template>

<script setup lang="ts">
import { reactive, ref, onMounted, onBeforeUnmount, watch } from 'vue'
import api from '../../api/api'
import type { NewVersion } from '../../types/newVersion'
import type { Area } from '../../types/areas'
import { toast } from 'vue-sonner'
import { Modal } from 'bootstrap'

import EditorJS from '@editorjs/editorjs'
import Header from '@editorjs/header'
import ImageTool from '@editorjs/image'
import List from '@editorjs/list'

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
  usuario_id_autor: idUsuarioLogueado,
  estado: 'borrador',
  fecha_creacion: new Date().toISOString().split('T')[0],
  fecha_publicacion: new Date().toISOString().split('T')[0],
  imagenes_quill: []
})

const registro = reactive<NewVersion>(registroVacio())

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
      titulo:            draft.titulo            || '',
      version:           draft.version           || '',
      resumen:           draft.resumen           || '',
      area_servicio_id:  draft.area_servicio_id  || '',
      estado:            draft.estado            || 'borrador',
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
      titulo:            registro.titulo,
      version:           registro.version,
      resumen:           registro.resumen,
      area_servicio_id:  registro.area_servicio_id,
      estado:            registro.estado,
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
    titulo:            registro.titulo,
    version:           registro.version,
    resumen:           registro.resumen,
    area_servicio_id:  registro.area_servicio_id,
    estado:            registro.estado,
    fecha_publicacion: registro.fecha_publicacion,
  }),
  () => { programarAutosave() },
  { deep: true }
)

// ── Listas ────────────────────────────────────────────────────────
const listaAreas = ref<Area[]>([])
const listaEstados = ref<{ id: string; nombre: string }[]>([])
const enviando = ref(false)
const emit = defineEmits(['cerrar', 'recargar-lista'])

// ── Editor.js ─────────────────────────────────────────────────────
const editorInstance = ref<EditorJS | null>(null)

onMounted(async () => {
  try {
    const resAreas = await api.get('/admin/area-servicio')
    listaAreas.value = resAreas.data.data
  } catch (error) {
    console.error('Error al cargar las áreas:', error)
  }

  try {
    const resEstados = await api.get('/admin/estados-actualizacion')
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
      header: Header,
      list: List,
      image: {
        class: ImageTool,
        config: {
          uploader: {
            async uploadByFile(file: File) {
              try {
                const formData = new FormData()
                formData.append('imagen', file)
                const respuesta = await api.post('/admin/subir-imagen-blog', formData)
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
})

onBeforeUnmount(() => {
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
    formData.append('actualizacion_titulo',            registro.titulo)
    formData.append('actualizacion_version',           registro.version)
    formData.append('actualizacion_contenido',         JSON.stringify(outputData))
    formData.append('actualizacion_resumen',           registro.resumen)
    formData.append('actualizacion_area_servicio_id',  String(registro.area_servicio_id))
    formData.append('actualizacion_usuario_id_autor',  String(registro.usuario_id_autor))
    formData.append('actualizacion_estado',            registro.estado)
    formData.append('actualizacion_fecha_publicacion', registro.fecha_publicacion || '')

    if (archivoMiniatura.value) {
      formData.append('actualizacion_imagen_destacada', archivoMiniatura.value)
    }

    const respuesta = await api.post('/admin/actualizaciones', formData, {
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
    const modalEl = document.getElementById('modalNuevaActualizacion') // el id de tu modal
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
</script>

<style scoped>

:deep(.codex-editor__redactor) {
  padding-bottom: 40px !important; /* el valor que prefieras */
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

.autosave-guardando { color: #f59e0b; display: flex; align-items: center; gap: 6px; }
.autosave-ok        { color: #16a34a; display: flex; align-items: center; gap: 6px; }
.autosave-vacio     { color: #94a3b8; }

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
  0%, 100% { opacity: 1;   transform: scale(1);   }
  50%       { opacity: 0.4; transform: scale(0.8); }
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
</style>