<template>
  <div class="container">
    <div v-if="cargando" class="text-center py-5">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Cargando...</span>
      </div>
      <p class="mt-2">Cargando actualización...</p>
    </div>

    <div v-else>
      <form @submit.prevent="guardarCambios">
        <div class="col-md-12 mb-3">
          <label class="form-label fw-bold">Título</label>
          <input v-model="form.titulo" type="text" class="form-control" ref="inputTitulo" />
          <div v-if="errores.titulo || errores.actualizacion_titulo" class="text-danger small mt-1">
            {{ errores.titulo?.[0] || errores.actualizacion_titulo?.[0] }}
          </div>
        </div>

        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label fw-bold">Versión</label>
            <input v-model="form.version" type="text" class="form-control" />
            <div v-if="errores.version || errores.actualizacion_version" class="text-danger small mt-1">
              {{ errores.version?.[0] || errores.actualizacion_version?.[0] }}
            </div>
          </div>

          <div class="col-md-6 mb-3">
            <label class="form-label fw-bold">Imagen destacada (Portada)</label>

            <div v-if="previewImagen || form.imagen_destacada" class="mb-2">
              <img
                :src="previewImagen || obtenerUrlImagen(form.imagen_destacada)"
                alt="Portada actual"
                class="img-thumbnail"
                style="max-height: 150px; border-radius: 8px;"
              />
              <div class="form-text">
                {{ previewImagen ? 'Nueva imagen seleccionada' : 'Imagen actual' }}
              </div>
            </div>

            <input type="file" class="form-control" accept="image/*" @change="manejarImagen" />
          </div>
        </div>

        <div class="row">
          <div class="col-md-6 mb-3">
            <label for="area" class="form-label fw-bold">Área *</label>
            <select id="area" class="form-select" v-model="form.area_servicio_id" required>
              <option value="" disabled>Selecciona un área...</option>
              <option
                v-for="area in listaAreas"
                :key="area.area_servicio_id"
                :value="area.area_servicio_id"
              >
                {{ area.area_servicio_nombre }}
              </option>
            </select>
            <div v-if="errores.area_servicio_id || errores.actualizacion_area_servicio_id" class="text-danger small mt-1">
              {{ errores.area_servicio_id?.[0] || errores.actualizacion_area_servicio_id?.[0] }}
            </div>
          </div>

          <div class="col-md-6 mb-3">
            <label for="categoria" class="form-label fw-bold">Categoría *</label>
            <!--
              FIXES aplicados:
              1. v-model apunta a form.categoria_id (nombre unificado, tipo Number)
              2. :value de cada option usa Number() para evitar mismatch string/number
              3. Se añade :key con el mismo valor para forzar reactividad
            -->
            <select
              id="categoria"
              class="form-select"
              v-model="form.categoria_id"
              required
            >
              <option :value="null" disabled>Selecciona una categoría...</option>
              <option
                v-for="categoria in listaCategorias"
                :key="categoria.categoria_actualizacion_id"
                :value="Number(categoria.categoria_actualizacion_id)"
              >
                {{ categoria.categoria_actualizacion_nombre }}
              </option>
            </select>
            <div v-if="errores.actualizacion_categoria_id" class="text-danger small mt-1">
              {{ errores.actualizacion_categoria_id?.[0] }}
            </div>
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label fw-bold">Resumen</label>
          <textarea v-model="form.resumen" class="form-control" rows="3"></textarea>
          <div v-if="errores.resumen || errores.actualizacion_resumen" class="text-danger small mt-1">
            {{ errores.resumen?.[0] || errores.actualizacion_resumen?.[0] }}
          </div>
        </div>

        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label fw-bold">Estado</label>
            <select v-model="form.estado" class="form-select">
              <option value="borrador">Borrador</option>
              <option value="revision">Revisión</option>
              <option value="publicado">Publicado</option>
              <option value="inactivo">Inactivo</option>
            </select>
            <div v-if="errores.estado || errores.actualizacion_estado" class="text-danger small mt-1">
              {{ errores.estado?.[0] || errores.actualizacion_estado?.[0] }}
            </div>
          </div>

          <div class="col-md-6 mb-3">
            <template v-if="form.estado !== 'publicado'">
              <label class="form-label fw-bold">Fecha de creación</label>
              <input v-model="form.fecha_creacion" type="date" class="form-control bg-light" readonly disabled />
              <div class="form-text mt-1">
                Registro no publicado. Mostrando fecha de creación.
              </div>
            </template>

            <template v-else>
              <label class="form-label fw-bold text-success">Fecha de publicación</label>
              <input v-model="form.fecha_publicacion" type="date" class="form-control bg-light border-success" readonly />
              <div
                v-if="errores.fecha_publicacion || errores.actualizacion_fecha_publicacion"
                class="text-danger small mt-1"
              >
                {{ errores.fecha_publicacion?.[0] || errores.actualizacion_fecha_publicacion?.[0] }}
              </div>
              <div class="form-text mt-1 text-success fw-bold">
                <i class="bi bi-check-circle me-1"></i>
                El registro se publicará con esta fecha.
              </div>
            </template>
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label fw-bold">Contenido</label>
          <div ref="editorHolder" id="editorjs" class="editor-container border p-3 bg-white"></div>
        </div>

        <div class="d-flex justify-content-end gap-2 mt-4">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" ref="btnCerrarEdit">
            Cancelar
          </button>

          <button type="submit" class="btn-primary" :disabled="guardando">
            {{ guardando ? 'Guardando...' : 'Guardar cambios' }}
          </button>
        </div>

        <div v-if="mensajeOk" class="alert alert-success mt-3 mb-0">
          {{ mensajeOk }}
        </div>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { reactive, ref, shallowRef, watch, nextTick, onBeforeUnmount, onMounted } from 'vue'
