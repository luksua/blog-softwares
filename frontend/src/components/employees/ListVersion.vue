<template>
  <div class="vista-detalle-container">
    <div v-if="cargando" class="estado-carga">
      <div class="spinner-border text-primary mb-3" role="status">
        <span class="visually-hidden">Cargando...</span>
      </div>
      <p>Cargando actualización...</p>
    </div>

    <div v-else-if="actualizacion" class="detalle-card">
      <div class="hero-banner" :class="{ 'sin-imagen': !actualizacion.actualizacion_imagen_destacada }">

        <button class="btn-volver hero-btn-pos" @click="volver">
          <span class="arrow-icon">←</span>
          Volver
        </button>

        <img v-if="actualizacion.actualizacion_imagen_destacada"
          :src="obtenerUrlImagen(actualizacion.actualizacion_imagen_destacada)" alt="Portada" class="hero-image" />

        <div class="hero-overlay"></div>

        <div class="hero-content">
          <div class="hero-top-info">
            <h1 class="hero-titulo">
              {{ actualizacion.actualizacion_titulo }}
            </h1>
          </div>

          <div class="hero-bottom-info">
            <div class="hero-meta-left">
              <span class="version-badge">
                v{{ actualizacion.actualizacion_version }}
              </span>
              <span class="fecha-texto">
                Publicado el: {{ formatearFecha(actualizacion.actualizacion_fecha_publicacion) }}
              </span>
            </div>

            <div class="hero-meta-right">
              <span :class="['estado-badge', mapearClaseEstado(actualizacion.actualizacion_estado)]">
                <span class="estado-dot"></span>
                {{ actualizacion.actualizacion_estado || 'Publicado' }}
              </span>
            </div>
          </div>
        </div>
      </div>
      <div class="row">
        <div class="col-lg-8">
          <div class="resumen-container">
            <p class="resumen-texto">
              {{ actualizacion.actualizacion_resumen }}
            </p>
          </div>

          <div class="contenido-container">
            <div class="contenido-header">
              <h3>Contenido completo</h3>
            </div>
            <div class="editorjs-editor" v-html="actualizacion.actualizacion_contenido_html"></div>
          </div>
        </div>
        <div class="col-lg-4">
          <h2>También te puede interesar</h2>
          <h2>También te puede interesar</h2>
        </div>
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
  /* CAMBIO CLAVE: Todo el contenedor padre ahora ocupa el 100% del espacio que le den */
  width: 100%;
  max-width: 100%;
  margin: 0;
}

/* Estado de carga */
.estado-carga {
  text-align: center;
  padding: 60px 40px;
  background: white;
  border-radius: 20px;
  box-shadow: var(--shadow-sm);
  color: #6b7280;
  max-width: 800px;
  margin: 40px auto;
}

/* Tarjeta principal */
.detalle-card {
  background: white;
  border-radius: 0 0 24px 24px;
  /* Quitamos bordes redondeados arriba porque toca los bordes de la pantalla/sidebar */
  box-shadow: var(--shadow-md);
  overflow: hidden;
  transition: var(--transition);
}

/* --- HERO BANNER (OCUPA EL 100%) --- */
.hero-banner {
  position: relative;
  width: 100%;
  /* La imagen ahora abarca todo lo ancho */
  min-height: 300px;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  border-radius: 0;
  /* Sin bordes para que parezca un banner real de lado a lado */
  overflow: hidden;
  background-color: var(--secondary);
}

.hero-banner.sin-imagen {
  min-height: 200px;
  background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
}

.hero-image {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
  z-index: 1;
}

.hero-overlay {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: linear-gradient(to top, rgba(15, 23, 42, 0.9) 0%, rgba(15, 23, 42, 0.4) 35%, rgba(15, 23, 42, 0.0) 100%);
  z-index: 2;
}

.hero-content {
  position: relative;
  z-index: 3;
  padding: 30px 25% 24px 25%;
  height: 100%;
  display: flex;
  flex-direction: column;
  justify-content: flex-end;
  flex-grow: 1;
}

/* --- ESTILOS ORIGINALES RESTAURADOS --- */

/* Botón Volver Original */
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
  background: rgba(255, 255, 255, 0.85);
}

.btn-volver:hover {
  background: rgba(255, 255, 255, 1);
  transform: translateX(-4px);
}

