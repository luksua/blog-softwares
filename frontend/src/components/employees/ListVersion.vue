<template>
  <div class="vista-detalle-container">
    <div v-if="cargando" class="estado-carga">
      <div class="spinner-border text-primary mb-3" role="status">
        <span class="visually-hidden">Cargando...</span>
      </div>
      <p>Cargando actualización...</p>
    </div>

    <div v-else-if="actualizacion" class="detalle-card">
      <div class="col-lg-9">
        <!-- Botón volver -->
        <div class="detalle-header">
          <button class="btn-volver" @click="volver">
            <span class="arrow-icon">←</span>
            Volver
          </button>
        </div>

        <!-- Badges de información -->
        <div class="info-badges">
          <span class="version-badge">
            v{{ actualizacion.actualizacion_version }}
          </span>
          <span :class="['estado-badge', mapearClaseEstado(actualizacion.actualizacion_estado)]">
            <span class="estado-dot"></span>
            {{ actualizacion.actualizacion_estado || 'Publicado' }}
          </span>
        </div>

        <!-- Título -->
        <h1 class="detalle-titulo">
          {{ actualizacion.actualizacion_titulo }}
        </h1>

        <!-- Fecha -->
        <p class="detalle-fecha">
          Publicado el: {{ formatearFecha(actualizacion.actualizacion_fecha_publicacion) }}
        </p>

        <!-- Imagen destacada -->
        <div v-if="actualizacion.actualizacion_imagen_destacada" class="imagen-destacada-container">
          <img :src="obtenerUrlImagen(actualizacion.actualizacion_imagen_destacada)" alt="Portada"
            class="imagen-destacada" />
        </div>

        <!-- Resumen -->
        <div class="resumen-container">
          <p class="resumen-texto">
            {{ actualizacion.actualizacion_resumen }}
          </p>
        </div>

        <!-- Contenido completo -->
        <div class="contenido-container">
          <div class="contenido-header">
            <!-- <span class="contenido-icon">📄</span> -->
            <h3>Contenido completo</h3>
          </div>
          <div class="editorjs-editor" v-html="actualizacion.actualizacion_contenido_html"></div>
        </div>
      </div>
      <div class="col-lg-3">
        <h1>aaaaaaaaa</h1>
      </div>
    </div>

    <div v-else class="error-container">
      <div class="error-icon">⚠️</div>
      <p>No se pudo cargar la información de esta actualización.</p>
      <button class="btn-retry" @click="volver">Volver al inicio</button>
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
  router.push({ name: 'employee-actualizaciones' })
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

watch(() => props.id, () => {
  obtenerDetalle()
})
</script>

<style scoped>
/* Variables del sistema */
:root {
  --primary: #077E9D;
  --secondary: #025B7D;
  --warning: #FCBB1C;
  --shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.08);
  --shadow-md: 0 4px 16px rgba(0, 0, 0, 0.12);
  --shadow-lg: 0 8px 24px rgba(0, 0, 0, 0.15);
  --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.vista-detalle-container {
  max-width: 1000px;
  margin: 0 auto;
  padding: 24px;
}

/* Estado de carga */
.estado-carga {
  text-align: center;
  padding: 60px 40px;
  background: white;
  border-radius: 20px;
  box-shadow: var(--shadow-sm);
  color: #6b7280;
}

/* Tarjeta principal */
.detalle-card {
  background: white;
  border-radius: 24px;
  box-shadow: var(--shadow-md);
  overflow: hidden;
  transition: var(--transition);
}

.detalle-card:hover {
  box-shadow: var(--shadow-lg);
}

/* Header con botón volver */
.detalle-header {
  padding: 24px 32px 0 32px;
}

.btn-volver {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  background: none;
  border: none;
  color: var(--primary);
  font-weight: 600;
  font-size: 0.95rem;
  cursor: pointer;
  padding: 8px 16px;
  border-radius: 10px;
  transition: var(--transition);
  background: rgba(7, 126, 157, 0.1);
}

.btn-volver:hover {
  background: rgba(7, 126, 157, 0.2);
  transform: translateX(-4px);
}

.arrow-icon {
  font-size: 1.2rem;
  transition: var(--transition);
}

.btn-volver:hover .arrow-icon {
  transform: translateX(-4px);
}

/* Badges de información */
.info-badges {
  display: flex;
  gap: 12px;
  padding: 0 32px;
  margin-top: 16px;
}

.version-badge {
  display: inline-block;
  padding: 6px 14px;
  background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
  color: white;
  border-radius: 30px;
  font-size: 0.8rem;
  font-weight: 600;
  font-family: 'Courier New', monospace;
  box-shadow: var(--shadow-sm);
}

.estado-badge {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 6px 14px;
  border-radius: 30px;
  font-size: 0.8rem;
  font-weight: 600;
  text-transform: capitalize;
}

.estado-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  display: inline-block;
}

.estado-publicado {
  background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%);
  color: #2e7d32;
}

.estado-publicado .estado-dot {
  background: #4caf50;
  box-shadow: 0 0 0 2px rgba(76, 175, 80, 0.2);
}

