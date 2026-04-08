<template>
  <div class="vista-detalle-container">
    <div v-if="cargando" class="text-center py-5">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Cargando...</span>
      </div>
      <p class="mt-2">Cargando actualización...</p>
    </div>

    <div v-else-if="actualizacion" class="card shadow-sm">
      <div class="card-header bg-white border-bottom-0 pt-4 pb-0 px-4">
        <div class="pb-5 d-flex justify-content-start">
          <button class="btn btn-secondary" @click="volver">
            Volver
          </button>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-2">
          <span class="badge bg-primary">
            Versión {{ actualizacion.actualizacion_version }}
          </span>
        </div>

        <h1 class="card-title h2 fw-bold mb-1">
          {{ actualizacion.actualizacion_titulo }}
        </h1>

        <p class="text-muted small mb-0">
          Publicado el: {{ formatearFecha(actualizacion.actualizacion_fecha_publicacion) }}
        </p>
      </div>

      <div class="card-body px-4 py-3">
        <div v-if="actualizacion.actualizacion_imagen_destacada" class="mb-4 text-center">
          <img
            :src="obtenerUrlImagen(actualizacion.actualizacion_imagen_destacada)"
            alt="Portada"
            class="img-fluid rounded max-altura-portada"
          />
        </div>

        <div class="alert alert-custom mb-4">
          {{ actualizacion.actualizacion_resumen }}
        </div>

        <div class="editorjs-container border-0">
          <div
            class="editorjs-editor px-0"
            v-html="actualizacion.actualizacion_contenido_html"
          ></div>
        </div>
      </div>
    </div>

    <div v-else class="alert alert-danger">
      No se pudo cargar la información de esta actualización.
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import api from '../../api/api'

const props = defineProps<{
  id: string | number
}>()

const router = useRouter()

const actualizacion = ref<any>(null)
const cargando = ref(true)

const obtenerDetalle = async () => {
  cargando.value = true

  try {
    const respuesta = await api.get(`/admin/actualizaciones/${props.id}`)
    actualizacion.value = respuesta.data.data
    window.scrollTo({ top: 0, behavior: 'smooth' })
  } catch (error) {
    console.error('Error al cargar la actualización:', error)
    actualizacion.value = null
  } finally {
    cargando.value = false
  }
}

const volver = () => {
  router.push({ name: 'inicioAdmin' })
}

const formatearFecha = (fechaString: string) => {
  if (!fechaString) return 'Sin fecha'

  return new Date(fechaString).toLocaleDateString('es-ES', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

const obtenerUrlImagen = (ruta: string) => {
  if (!ruta) return ''
  if (ruta.startsWith('http')) return ruta

  const baseUrl = import.meta.env.VITE_API_URL || 'http://localhost:8000'
  return `${baseUrl}/storage/${ruta}`
}

onMounted(() => {
  obtenerDetalle()
})

watch(() => props.id, () => {
  obtenerDetalle()
})
</script>

<style scoped>
.vista-detalle-container {
  max-width: 900px;
  margin: 0 auto;
}

.max-altura-portada {
  max-height: 350px;
  object-fit: cover;
  width: 100%;
}

.alert-custom {
  background-color: #dbdbdb;
}

:deep(.editorjs-editor) {
  font-family: system-ui, -apple-system, sans-serif;
  font-size: 1.1rem;
  line-height: 1.6;
  color: #333;
}

:deep(.editorjs-editor p) {
  margin-bottom: 1rem;
}

:deep(.editorjs-editor ul),
:deep(.editorjs-editor ol) {
  margin-bottom: 1rem;
  padding-left: 2rem;
}

:deep(.editorjs-editor img) {
  max-width: 100%;
  height: auto;
  border-radius: 8px;
  box-shadow: 0 4px 6px rgba(0,0,0,0.1);
  margin: 20px auto;
  display: block;
}
</style>