<template>
  <div class="edit-version-container">
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
        <div class="form-group mb-4">
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
          <div class="col-12 col-sm-6 mb-4">
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
          <div class="col-12 col-sm-6 mb-4">
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
          <div class="col-12 col-md-6 mb-4">
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

          <!-- Categorías - Select Moderno Responsive -->
          <div class="col-12 col-md-6 mb-4">
            <label class="form-label fw-bold text-primary">
              Categorías <span class="text-danger">*</span>
              <small class="text-muted">(máximo 3)</small>
            </label>

            <!-- Select moderno -->
            <div class="categoria-select-wrapper" :class="{ open: selectAbierto, 'has-selection': categoriasSeleccionadas.length > 0 }">
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
                  <input 
                    type="text" 
                    v-model="busquedaCategoria" 
                    placeholder="Buscar categoría..."
                    @click.stop
                  />
                </div>
                <div class="dropdown-options">
                  <button
                    type="button"
                    v-for="categoria in categoriasFiltradas"
                    :key="categoria.categoria_actualizacion_id"
                    class="dropdown-option"
                    :class="{
                      selected: categoriaSeleccionada(Number(categoria.categoria_actualizacion_id)),
                      disabled: !categoriaSeleccionada(Number(categoria.categoria_actualizacion_id)) && categoriasSeleccionadas.length >= 3
                    }"
                    @click="toggleCategoria(Number(categoria.categoria_actualizacion_id))"
                    :disabled="!categoriaSeleccionada(Number(categoria.categoria_actualizacion_id)) && categoriasSeleccionadas.length >= 3"
                  >
                    <span class="option-name">{{ categoria.categoria_actualizacion_nombre }}</span>
                    <span class="option-check">
                      <i v-if="categoriaSeleccionada(Number(categoria.categoria_actualizacion_id))" class="bi bi-check-lg"></i>
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
                <span
                  v-for="categoria in categoriasSeleccionadas"
                  :key="categoria.id"
                  class="label-categoria"
                  :style="{ backgroundColor: getCategoriaColor(categoria.nombre) + '15', borderColor: getCategoriaColor(categoria.nombre) }"
                >
                  <i :class="getCategoriaIcon(categoria.nombre)" :style="{ color: getCategoriaColor(categoria.nombre) }"></i>
                  {{ categoria.nombre }}
                  <button
                    type="button"
                    class="label-remove"
                    @click="toggleCategoria(categoria.id)"
                  >
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

            <div v-if="errores.actualizacion_categoria_ids || errores.actualizacion_categoria_id" class="invalid-feedback d-block">
              {{ errores.actualizacion_categoria_ids?.[0] || errores.actualizacion_categoria_id?.[0] }}
            </div>
          </div>
        </div>

        <!-- Resumen -->
        <div class="form-group mb-4">
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
          <div class="col-12 col-md-6 mb-4">
            <template v-if="!modoCorreccion">
              <label class="form-label fw-bold text-primary">Estado</label>
              <select 
                v-model="form.estado" 
                class="form-select" 
                :class="{ 'is-invalid': errores.estado || errores.actualizacion_estado }"
              >
                <option value="borrador">Borrador</option>
                <option value="revision">Revisión</option>
                <option value="publicado">Publicado</option>
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
        <div class="form-group mb-4">
          <label class="form-label fw-bold text-primary">Contenido</label>
          <div ref="editorHolder" id="editorjs" class="editor-container border rounded p-3 bg-white"></div>
        </div>

        <!-- Botones de acción -->
        <div class="form-actions">
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

// Estado del select de categorías
const selectAbierto = ref(false)
const busquedaCategoria = ref('')

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
  fecha_publicacion: ''
})

// Computed
const modoCorreccion = computed(() => props.modoCorreccion)
const estadoParaVista = computed(() => props.modoCorreccion ? 'publicado' : form.estado)