.arrow-icon {
  font-size: 1.2rem;
  transition: var(--transition);
}

.hero-btn-pos {
  position: absolute;
  top: 20px;
  left: 25%;
  z-index: 4;
}

/* Badge Versión Original */
.version-badge {
  display: inline-block;
  padding: 6px 14px;
  background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
  color: white;
  border-radius: 30px;
  font-size: 0.85rem;
  font-weight: 600;
  font-family: 'Courier New', monospace;
  box-shadow: var(--shadow-sm);
}

/* Badges de Estado Originales */
.estado-badge {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 8px 16px;
  border-radius: 30px;
  font-size: 0.85rem;
  font-weight: 700;
  text-transform: capitalize;
  box-shadow: var(--shadow-sm);
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

/* Textos sobre el Banner */
.hero-titulo {
  color: white;
  font-size: 2.2rem;
  font-weight: 700;
  margin: 0 0 16px 0;
  line-height: 1.2;
  text-shadow: 0 2px 8px rgba(0, 0, 0, 0.4);
}

.fecha-texto {
  color: rgba(255, 255, 255, 0.9);
  font-size: 0.95rem;
  font-weight: 500;
  text-shadow: 0 1px 4px rgba(0, 0, 0, 0.5);
}

.hero-bottom-info {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 16px;
}

.hero-meta-left {
  display: flex;
  align-items: center;
  gap: 16px;
  flex-wrap: wrap;
}

.hero-meta-right {
  display: flex;
  align-items: center;
}

/* --- RESUMEN Y CONTENIDO (AHORA SON MÁS ESTRECHOS Y CENTRADOS) --- */
.resumen-container {
  max-width: 800px;
  /* Ancho máximo para no cansar la vista */
  margin: 40px auto 32px auto;
  /* Margin "auto" a los lados lo centra perfectamente */
  padding: 24px;
  background: #f8fafc;
  border-radius: 12px;
  border-left: 4px solid var(--primary);
}

.resumen-texto {
  font-size: 1.1rem;
  line-height: 1.6;
  color: #334155;
  margin: 0;
}

.contenido-container {
  max-width: 800px;
  /* Ancho máximo para el contenido completo */
  margin: 0 auto 60px auto;
  /* Centrado igual que el resumen */
  padding: 0 20px;
  /* Un poco de padding lateral por si las pantallas se hacen pequeñas */
}

.contenido-header {
  margin-bottom: 24px;
  padding-bottom: 12px;
  border-bottom: 2px solid #e2e8f0;
}

.contenido-header h3 {
  margin: 0;
  font-size: 1.25rem;
  font-weight: 600;
  color: #0f172a;
}

/* EditorJS contenido */
.editorjs-editor {
  font-family: system-ui, -apple-system, sans-serif;
  font-size: 1.05rem;
  line-height: 1.8;
  color: #334155;
}

:deep(.editorjs-editor p) {
  margin-bottom: 1.2rem;
}

:deep(.editorjs-editor h1),
:deep(.editorjs-editor h2),
:deep(.editorjs-editor h3) {
  margin-top: 2rem;
  margin-bottom: 1rem;
  color: #0f172a;
}

:deep(.editorjs-editor ul),
:deep(.editorjs-editor ol) {
  margin-bottom: 1.2rem;
  padding-left: 2rem;
}

:deep(.editorjs-editor img) {
  max-width: 100%;
  height: auto;
  border-radius: 12px;
  box-shadow: var(--shadow-sm);
  margin: 24px auto;
  display: block;
}

/* Error container */
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

/* Responsive */
@media (max-width: 768px) {
  .hero-banner {
    min-height: 250px;
  }

  .hero-content {
    padding: 60px 20px 20px 20px;
  }

  .hero-titulo {
    font-size: 1.8rem;
  }

  .resumen-container {
    margin: 24px 20px;
    padding: 16px;
  }

  .contenido-container {
    margin: 0 20px 32px 20px;
  }

  .hero-btn-pos {
    left: 20px;
    top: 16px;
  }
}

@media (max-width: 480px) {
  .hero-titulo {
    font-size: 1.5rem;
  }

  .hero-bottom-info {
    flex-direction: column;
    align-items: flex-start;
    gap: 12px;
  }
}
</style>