import api from '../../api/api'
import type { NewVersion } from '../../types/newVersion'

import EditorJS from '@editorjs/editorjs'
import Header from '@editorjs/header'
import ImageTool from '@editorjs/image'
import List from '@editorjs/list'

const inputTitulo = ref<HTMLInputElement | null>(null)

const props = defineProps<{
  id: string | number
}>()

const emit = defineEmits(['guardado', 'cerrar'])

const cargando = ref(true)
const guardando = ref(false)
const mensajeOk = ref('')
const errores = ref<Record<string, string[]>>({})

const editorHolder = ref<HTMLElement | null>(null)
const editor = shallowRef<EditorJS | null>(null)

const archivoImagen = ref<File | null>(null)
const previewImagen = ref<string | null>(null)

const listaAreas = ref<any[]>([])
const listaCategorias = ref<any[]>([])

// ── Form unificado ────────────────────────────────────────────────
// Se usa "categoria_id" como nombre interno (Number) para evitar el
// mismatch de tipos entre lo que devuelve la API y lo que espera el select.
const form = reactive({
  titulo: '',
  version: '',
  contenido: '',
  resumen: '',
  imagen_destacada: '',
  area_servicio_id: null as number | null,
  categoria_id: null as number | null,   // ← nombre unificado, siempre Number
  usuario_id_autor: null as number | null,
  estado: 'borrador',
  fecha_creacion: '',
  fecha_publicacion: ''
})

const manejarImagen = (event: Event) => {
  const target = event.target as HTMLInputElement
  if (target.files && target.files.length > 0) {
    archivoImagen.value = target.files[0]
    previewImagen.value = URL.createObjectURL(target.files[0])
  } else {
    archivoImagen.value = null
    previewImagen.value = null
  }
}

const obtenerUrlImagen = (ruta: string) => {
  if (!ruta) return ''
  if (ruta.startsWith('http')) return ruta
  const baseUrl = import.meta.env.VITE_API_URL || 'http://localhost:8000'
  return `${baseUrl}/storage/${ruta}`
}

const formatearFechaParaInput = (fecha: any) => {
  if (!fecha) return ''
  const fechaObj = typeof fecha === 'number' && fecha < 10000000000
    ? new Date(fecha * 1000)
    : new Date(fecha)
  if (isNaN(fechaObj.getTime())) return ''
  return fechaObj.toISOString().split('T')[0]
}

const cargarListas = async () => {
  try {
    const resAreas = await api.get('/admin/area-servicio')
    listaAreas.value = resAreas.data.data
  } catch (error) {
    console.error('Error al cargar áreas:', error)
  }

  try {
    const resCategorias = await api.get('/admin/categorias')
    listaCategorias.value = resCategorias.data.data
  } catch (error) {
    console.error('Error al cargar categorías:', error)
  }
}

const initEditor = (initialData: any = {}) => {
  if (editor.value) {
    editor.value.destroy()
  }

  editor.value = new EditorJS({
    holder: editorHolder.value!,
    data: initialData,
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
                alert('Hubo un error al subir la imagen.')
                return { success: 0 }
              }
            }
          }
        }
      }
    }
  })
}