const textoBotonGuardar = computed(() => {
  if (guardando.value) return 'Procesando...'
  return props.modoCorreccion ? 'Enviar corrección' : 'Guardar cambios'
})

// Categorías seleccionadas con detalles
const categoriasSeleccionadas = computed(() => {
  return form.categoria_ids
    .map(id => {
      const categoria = listaCategorias.value.find(c => Number(c.categoria_actualizacion_id) === id)
      return categoria ? {
        id: Number(categoria.categoria_actualizacion_id),
        nombre: categoria.categoria_actualizacion_nombre
      } : null
    })
    .filter(c => c !== null)
})

// Categorías filtradas por búsqueda
const categoriasFiltradas = computed(() => {
  if (!busquedaCategoria.value) return listaCategorias.value
  const busqueda = busquedaCategoria.value.toLowerCase()
  return listaCategorias.value.filter(cat => 
    cat.categoria_actualizacion_nombre.toLowerCase().includes(busqueda)
  )
})

const normalizarCategoriaIds = (valor: any): number[] => {
  const ids = Array.isArray(valor) ? valor : (valor ? [valor] : [])
  return ids
    .map((id) => Number(id))
    .filter((id, index, array) => Number.isFinite(id) && id > 0 && array.indexOf(id) === index)
    .slice(0, 3)
}

const categoriaSeleccionada = (categoriaId: number) => {
  return form.categoria_ids.includes(categoriaId)
}

const toggleCategoria = (categoriaId: number) => {
  const index = form.categoria_ids.indexOf(categoriaId)
  
  if (index > -1) {
    form.categoria_ids.splice(index, 1)
  } else {
    if (form.categoria_ids.length < 3) {
      form.categoria_ids.push(categoriaId)
    }
  }
}

const toggleSelect = () => {
  selectAbierto.value = !selectAbierto.value
  if (!selectAbierto.value) {
    busquedaCategoria.value = ''
  }
}

// Función para asignar colores según la categoría
const getCategoriaColor = (nombre: string) => {
  const colorMap: Record<string, string> = {
    'inicio': '#077E9D',
    'noticias': '#FCBB1C',
    'actualizaciones': '#025B7D',
    'documentos': '#4F46E5',
    'tutoriales': '#10B981',
    'eventos': '#F59E0B',
    'avisos': '#EF4444',
    'novedades': '#8B5CF6'
  }
  
  const lowerNombre = nombre.toLowerCase()
  for (const [key, color] of Object.entries(colorMap)) {
    if (lowerNombre.includes(key)) {
      return color
    }
  }
  return '#077E9D'
}

// Función para asignar iconos según la categoría
const getCategoriaIcon = (nombre: string) => {
  const iconMap: Record<string, string> = {
    'inicio': 'bi bi-house-fill',
    'noticias': 'bi bi-megaphone-fill',
    'actualizaciones': 'bi bi-arrow-repeat',
    'documentos': 'bi bi-file-text-fill',
    'tutoriales': 'bi bi-journal-bookmark-fill',
    'eventos': 'bi bi-calendar-event-fill',
    'avisos': 'bi bi-bell-fill',
    'novedades': 'bi bi-star-fill'
  }
  
  const lowerNombre = nombre.toLowerCase()
  for (const [key, icon] of Object.entries(iconMap)) {
    if (lowerNombre.includes(key)) {
      return icon
    }
  }
  return 'bi bi-tag-fill'
}

// Cerrar select al hacer click fuera
const cerrarSelectAlClickFuera = (event: MouseEvent) => {
  const wrapper = document.querySelector('.categoria-select-wrapper')
  if (wrapper && !wrapper.contains(event.target as Node)) {
    selectAbierto.value = false
    busquedaCategoria.value = ''
  }
}

