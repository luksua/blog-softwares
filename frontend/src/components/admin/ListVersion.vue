<template>
  <div class="vista-detalle-container">
    <div v-if="cargando" class="estado-carga">
      <div class="spinner-border text-primary mb-3" role="status">
        <span class="visually-hidden">Cargando...</span>
      </div>
      <p>Cargando actualización...</p>
    </div>

    <div v-else-if="actualizacion" class="detalle-card">
      
      <div class="hero-banner" :class="{'sin-imagen': !actualizacion.actualizacion_imagen_destacada}">
        
        <button class="btn-volver hero-btn" @click="volver">
          <span class="arrow-icon">←</span>
          Volver
        </button>

        <img v-if="actualizacion.actualizacion_imagen_destacada" 
             :src="obtenerUrlImagen(actualizacion.actualizacion_imagen_destacada)" 
             alt="Portada"
             class="hero-image" />

        <div class="hero-overlay"></div>

        <div class="hero-content">
          <div class="hero-top-info">
            <h1 class="hero-titulo">
              {{ actualizacion.actualizacion_titulo }}
            </h1>
          </div>

          <div class="hero-bottom-info">
            <div class="hero-meta-left">
              <span class="glass-badge">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path><line x1="7" y1="7" x2="7.01" y2="7"></line></svg>
                v{{ actualizacion.actualizacion_version }}
              </span>
              <span class="glass-badge">
                Actualizado el {{ formatearFecha(actualizacion.actualizacion_fecha_publicacion) }}
              </span>
            </div>

            <div class="hero-meta-right">
              <span class="estado-label">ESTADO</span>
              <div class="author-badge">
                <span :class="['estado-dot-small', mapearClaseEstado(actualizacion.actualizacion_estado)]"></span>
                <span class="estado-texto">{{ actualizacion.actualizacion_estado || 'Publicado' }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
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
/* Mantén tus variables de :root y estilos generales aquí arriba */
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
  width: 60%;
  max-width: 1400px;
  margin: 0 auto;
}

.estado-carga {
  text-align: center;
  padding: 60px 40px;
  background: white;
  border-radius: 20px;
  box-shadow: var(--shadow-sm);
  color: #6b7280;
}

.detalle-card {
  background: white;
  border-radius: 24px;
  box-shadow: var(--shadow-md);
  overflow: hidden;
  transition: var(--transition);
}

/* --- NUEVOS ESTILOS DEL HERO BANNER --- */
.hero-banner {
  position: relative;
  width: 100%;
  min-height: 400px;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  border-radius: 24px 24px 0 0;
  overflow: hidden;
  background-color: var(--secondary); /* Fallback por si la imagen tarda en cargar */
}

.hero-banner.sin-imagen {
  min-height: 250px;
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
  /* Gradiente suave: oscuro solo abajo para leer el texto, casi transparente arriba */
  background: linear-gradient(to top, rgba(15, 23, 42, 0.85) 0%, rgba(15, 23, 42, 0.3) 40%, rgba(15, 23, 42, 0.05) 100%);
  z-index: 2;
}

.hero-content {
  position: relative;
  z-index: 3;
  padding: 80px 40px 32px 40px;
  height: 100%;
  display: flex;
  flex-direction: column;
  justify-content: flex-end;
  flex-grow: 1;
}

.hero-btn {
  position: absolute;
  top: 24px;
  left: 32px;
  z-index: 4;
  background: rgba(255, 255, 255, 0.15);
  backdrop-filter: blur(8px);
  -webkit-backdrop-filter: blur(8px);
  color: white;
  border: 1px solid rgba(255, 255, 255, 0.2);
  padding: 8px 16px;
  border-radius: 8px;
  font-weight: 600;
  font-size: 0.9rem;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
  transition: var(--transition);
}

.hero-btn:hover {
  background: rgba(255, 255, 255, 0.25);
  transform: translateX(-4px);
}

.hero-btn .arrow-icon {
  font-size: 1.1rem;
}

.hero-titulo {
  color: white;
  font-size: 2.5rem;
  font-weight: 700;
  margin: 0 0 24px 0;
  line-height: 1.2;
  text-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
}

.hero-bottom-info {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  flex-wrap: wrap;
  gap: 20px;
}

.hero-meta-left {
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
}

/* Estilo Glassmorphism para los badges (como en la imagen de referencia) */
.glass-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 14px;
  background: rgba(255, 255, 255, 0.15);
  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, 0.1);
  color: white;
  border-radius: 6px;
  font-size: 0.85rem;
  font-weight: 500;
}

/* Estilo del "Autor/Estado" a la derecha */
.hero-meta-right {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 4px;
}

.estado-label {
  color: rgba(255, 255, 255, 0.7);
  font-size: 0.7rem;
  font-weight: 700;
  letter-spacing: 1px;
}

.author-badge {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 8px 16px;
  background: rgba(15, 23, 42, 0.6); /* Fondo oscuro semitransparente */
  backdrop-filter: blur(10px);
  border-radius: 8px;
  border: 1px solid rgba(255, 255, 255, 0.05);
}

.estado-texto {
  color: white;
  font-weight: 600;
  font-size: 0.9rem;
  text-transform: capitalize;
}

.estado-dot-small {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  display: inline-block;
}

/* Colores de los estados */
.estado-publicado { background: #4caf50; box-shadow: 0 0 8px rgba(76, 175, 80, 0.5); }
.estado-borrador { background: #ff9800; box-shadow: 0 0 8px rgba(255, 152, 0, 0.5); }
.estado-revision { background: #f44336; box-shadow: 0 0 8px rgba(244, 67, 54, 0.5); }
.estado-inactivo { background: #9e9e9e; }
.estado-default { background: #2196f3; box-shadow: 0 0 8px rgba(33, 150, 243, 0.5); }

/* --- RESTO DEL CONTENIDO --- */
.resumen-container {
  margin: 32px 40px;
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
  margin: 0 40px 40px 40px;
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

:deep(.editorjs-editor p) { margin-bottom: 1.2rem; }
:deep(.editorjs-editor h1), :deep(.editorjs-editor h2), :deep(.editorjs-editor h3) { margin-top: 2rem; margin-bottom: 1rem; color: #0f172a; }
:deep(.editorjs-editor ul), :deep(.editorjs-editor ol) { margin-bottom: 1.2rem; padding-left: 2rem; }
:deep(.editorjs-editor img) { max-width: 100%; height: auto; border-radius: 12px; box-shadow: var(--shadow-sm); margin: 24px auto; display: block; }

/* Responsive */
@media (max-width: 768px) {
  .vista-detalle-container { width: 95%; padding: 16px 0; }
  .hero-banner { min-height: 350px; }
  .hero-content { padding: 80px 20px 24px 20px; }
  .hero-titulo { font-size: 1.8rem; }
  .resumen-container { margin: 24px 20px; padding: 16px; }
  .contenido-container { margin: 0 20px 32px 20px; }
  .hero-btn { left: 20px; top: 16px; }
}

@media (max-width: 480px) {
  .hero-titulo { font-size: 1.5rem; }
  .hero-bottom-info { flex-direction: column; align-items: flex-start; gap: 16px; }
}
</style>