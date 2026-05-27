<template>
  <div class="container">
    <!-- Estado de carga -->
    <div v-if="cargando" class="text-center py-5">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Cargando...</span>
      </div>
      <p class="mt-2 text-secondary">Cargando actualización...</p>
    </div>

    <div v-else>
      <form @submit.prevent="guardarCambios">
        <!-- Alerta de corrección -->
        <div v-if="modoCorreccion" class="alert alert-warning mb-4">
          <i class="bi bi-exclamation-triangle-fill me-2"></i>
          <strong>Corrección pendiente.</strong>
          Realiza los ajustes solicitados. Al enviar la corrección, el registro quedará publicado y se notificará al
          supervisor del área.
        </div>

        <!-- Título -->
        <div class="col-md-12 mb-4">
          <label class="form-label fw-bold text-primary">
            Título <span class="text-danger">*</span>
          </label>
          <input 
            v-model="form.titulo" 
            type="text" 
            class="form-control" 
            :class="{ 'is-invalid': errores.titulo || errores.actualizacion_titulo }"
            ref="inputTitulo"
            placeholder="Ingrese el título de la actualización"
          />
          <div v-if="errores.titulo || errores.actualizacion_titulo" class="invalid-feedback">
            {{ errores.titulo?.[0] || errores.actualizacion_titulo?.[0] }}
          </div>
        </div>

        <div class="row">
          <!-- Versión -->
          <div class="col-md-6 mb-4">
            <label class="form-label fw-bold text-primary">Versión</label>
            <input 
              v-model="form.version" 
              type="text" 
              class="form-control" 
              :class="{ 'is-invalid': errores.version || errores.actualizacion_version }"
              placeholder="Ej: 1.0.0"
            />
            <div v-if="errores.version || errores.actualizacion_version" class="invalid-feedback">
              {{ errores.version?.[0] || errores.actualizacion_version?.[0] }}
            </div>
          </div>

          <!-- Imagen destacada -->
          <div class="col-md-6 mb-4">
            <label class="form-label fw-bold text-primary">Imagen destacada (Portada)</label>
            
            <div v-if="previewImagen || form.imagen_destacada" class="mb-3">
              <div class="position-relative d-inline-block">
                <img 
                  :src="previewImagen || obtenerUrlImagen(form.imagen_destacada)" 
                  alt="Portada actual"
                  class="img-thumbnail" 
                  style="max-height: 150px; border-radius: 8px;" 
                />
                <button 
                  v-if="previewImagen || form.imagen_destacada"
                  type="button"
                  class="btn btn-sm btn-danger position-absolute top-0 end-0 rounded-circle"
                  style="transform: translate(50%, -50%);"
                  @click="eliminarImagen"
                >
                  <i class="bi bi-x"></i>
                </button>
              </div>
              <div class="form-text mt-2">
                {{ previewImagen ? 'Nueva imagen seleccionada' : 'Imagen actual' }}
              </div>
            </div>

            <input 
              type="file" 
              class="form-control" 
              :class="{ 'is-invalid': errores.imagen_destacada }"
              accept="image/*" 
              @change="manejarImagen" 
            />
            <div v-if="errores.imagen_destacada" class="invalid-feedback">
              {{ errores.imagen_destacada?.[0] }}
            </div>
          </div>
        </div>

        <div class="row">
          <!-- Área -->
          <div class="col-md-6 mb-4">
            <label for="area" class="form-label fw-bold text-primary">
              Área <span class="text-danger">*</span>
            </label>
            <select 
              id="area" 
              class="form-select" 
              :class="{ 'is-invalid': errores.area_servicio_id || errores.actualizacion_area_servicio_id }"
              v-model="form.area_servicio_id" 
              required
            >
              <option value="" disabled>Selecciona un área...</option>
              <option v-for="area in listaAreas" :key="area.area_servicio_id" :value="area.area_servicio_id">
                {{ area.area_servicio_nombre }}
              </option>
            </select>
            <div v-if="errores.area_servicio_id || errores.actualizacion_area_servicio_id" class="invalid-feedback">
              {{ errores.area_servicio_id?.[0] || errores.actualizacion_area_servicio_id?.[0] }}
            </div>
          </div>

          <!-- Categoría -->
          <div class="col-md-6 mb-4">
            <label for="categoria" class="form-label fw-bold text-primary">
              Categoría <span class="text-danger">*</span>
            </label>
            <select 
              id="categoria" 
              class="form-select" 
              :class="{ 'is-invalid': errores.actualizacion_categoria_id }"
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
            <div v-if="errores.actualizacion_categoria_id" class="invalid-feedback">
              {{ errores.actualizacion_categoria_id?.[0] }}
            </div>
          </div>
        </div>

        <!-- Resumen -->
        <div class="mb-4">
          <label class="form-label fw-bold text-primary">Resumen</label>
          <textarea 
            v-model="form.resumen" 
            class="form-control" 
            :class="{ 'is-invalid': errores.resumen || errores.actualizacion_resumen }"
            rows="3" 
            placeholder="Breve descripción de la actualización..."
          ></textarea>
          <div v-if="errores.resumen || errores.actualizacion_resumen" class="invalid-feedback">
            {{ errores.resumen?.[0] || errores.actualizacion_resumen?.[0] }}
          </div>
        </div>

        <div class="row">
          <!-- Estado -->
          <div class="col-md-6 mb-4">
            <template v-if="!modoCorreccion">
              <label class="form-label fw-bold text-primary">Estado</label>
              <select 
                v-model="form.estado" 
                class="form-select" 
                :class="{ 'is-invalid': errores.estado || errores.actualizacion_estado }"
              >
                <option value="borrador">📝 Borrador</option>
                <option value="revision">🔍 Revisión</option>
                <option value="publicado">✅ Publicado</option>
                <option value="inactivo">⛔ Inactivo</option>
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
          <div class="col-md-6 mb-4">
            <template v-if="estadoParaVista !== 'publicado'">
              <label class="form-label fw-bold text-secondary">Fecha de creación</label>
              <input 
                v-model="form.fecha_creacion" 
                type="date" 
                class="form-control bg-light" 
                readonly 
                disabled 
              />
              <div class="form-text text-secondary mt-1">
                <i class="bi bi-info-circle me-1"></i>
                Registro no publicado. Mostrando fecha de creación.
              </div>
            </template>

            <template v-else>
              <label class="form-label fw-bold text-primary">Fecha de publicación</label>
              <input 
                v-model="form.fecha_publicacion" 
                type="date" 
                class="form-control border-primary bg-light" 
                :class="{ 'is-invalid': errores.fecha_publicacion || errores.actualizacion_fecha_publicacion }"
                readonly 
              />
              <div v-if="errores.fecha_publicacion || errores.actualizacion_fecha_publicacion" class="invalid-feedback">
                {{ errores.fecha_publicacion?.[0] || errores.actualizacion_fecha_publicacion?.[0] }}
              </div>
              <div class="form-text text-primary fw-bold mt-1">
                <i class="bi bi-check-circle-fill me-1"></i>
                El registro se publicará con esta fecha.
              </div>
            </template>
          </div>
        </div>

        <!-- Contenido del editor -->
        <div class="mb-4">
          <label class="form-label fw-bold text-primary">Contenido</label>
          <div ref="editorHolder" id="editorjs" class="editor-container border rounded p-3 bg-white"></div>
        </div>

        <!-- Botones de acción -->
        <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
          <button 
            type="button" 
            class="btn btn-outline-secondary" 
            @click="cerrarModal"
            :disabled="guardando"
          >
            <i class="bi bi-x-circle me-1"></i>
            Cancelar
          </button>

          <button 
            type="submit" 
            class="btn btn-primary" 
            :disabled="guardando || !formularioValido"
          >
            <span v-if="guardando" class="spinner-border spinner-border-sm me-2" role="status"></span>
            <i v-else :class="modoCorreccion ? 'bi bi-send-check' : 'bi bi-save'" class="me-1"></i>
            {{ textoBotonGuardar }}
          </button>
        </div>

        <!-- Mensaje de éxito -->
        <transition name="fade">
          <div v-if="mensajeOk" class="alert alert-success mt-4 mb-0">
            <i class="bi bi-check-circle-fill me-2"></i>
            {{ mensajeOk }}
          </div>
        </transition>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, reactive, ref, shallowRef, watch, nextTick, onBeforeUnmount, onMounted } from 'vue'