const formularioValido = computed(() => {
  return form.titulo.trim() !== '' && 
         form.area_servicio_id !== null && 
         form.categoria_ids.length > 0 &&
         form.categoria_ids.length <= 3
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
      header: {
        class: Header as any,
        inlineToolbar: true,
        config: {
          placeholder: 'Escribe un subtítulo',
          levels: [2, 3, 4],
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
  document.addEventListener('click', cerrarSelectAlClickFuera)
})

onBeforeUnmount(() => {
  const modalEl = document.getElementById('modalEditarRegistro')
  modalEl?.removeEventListener('shown.bs.modal', enfocarTitulo)
  document.removeEventListener('click', cerrarSelectAlClickFuera)
  
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

/* Contenedor principal responsive */
.edit-version-container {
  width: 100%;
  max-width: 100%;
  padding: 0;
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
  color: white;
  transform: translateY(-1px);
}

.alert-warning {
  background-color: rgba(252, 187, 28, 0.1);
  border-left: 4px solid var(--warning);
  color: #856404;
}

/* Formulario */
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

/* Select de categorías moderno responsive */
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
  background: white;
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

.dropdown-options::-webkit-scrollbar-thumb:hover {
  background: var(--primary);
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
  border: 1px solid transparent;
}

.label-categoria:hover {
  background: #e2e8f0;
}

.label-remove {
  background: transparent;
  border: none;
  cursor: pointer;
  padding: 0;
  margin-left: 2px;
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
  transition: all 0.2s ease;
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

/* Botones de acción responsive */
.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  margin-top: 2rem;
  padding-top: 1.5rem;
  border-top: 1px solid #e2e8f0;
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

/* Imagen thumbnail */
.img-thumbnail {
  transition: transform 0.2s ease;
  cursor: pointer;
}

.img-thumbnail:hover {
  transform: scale(1.05);
}

/* ============================================
   MEDIA QUERIES RESPONSIVE
   ============================================ */

/* Tablets y móviles grandes (768px - 1024px) */
@media (max-width: 1024px) {
  .form-actions {
    gap: 10px;
  }
  
  .btn {
    padding: 8px 16px;
    font-size: 0.9rem;
  }
}

/* Tablets pequeñas (576px - 768px) */
@media (max-width: 768px) {
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
  
  .form-actions {
    flex-direction: column-reverse;
    gap: 10px;
  }
  
  .form-actions .btn {
    width: 100%;
    justify-content: center;
  }
  
  .editor-container {
    min-height: 280px;
  }
}

/* Móviles (menos de 576px) */
@media (max-width: 576px) {
  .edit-version-container {
    padding: 0;
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
  
  .categorias-seleccionadas {
    flex-direction: column;
    align-items: flex-start;
  }
  
  .seleccionadas-labels {
    width: 100%;
  }
  
  .label-categoria {
    width: calc(100% - 8px);
    justify-content: space-between;
  }
  
  .categoria-counter {
    flex-direction: column;
    align-items: flex-start;
    gap: 6px;
  }
  
  .counter-warning {
    align-self: flex-start;
  }
  
  .dropdown-option {
    padding: 8px 10px;
  }
  
  .option-name {
    font-size: 0.75rem;
  }
  
  .editor-container {
    min-height: 250px;
  }
  
  .alert {
    font-size: 0.85rem;
    padding: 10px 12px;
  }
  
  .alert i {
    font-size: 0.9rem;
  }
}

/* Móviles muy pequeños (menos de 375px) */
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
  
  .label-categoria {
    font-size: 0.65rem;
    padding: 2px 5px 2px 6px;
  }
  
  .counter-text,
  .counter-warning {
    font-size: 0.65rem;
  }
  
  .btn {
    font-size: 0.8rem;
    padding: 6px 12px;
  }
  
  .editor-container {
    min-height: 220px;
  }
}

/* Soporte para pantallas con orientación horizontal en móviles */
@media (max-width: 768px) and (orientation: landscape) {
  .editor-container {
    min-height: 200px;
  }
  
  .dropdown-options {
    max-height: 150px;
  }
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
  :root {
    --primary: #0999be;
    --secondary: #0370a0;
  }
}
</style>