watch(() => form.estado, (nuevoEstado, viejoEstado) => {
  if (cargando.value) return

  if (nuevoEstado === 'publicado') {
    if (
      !form.fecha_publicacion ||
      viejoEstado === 'inactivo' ||
      viejoEstado === 'borrador' ||
      viejoEstado === 'revision'
    ) {
      const hoy = new Date();
      const año = hoy.getFullYear();
      const mes = String(hoy.getMonth() + 1).padStart(2, '0');
      const dia = String(hoy.getDate()).padStart(2, '0');

      form.fecha_publicacion = `${año}-${mes}-${dia}`;
    }
  } else if (nuevoEstado === 'inactivo' || nuevoEstado === 'borrador') {
    form.fecha_publicacion = ''
  }
})

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

  try {
    const respuesta = await api.get(`/admin/actualizaciones/${props.id}`)
    const data = respuesta.data.data

    form.titulo               = data.actualizacion_titulo ?? ''
    form.version              = data.actualizacion_version ?? ''
    form.imagen_destacada     = data.actualizacion_imagen_destacada ?? ''
    form.resumen              = data.actualizacion_resumen ?? ''
    form.estado               = data.actualizacion_estado ?? 'borrador'
    form.fecha_publicacion    = formatearFechaParaInput(data.actualizacion_fecha_publicacion)
    form.fecha_creacion       = formatearFechaParaInput(data.actualizacion_fecha_creacion)

    // ── FIX: convertir siempre a Number para que coincida con el :value del option ──
    form.area_servicio_id = data.actualizacion_area_servicio_id
      ? Number(data.actualizacion_area_servicio_id)
      : null

    form.categoria_id = data.actualizacion_categoria_id
      ? Number(data.actualizacion_categoria_id)
      : null

    const contenidoBD = data.actualizacion_contenido

    await nextTick()

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

    initEditor(editorData)
  } catch (error) {
    console.error('Error al cargar registro:', error)
  } finally {
    cargando.value = false
    await nextTick()
    setTimeout(() => { inputTitulo.value?.focus() }, 100)
  }
}

const guardarCambios = async () => {
  guardando.value = true
  errores.value = {}
  mensajeOk.value = ''

  let rutaImagen = form.imagen_destacada

  try {
    let contenidoFinal = null

    if (editor.value) {
      contenidoFinal = await editor.value.save()
      form.contenido = JSON.stringify(contenidoFinal)
    }

    if (archivoImagen.value) {
      const imgData = new FormData()
      imgData.append('imagen', archivoImagen.value)
      const res = await api.post('/admin/subir-imagen-portada', imgData, {
        headers: { 'Content-Type': 'multipart/form-data' }
      })
      rutaImagen = res.data.path
    }

    const payload = {
      actualizacion_titulo:             form.titulo,
      actualizacion_version:            form.version,
      actualizacion_resumen:            form.resumen,
      actualizacion_imagen_destacada:   rutaImagen,
      actualizacion_estado:             form.estado,
      actualizacion_fecha_publicacion:  form.fecha_publicacion,
      actualizacion_contenido:          contenidoFinal,
      actualizacion_area_servicio_id:   form.area_servicio_id,
      actualizacion_categoria_id:       form.categoria_id,   // ← nombre correcto para la API
    }

    await api.post(`/admin/actualizaciones/${props.id}`, payload)

    mensajeOk.value = 'Guardado exitosamente.'
    emit('guardado')
  } catch (error: any) {
    console.error('Error al guardar:', error)
    if (error.response?.status === 422) {
      errores.value = error.response.data.errors || {}
    }
  } finally {
    guardando.value = false
  }
}

onMounted(async () => {
  await cargarListas()
  const modalEl = document.getElementById('modalEditarRegistro')
  modalEl?.addEventListener('shown.bs.modal', enfocarTitulo)
})

onBeforeUnmount(() => {
  const modalEl = document.getElementById('modalEditarRegistro')
  modalEl?.removeEventListener('shown.bs.modal', enfocarTitulo)
  if (editor.value && typeof editor.value.destroy === 'function') {
    editor.value.destroy()
  }
})

watch(
  () => props.id,
  () => { if (props.id) cargarRegistro() },
  { immediate: true }
)
</script>

<style scoped>
:deep(.codex-editor__redactor) {
  padding-bottom: 20px !important;
}

.editor-container {
  min-height: 150px;
}
</style>