import api from '../../api/api'
import EditorJS from '@editorjs/editorjs'
import Header from '@editorjs/header'
import ImageTool from '@editorjs/image'
import List from '@editorjs/list'

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
const editor = shallowRef<EditorJS | null>(null)

// Estado
const cargando = ref(true)
const guardando = ref(false)
const mensajeOk = ref('')
const errores = ref<Record<string, string[]>>({})
const archivoImagen = ref<File | null>(null)
const previewImagen = ref<string | null>(null)
const listaAreas = ref<any[]>([])
const listaCategorias = ref<any[]>([])

// Formulario
const form = reactive({
  titulo: '',
  version: '',
  contenido: '',
  resumen: '',
  imagen_destacada: '',
  area_servicio_id: null as number | null,
  categoria_id: null as number | null,
  usuario_id_autor: null as number | null,
  estado: 'borrador',
  fecha_creacion: '',
  fecha_publicacion: ''
})

// Computed
const modoCorreccion = computed(() => props.modoCorreccion)
const estadoParaVista = computed(() => props.modoCorreccion ? 'publicado' : form.estado)

const textoBotonGuardar = computed(() => {
  if (guardando.value) return 'Procesando...'
  return props.modoCorreccion ? 'Enviar corrección' : 'Guardar cambios'
})

