<template>
  <div class="vista-detalle-container">
    <!-- Estado de carga -->
    <div v-if="cargando" class="estado-carga">
      <div class="spinner-border text-primary mb-3" role="status">
        <span class="visually-hidden">Cargando...</span>
      </div>
      <p>Cargando actualización...</p>
    </div>

    <!-- Contenido principal -->
    <article v-else-if="actualizacion" class="detalle-card">
      <!-- Hero Banner -->
      <header 
        class="hero-banner" 
        :class="{ 'sin-imagen': !actualizacion.actualizacion_imagen_destacada }"
      >
        <button class="btn-volver hero-btn-pos" @click="volver" aria-label="Volver atrás">
          <span class="arrow-icon" aria-hidden="true">←</span>
          Volver
        </button>

        <img 
          v-if="actualizacion.actualizacion_imagen_destacada"
          :src="obtenerUrlImagen(actualizacion.actualizacion_imagen_destacada)" 
          :alt="actualizacion.actualizacion_titulo" 
          class="hero-image" 
        />

        <div class="hero-overlay" aria-hidden="true"></div>

        <div class="hero-content">
          <div class="hero-top-info">
            <h1 class="hero-titulo">
              {{ actualizacion.actualizacion_titulo }}
            </h1>
          </div>

          <div class="hero-bottom-info">
            <div class="hero-meta-left">
              <span class="version-badge" aria-label="Versión">
                v{{ actualizacion.actualizacion_version }}
              </span>
              <time class="fecha-texto" :datetime="actualizacion.actualizacion_fecha_publicacion">
                Publicado el: {{ formatearFecha(actualizacion.actualizacion_fecha_publicacion) }}
              </time>
            </div>

            <div class="hero-meta-right">
              <span 
                :class="['estado-badge', mapearClaseEstado(actualizacion.actualizacion_estado)]"
                :aria-label="`Estado: ${actualizacion.actualizacion_estado || 'Publicado'}`"
              >
                <span class="estado-dot" aria-hidden="true"></span>
                {{ actualizacion.actualizacion_estado || 'Publicado' }}
              </span>
            </div>
          </div>
        </div>
      </header>

      <!-- Contenido del artículo -->
      <div class="contenido-principal">
        <div class="contenido-wrapper">
          <!-- Columna principal del artículo -->
          <div class="contenido-columna">
            <!-- Resumen -->
            <section class="resumen-container" aria-label="Resumen del artículo">
              <p class="resumen-texto">
                {{ actualizacion.actualizacion_resumen }}
              </p>
            </section>

            <!-- Contenido completo -->
            <section class="contenido-container" aria-label="Contenido completo">
              <div class="contenido-header">
                <h2>Contenido completo</h2>
              </div>
              <div class="editorjs-editor" v-html="actualizacion.actualizacion_contenido_html"></div>
            </section>
          </div>

          <!-- Sidebar -->
          <aside class="sidebar-relacionados" aria-label="Artículos relacionados">
            <div class="sidebar-header">
              <span class="sidebar-icon" aria-hidden="true">✦</span>
              <h2 class="sidebar-titulo">También te puede interesar</h2>
            </div>

            <!-- Skeleton loader -->
            <div v-if="cargandoRelacionados" class="relacionados-skeleton" aria-label="Cargando artículos relacionados">
              <div v-for="n in 3" :key="n" class="skeleton-card" aria-hidden="true">
                <div class="skeleton-img"></div>
                <div class="skeleton-body">
                  <div class="skeleton-line largo"></div>
                  <div class="skeleton-line corto"></div>
                  <div class="skeleton-line medio"></div>
                </div>
              </div>
            </div>

            <!-- Sin resultados -->
            <div v-else-if="relacionados.length === 0" class="relacionados-vacio">
              <p>No hay otras publicaciones disponibles.</p>
            </div>

            <!-- Lista de relacionados -->
            <div v-else class="relacionados-lista">
              <article
                v-for="item in relacionados"
                :key="item.id"
                class="relacionado-card"
                @click="irA(item.id)"
                role="button"
                tabindex="0"
                @keydown.enter="irA(item.id)"
                @keydown.space.prevent="irA(item.id)"

              >
                <div class="relacionado-img-wrap">
                  <img
                    v-if="item.actualizacion_imagen_destacada"
                    :src="obtenerUrlImagen(item.actualizacion_imagen_destacada)"
                    :alt="item.actualizacion_titulo"
                    class="relacionado-img"
                    loading="lazy"
                  />
                  <div v-else class="relacionado-sin-img" aria-hidden="true">
                    <span>🖼️</span>
                  </div>
                </div>

                <div class="relacionado-body">
                  <span class="relacionado-area">
                    {{ item.area_servicio?.area_servicio_nombre || 'General' }}
                  </span>
                  <h3 class="relacionado-titulo">{{ item.actualizacion_titulo }}</h3>
                  <time class="relacionado-fecha" :datetime="item.actualizacion_fecha_publicacion">
                    {{ formatearFecha(item.actualizacion_fecha_publicacion) }}
                  </time>
                </div>
              </article>
            </div>
          </aside>
        </div>
      </div>
    </article>

    <!-- Estado de error -->
    <div v-else class="error-container">
      <div class="error-icon" aria-hidden="true">⚠️</div>
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
const relacionados = ref<any[]>([])
const cargandoRelacionados = ref(false)