.estado-borrador {
  background: linear-gradient(135deg, #fff8e1 0%, #ffecb3 100%);
  color: #f57c00;
}

.estado-borrador .estado-dot {
  background: #ff9800;
  box-shadow: 0 0 0 2px rgba(255, 152, 0, 0.2);
}

.estado-revision {
  background: linear-gradient(135deg, #fce4ec 0%, #f8bbd0 100%);
  color: #c62828;
}

.estado-revision .estado-dot {
  background: #f44336;
  box-shadow: 0 0 0 2px rgba(244, 67, 54, 0.2);
}

.estado-inactivo {
  background: linear-gradient(135deg, #e0e0e0 0%, #bdbdbd 100%);
  color: #424242;
}

.estado-inactivo .estado-dot {
  background: #757575;
}

.estado-default {
  background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
  color: #1976d2;
}

.estado-default .estado-dot {
  background: #2196f3;
}

/* Título */
.detalle-titulo {
  font-size: 2rem;
  font-weight: 700;
  color: #1a202c;
  margin: 20px 32px 12px 32px;
  line-height: 1.3;
}

/* Fecha */
.detalle-fecha {
  font-size: 0.9rem;
  color: #6b7280;
  margin: 0 32px 24px 32px;
  padding-bottom: 20px;
  border-bottom: 1px solid #e8ecf0;
}

/* Imagen destacada */
.imagen-destacada-container {
  margin: 0 32px 32px 32px;
  border-radius: 16px;
  overflow: hidden;
  box-shadow: var(--shadow-sm);
  transition: var(--transition);
}

.imagen-destacada-container:hover {
  transform: scale(1.02);
  box-shadow: var(--shadow-md);
}

.imagen-destacada {
  width: 100%;
  max-height: 450px;
  object-fit: cover;
  display: block;
}

/* Resumen */
.resumen-container {
  margin: 0 32px 32px 32px;
  padding: 20px 24px;
  background: linear-gradient(135deg, #f0f7fa 0%, #e8f4f8 100%);
  border-radius: 16px;
  border-left: 4px solid var(--primary);
}

.resumen-texto {
  font-size: 1.05rem;
  line-height: 1.6;
  color: #2c3e50;
  margin: 0;
  font-weight: 500;
}

/* Contenido completo */
.contenido-container {
  margin: 0 32px 32px 32px;
  padding: 24px;
  /* background: #fafbfc;
  border-radius: 16px;
  border: 1px solid #e8ecf0; */
}

.contenido-header {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 20px;
  padding-bottom: 12px;
  border-bottom: 2px solid var(--primary);
}

.contenido-icon {
  font-size: 1.5rem;
}

.contenido-header h3 {
  margin: 0;
  font-size: 1.2rem;
  font-weight: 600;
  color: var(--primary);
}

/* EditorJS contenido */
.editorjs-editor {
  font-family: system-ui, -apple-system, sans-serif;
  font-size: 1rem;
  line-height: 1.7;
  color: #2c3e50;
}

:deep(.editorjs-editor p) {
  margin-bottom: 1rem;
}

:deep(.editorjs-editor h1),
:deep(.editorjs-editor h2),
:deep(.editorjs-editor h3) {
  margin-top: 1.5rem;
  margin-bottom: 0.75rem;
  color: #1a202c;
}

:deep(.editorjs-editor ul),
:deep(.editorjs-editor ol) {
  margin-bottom: 1rem;
  padding-left: 2rem;
}

:deep(.editorjs-editor li) {
  margin-bottom: 0.5rem;
}

:deep(.editorjs-editor img) {
  max-width: 100%;
  height: auto;
  border-radius: 12px;
  box-shadow: var(--shadow-sm);
  margin: 20px auto;
  display: block;
}

:deep(.editorjs-editor blockquote) {
  border-left: 4px solid var(--primary);
  padding-left: 20px;
  margin: 20px 0;
  color: #4a5568;
  font-style: italic;
}

:deep(.editorjs-editor pre) {
  background: #1e293b;
  color: #e2e8f0;
  padding: 16px;
  border-radius: 12px;
  overflow-x: auto;
  margin: 20px 0;
}

:deep(.editorjs-editor code) {
  background: #f1f5f9;
  padding: 2px 6px;
  border-radius: 6px;
  font-size: 0.9em;
  color: #d32f2f;
}

/* Error container */
.error-container {
  text-align: center;
  padding: 60px 40px;
  background: white;
  border-radius: 20px;
  box-shadow: var(--shadow-sm);
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

/* Responsive */
@media (max-width: 768px) {
  .vista-detalle-container {
    padding: 16px;
  }

  .detalle-header {
    padding: 20px 20px 0 20px;
  }

  .info-badges {
    padding: 0 20px;
  }

  .detalle-titulo {
    font-size: 1.5rem;
    margin: 16px 20px 8px 20px;
  }

  .detalle-fecha {
    margin: 0 20px 16px 20px;
  }

  .imagen-destacada-container {
    margin: 0 20px 24px 20px;
  }

  .resumen-container {
    margin: 0 20px 24px 20px;
    padding: 16px;
  }

  .contenido-container {
    margin: 0 20px 20px 20px;
    padding: 16px;
  }

  .resumen-texto {
    font-size: 0.95rem;
  }
}

@media (max-width: 480px) {
  .detalle-titulo {
    font-size: 1.3rem;
  }

  .info-badges {
    flex-wrap: wrap;
  }

  .contenido-header h3 {
    font-size: 1rem;
  }
}
</style>