const formularioValido = computed(() => {
  return form.titulo.trim() !== '' && 
         form.area_servicio_id !== null && 
         form.categoria_id !== null
})

// Métodos
const manejarImagen = (event: Event) => {
  const target = event.target as HTMLInputElement
  if (target.files && target.files.length > 0) {
    const file = target.files[0]
    const maxSize = 5 * 1024 * 1024 // 5MB
    
    if (file.size > maxSize) {
      errores.value.imagen_destacada = ['La imagen no debe superar los 5MB']
      return
    }
    
    if (!file.type.startsWith('image/')) {
      errores.value.imagen_destacada = ['Solo se permiten archivos de imagen']
      return
    }
    
    archivoImagen.value = file
    previewImagen.value = URL.createObjectURL(file)
    delete errores.value.imagen_destacada
  } else {
    archivoImagen.value = null
    previewImagen.value = null
  }
}

const eliminarImagen = () => {
  archivoImagen.value = null
  previewImagen.value = null
  form.imagen_destacada = ''
}

const obtenerUrlImagen = (ruta: string) => {
  if (!ruta) return ''
  if (ruta.startsWith('http')) return ruta

  const backendUrl = import.meta.env.VITE_BACKEND_URL || 'http://localhost:8000'
  
  if (ruta.startsWith('/storage/')) return `${backendUrl}${ruta}`
  if (ruta.startsWith('storage/')) return `${backendUrl}/${ruta}`
  
  return `${backendUrl}/storage/${ruta}`
}

const formatearFechaParaInput = (fecha: any) => {
  if (!fecha) return ''
  const fechaObj = typeof fecha === 'number' && fecha < 10000000000
    ? new Date(fecha * 1000)
    : new Date(fecha)
  return isNaN(fechaObj.getTime()) ? '' : fechaObj.toISOString().split('T')[0]
}

