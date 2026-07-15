<template>
  <div class="vista-detalle-container">
    <div v-if="cargando" class="estado-carga">
      <div class="spinner-border text-primary mb-3" role="status">
        <span class="visually-hidden">Cargando...</span>
      </div>
      <p>Cargando actualización...</p>
    </div>

    <DetalleRegistroVisual
      v-else-if="actualizacion"
      :titulo="actualizacion.actualizacion_titulo"
      :version="actualizacion.actualizacion_version"
      :fecha-texto="`Publicado el: ${formatearFecha(actualizacion.actualizacion_fecha_publicacion)}`"
      :imagen-url="obtenerUrlImagen(actualizacion.actualizacion_imagen_destacada)"
      :resumen="actualizacion.actualizacion_resumen"
      :contenido-html="actualizacion.actualizacion_contenido_html"
      :estado="actualizacion.actualizacion_estado || 'Publicado'"
      :estado-clase="mapearClaseEstado(actualizacion.actualizacion_estado)"
      layout="stack"
      mostrar-header-contenido
      contenido-vacio-texto="No hay contenido disponible para esta actualización."
    >
      <template #back-button>
        <button class="btn-volver" type="button" @click="volver">
          <span class="arrow-icon" aria-hidden="true">←</span>
          {{ textoVolver }}
        </button>
      </template>

      <template v-if="esDetalleSupervision" #meta-extra>
        <div class="supervision-meta-container">
          <div class="supervision-meta-card">
            <span class="supervision-meta-label">Empleado autor</span>
            <strong>{{ obtenerNombreAutor(actualizacion) }}</strong>
          </div>

          <div v-if="actualizacion.revision_observacion?.observacion" class="supervision-meta-card revision-motivo-card">
            <span class="supervision-meta-label">Motivo de revisión</span>
            <p>{{ actualizacion.revision_observacion.observacion }}</p>
          </div>
        </div>
      </template>
    </DetalleRegistroVisual>

    <div v-else class="error-container">
      <div class="error-icon">⚠️</div>
      <p>No se pudo cargar la información de esta actualización.</p>
      <button class="btn-retry" @click="volver">Volver al inicio</button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '../../api/api'
import DetalleRegistroVisual from '../shared/DetalleRegistroVisual.vue'

const props = defineProps<{
  id: string | number
}>()

const router = useRouter()
const route = useRoute()

const esDetalleSupervision = computed(() => String(route.name) === 'supervision-show')
const textoVolver = computed(() => esDetalleSupervision.value ? 'Volver a supervisión' : 'Volver a mis registros')

const actualizacion = ref<any>(null)
const cargando = ref(true)

const obtenerDetalle = async () => {
  cargando.value = true

  try {
    const respuesta = await api.get(`/actualizaciones/${props.id}`)

    actualizacion.value = respuesta.data.data

    window.scrollTo({
      top: 0,
      behavior: 'smooth',
    })
  } catch (error) {
    console.error('Error al cargar la actualización:', error)
    actualizacion.value = null
  } finally {
    cargando.value = false
  }
}

const volver = () => {
  router.push({
    name: esDetalleSupervision.value ? 'supervision' : 'mis-registros',
  })
}

const formatearFecha = (fechaString: string) => {
  if (!fechaString) return 'Sin fecha'

  const fecha = new Date(fechaString).toLocaleDateString('es-ES', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  })

  return fecha.charAt(0).toUpperCase() + fecha.slice(1)
}

const obtenerNombrePersona = (persona: any) => {
  if (!persona) return 'Sin información'

  const nombres = [
    persona.usuario_nombre1,
    persona.usuario_nombre2,
    persona.usuario_apellido1,
    persona.usuario_apellido2,
  ]
    .filter(Boolean)
    .join(' ')
    .trim()

  return nombres || persona.usuario_nombre || persona.usuario_usuario || persona.usuario_login || 'Sin información'
}

const obtenerNombreAutor = (item: any) => {
  return obtenerNombrePersona(item?.usuario_autor || item?.autor || item?.usuario)
}

const obtenerUrlImagen = (ruta: string) => {
  if (!ruta) return ''

  if (ruta.startsWith('http')) {
    return ruta
  }

  const backendUrl = import.meta.env.VITE_BACKEND_URL || 'http://localhost:8000'

  if (ruta.startsWith('/storage/')) {
    return `${backendUrl}${ruta}`
  }

  if (ruta.startsWith('storage/')) {
    return `${backendUrl}/${ruta}`
  }

  return `${backendUrl}/storage/${ruta}`
}

const mapearClaseEstado = (estado: string) => {
  const estadoMin = estado?.toLowerCase() || ''

  if (estadoMin === 'publicado') return 'estado-publicado'
  if (estadoMin === 'borrador') return 'estado-borrador'
  if (estadoMin === 'revision') return 'estado-revision'
  if (estadoMin === 'inactivo') return 'estado-inactivo'

  return 'estado-default'
}

onMounted(() => {
  obtenerDetalle()
})

watch(
  () => props.id,
  () => {
    obtenerDetalle()
  }
)
</script>

<style scoped>
.vista-detalle-container {
  width: 100%;
  max-width: 100%;
  margin: 0;
}

.btn-volver {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  background: rgba(255, 255, 255, 0.85);
  border: none;
  color: var(--primary);
  font-weight: 600;
  font-size: 0.95rem;
  cursor: pointer;
  padding: 8px 16px;
  border-radius: 10px;
  transition: var(--transition);
}

.btn-volver:hover {
  background: rgba(255, 255, 255, 1);
  transform: translateX(-4px);
}

.arrow-icon {
  font-size: 1.2rem;
  transition: var(--transition);
}

.supervision-meta-container {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
  gap: 16px;
}

.supervision-meta-card {
  padding: 18px 20px;
  border: 1px solid #e5e7eb;
  border-radius: 16px;
  background: #f8fafc;
}

.supervision-meta-label {
  display: block;
  margin-bottom: 6px;
  color: #64748b;
  font-size: 0.78rem;
  font-weight: 800;
  letter-spacing: 0.04em;
  text-transform: uppercase;
}

.supervision-meta-card strong {
  color: #0f172a;
}

.revision-motivo-card p {
  margin: 0 0 8px;
  color: #334155;
  white-space: pre-line;
}

.revision-motivo-card small {
  color: #64748b;
}

.estado-carga {
  text-align: center;
  padding: 60px 40px;
  background: white;
  border-radius: 20px;
  color: #6b7280;
  max-width: 800px;
  margin: 40px auto;
}

.error-container {
  text-align: center;
  padding: 60px 40px;
  background: white;
  border-radius: 20px;
  box-shadow: var(--shadow-sm);
  max-width: 800px;
  margin: 40px auto;
}

.error-icon {
  font-size: 48px;
  margin-bottom: 16px;
}

.error-container p {
  color: #6b7280;
  margin-bottom: 20px;
}

.btn-retry {
  padding: 10px 24px;
  background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
  color: white;
  border: none;
  border-radius: 12px;
  cursor: pointer;
  font-weight: 500;
  transition: var(--transition);
}

.btn-retry:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow-md);
}
</style>