const obtenerRelacionados = async () => {
  if (!actualizacion.value) return
  cargandoRelacionados.value = true

  try {
    const areaId = actualizacion.value.actualizacion_area_servicio_id
    const respuesta = await api.get('/employee/actualizaciones', {
      params: {
        area_servicio_id: areaId,
        orden: 'recientes',
        per_page: 4
      }
    })

    relacionados.value = (respuesta.data.data as any[])
      .filter(item => item.id !== Number(props.id))
      .slice(0, 3)
  } catch (err) {
    console.error('Error al cargar relacionados:', err)
    relacionados.value = []
  } finally {
    cargandoRelacionados.value = false
  }
}

const irA = (id: number) => {
  router.push({ name: 'employee-actualizaciones-show', params: { id } })
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

const obtenerDetalle = async () => {
  cargando.value = true

  try {
    const respuesta = await api.get(`/employee/actualizaciones/${props.id}`)
    actualizacion.value = respuesta.data.data
    window.scrollTo({ top: 0, behavior: 'smooth' })
    await obtenerRelacionados()
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
/* Variables del sistema - NO MODIFICADO */
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
  width: 100%;
  max-width: 100%;
  margin: 0;
}

/* Estado de carga - SIN CAMBIOS */
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

/* Tarjeta principal - SIN CAMBIOS */
.detalle-card {
  background: white;
  border-radius: 0 0 24px 24px;
  box-shadow: var(--shadow-md);
  overflow: hidden;
  transition: var(--transition);
}

/* Hero Banner - SIN CAMBIOS */
.hero-banner {
  position: relative;
  width: 100%;
  min-height: 300px;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  border-radius: 0;
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

/* Botón Volver - SIN CAMBIOS */
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

/* Badge Versión - SIN CAMBIOS */
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

/* Badges de Estado - SIN CAMBIOS */
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

/* Textos sobre el Banner - SIN CAMBIOS */
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

/* NUEVA ESTRUCTURA PARA EL CONTENIDO PRINCIPAL */
.contenido-principal {
  padding: 0 20px;
}

.contenido-wrapper {
  max-width: 1400px;
  margin: 0 auto;
  display: grid;
  grid-template-columns: 1fr 320px;
  gap: 40px;
  padding: 40px 0;
}

.contenido-columna {
  min-width: 0; /* Previene overflow */
}

/* Responsive para el grid */
@media (max-width: 991px) {
  .contenido-wrapper {
    grid-template-columns: 1fr;
    gap: 32px;
  }
  
  .sidebar-relacionados {
    grid-row: 2;
  }
}

@media (max-width: 768px) {
  .contenido-principal {
    padding: 0 16px;
  }
  
  .contenido-wrapper {
    padding: 24px 0;
  }
}

/* Resumen y Contenido - MODIFICADO para adaptarse a la nueva estructura */
.resumen-container {
  max-width: 100%; /* Cambiado de 800px a 100% para que ocupe el ancho de la columna */
  margin: 0 0 32px 0;
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
  max-width: 100%; /* Cambiado de 800px a 100% */
  margin: 0;
  padding: 0;
}

.contenido-header {
  margin-bottom: 24px;
  padding-bottom: 12px;
  border-bottom: 2px solid #e2e8f0;
}

.contenido-header h2 {
  margin: 0;
  font-size: 1.5rem;
  font-weight: 600;
  color: #0f172a;
}

/* EditorJS contenido - SIN CAMBIOS */
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

/* Error container - SIN CAMBIOS */
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

/* Sidebar - MODIFICADO ligeramente para adaptarse */
.sidebar-relacionados {
  position: sticky;
  top: 24px;
  align-self: start;
}

.sidebar-header {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 20px;
  padding-bottom: 14px;
  border-bottom: 2px solid #e2e8f0;
}

.sidebar-icon {
  color: var(--primary);
  font-size: 1rem;
  flex-shrink: 0;
}

.sidebar-titulo {
  font-size: 1rem;
  font-weight: 700;
  color: #0f172a;
  margin: 0;
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

.relacionados-lista {
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.relacionado-card {
  display: flex;
  gap: 12px;
  align-items: flex-start;
  padding: 10px;
  border-radius: 12px;
  border: 1px solid #e1e7f0;
  background: white;
  cursor: pointer;
  transition: var(--transition);
}

.relacionado-card:hover {
  border-color: var(--primary);
  box-shadow: var(--shadow-sm);
  transform: translateX(4px);
}

.relacionado-img-wrap {
  width: 80px;
  min-width: 80px;
  height: 60px;
  border-radius: 8px;
  overflow: hidden;
  background: #f1f5f9;
}

.relacionado-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
  transition: var(--transition);
}

.relacionado-card:hover .relacionado-img {
  transform: scale(1.05);
}

.relacionado-sin-img {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.4rem;
  color: #cbd5e1;
}

.relacionado-body {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 4px;
  min-width: 0;
}

.relacionado-area {
  font-size: 0.7rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: var(--primary);
}

.relacionado-titulo {
  font-size: 0.88rem;
  font-weight: 600;
  color: #1a202c;
  margin: 0;
  line-height: 1.35;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  text-overflow: ellipsis;
  transition: color 0.2s;
}

.relacionado-card:hover .relacionado-titulo {
  color: var(--primary);
}

.relacionado-fecha {
  font-size: 0.75rem;
  color: #94a3b8;
}

/* Skeleton loader - SIN CAMBIOS */
.relacionados-skeleton {
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.skeleton-card {
  display: flex;
  gap: 12px;
  padding: 10px;
  border-radius: 12px;
  border: 1px solid #e1e7f0;
}

.skeleton-img {
  width: 80px;
  min-width: 80px;
  height: 60px;
  border-radius: 8px;
  background: linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 50%, #f1f5f9 75%);
  background-size: 200% 100%;
  animation: shimmer 1.4s infinite;
}

.skeleton-body {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 8px;
  justify-content: center;
}

.skeleton-line {
  height: 10px;
  border-radius: 6px;
  background: linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 50%, #f1f5f9 75%);
  background-size: 200% 100%;
  animation: shimmer 1.4s infinite;
}

.skeleton-line.largo  { width: 90%; }
.skeleton-line.medio  { width: 70%; }
.skeleton-line.corto  { width: 45%; }

@keyframes shimmer {
  0%   { background-position: 200% 0; }
  100% { background-position: -200% 0; }
}

.relacionados-vacio {
  padding: 20px;
  text-align: center;
  color: #94a3b8;
  font-size: 0.88rem;
  border: 1px dashed #e1e7f0;
  border-radius: 12px;
}

/* Responsive para móvil - MEJORADO */
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

  .hero-btn-pos {
    left: 20px;
    top: 16px;
  }
  
  .resumen-container {
    margin: 0 0 24px 0;
    padding: 16px;
  }
  
  .contenido-header h2 {
    font-size: 1.25rem;
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