const cargarListas = async () => {
  try {
    const [resAreas, resCategorias] = await Promise.all([
      api.get('/area-servicio'),
      api.get('/categorias'),
    ])

    listaAreas.value = resAreas.data?.data || []
    listaCategorias.value = resCategorias.data?.data || []
  } catch (error) {
    console.error('Error al cargar catálogos:', error)
  }
}

const initEditor = async (initialData: any = {}) => {
  if (editor.value) {
    await editor.value.destroy()
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
                const respuesta = await api.post('/subir-imagen-blog', formData)
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

    if (props.modoCorreccion && !form.fecha_publicacion) {
      form.fecha_publicacion = new Date().toISOString().split('T')[0]
    }

    form.area_servicio_id = data.actualizacion_area_servicio_id
      ? Number(data.actualizacion_area_servicio_id)
      : null

    form.categoria_id = data.actualizacion_categoria_id
      ? Number(data.actualizacion_categoria_id)
      : null

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
  if (!formularioValido.value) return
  
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
      actualizacion_categoria_id: form.categoria_id,
      actualizacion_area_servicio_id: form.area_servicio_id,
    }

    await api.put(`/actualizaciones/${props.id}`, payload)

    mensajeOk.value = props.modoCorreccion
      ? '✓ Corrección enviada correctamente'
      : '✓ Cambios guardados exitosamente'
    
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
      // Mostrar error general
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
  
  if (editor.value && typeof editor.value.destroy === 'function') {
    editor.value.destroy()
  }
  
  // Limpiar previews de imágenes
  if (previewImagen.value) {
    URL.revokeObjectURL(previewImagen.value)
  }
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
    if (!form.fecha_publicacion || ['inactivo', 'borrador', 'revision'].includes(viejoEstado || '')) {
      const hoy = new Date()
      form.fecha_publicacion = hoy.toISOString().split('T')[0]
    }
  } else if (['inactivo', 'borrador'].includes(nuevoEstado)) {
    form.fecha_publicacion = ''
  }
})
</script>

<style scoped>
/* Variables de color */
:root {
  --primary: #077E9D;
  --secondary: #025B7D;
  --warning: #FCBB1C;
}

/* Estilos base */
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

.alert-warning {
  background-color: rgba(252, 187, 28, 0.1);
  border-left: 4px solid var(--warning);
  color: #856404;
}

/* Editor de contenido */
:deep(.codex-editor__redactor) {
  padding-bottom: 20px !important;
  min-height: 300px;
}

.editor-container {
  min-height: 350px;
  transition: all 0.3s ease;
}

.editor-container:focus-within {
  box-shadow: 0 0 0 0.2rem rgba(7, 126, 157, 0.25);
  border-color: var(--primary);
}

/* Animaciones */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

/* Estilos de formulario */
.form-control:focus,
.form-select:focus {
  border-color: var(--primary);
  box-shadow: 0 0 0 0.2rem rgba(7, 126, 157, 0.25);
}

.form-label {
  margin-bottom: 0.5rem;
}

/* Badge de estado */
.badge-status {
  padding: 0.35rem 0.65rem;
  border-radius: 6px;
  font-size: 0.75rem;
  font-weight: 500;
}

/* Imagen thumbnail */
.img-thumbnail {
  transition: transform 0.2s ease;
  cursor: pointer;
}

.img-thumbnail:hover {
  transform: scale(1.05);
}

/* Responsive */
@media (max-width: 768px) {
  .container {
    padding: 0.5rem;
  }
  
  .d-flex {
    flex-direction: column;
    gap: 0.5rem !important;
  }
  
  .d-flex .btn {
    width: 100%;
  }
}

/* Scrollbar personalizada */
::-webkit-scrollbar {
  width: 8px;
  height: 8px;
}

::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 10px;
}

::-webkit-scrollbar-thumb {
  background: var(--primary);
  border-radius: 10px;
}

::-webkit-scrollbar-thumb:hover {
  background: var(--secondary);
}
</style>