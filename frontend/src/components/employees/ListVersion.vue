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
      <header class="hero-banner" :class="{'sin-imagen': !actualizacion.actualizacion_imagen_destacada}">
        <button class="btn-volver hero-btn-pos" @click="volver" aria-label="Volver atrás">
          <span class="arrow-icon" aria-hidden="true">←</span>
          Volver
        </button>

        <img v-if="actualizacion.actualizacion_imagen_destacada"
          :src="obtenerUrlImagen(actualizacion.actualizacion_imagen_destacada)"
          :alt="actualizacion.actualizacion_titulo"
          class="hero-image" />

        <div class="hero-overlay" aria-hidden="true"></div>

        <div class="hero-content">
          <div class="hero-top-info">
            <h1 class="hero-titulo">{{ actualizacion.actualizacion_titulo }}</h1>
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
              <span :class="['estado-badge', mapearClaseEstado(actualizacion.actualizacion_estado)]"
                :aria-label="`Estado: ${actualizacion.actualizacion_estado || 'Publicado'}`">
                <span class="estado-dot" aria-hidden="true"></span>
                {{ actualizacion.actualizacion_estado || 'Publicado' }}
              </span>
            </div>
          </div>
        </div>
      </header>

      <!-- Contenido del artículo con índice lateral -->
      <div class="contenido-principal">
        <div class="contenido-wrapper">

          <!-- Índice lateral (Sidebar) -->
          <aside class="indice-sidebar" aria-label="Índice del documento">
            <div class="indice-header">
              <span class="indice-icon" aria-hidden="true">☰</span>
              <h2 class="indice-titulo">Índice</h2>
            </div>
            <nav class="indice-nav">
              <ul class="indice-lista">
                <li v-if="actualizacion.actualizacion_resumen" class="indice-item">
                  <a href="#resumen" @click.prevent="scrollToElement('resumen')"
                    class="indice-link indice-nivel-fijo"
                    :class="{ activo: headingActivo === 'resumen' }">
                    <span class="indice-bullet">•</span>
                    Resumen
                  </a>
                </li>

                <li v-for="heading in headings" :key="heading.id" class="indice-item">
                  <a :href="`#${heading.id}`" @click.prevent="irAHeading(heading.id)"
                    class="indice-link"
                    :class="[`indice-nivel-${heading.level}`, { activo: headingActivo === heading.id }]">
                    <span class="indice-bullet">•</span>
                    <span v-html="heading.text"></span>
                  </a>
                </li>

                <li v-if="headings.length === 0" class="indice-vacio">
                  <span>Sin secciones</span>
                </li>
              </ul>
            </nav>
          </aside>

          <!-- Columna principal del artículo -->
          <div class="contenido-columna">
            <section id="resumen" class="resumen-container" aria-label="Resumen del artículo">
              <p class="resumen-texto">{{ actualizacion.actualizacion_resumen }}</p>
            </section>

            <section class="contenido-container" aria-label="Contenido completo">
              <div ref="contenidoRef" class="editorjs-editor" v-html="actualizacion.actualizacion_contenido_html"></div>
            </section>
          </div>
        </div>

        <!-- Sección "También te puede interesar" -->
        <div class="relacionados-footer">
          <div class="relacionados-footer-wrapper">
            <div class="relacionados-footer-header">
              <span class="relacionados-footer-icon" aria-hidden="true">✦</span>
              <h2 class="relacionados-footer-titulo">También te puede interesar</h2>
            </div>

            <!-- Skeleton loader -->
            <div v-if="cargandoRelacionados" class="relacionados-footer-grid">
              <div v-for="n in 3" :key="n" class="skeleton-card-footer" aria-hidden="true">
                <div class="skeleton-img-footer"></div>
                <div class="skeleton-body-footer">
                  <div class="skeleton-line-footer largo"></div>
                  <div class="skeleton-line-footer corto"></div>
                  <div class="skeleton-line-footer medio"></div>
                </div>
              </div>
            </div>

            <!-- Sin resultados -->
            <div v-else-if="relacionados.length === 0" class="relacionados-footer-vacio">
              <p>No hay otras publicaciones disponibles.</p>
            </div>

            <!-- Grid de 3 cards -->
            <div v-else class="relacionados-footer-grid">
              <div v-for="item in relacionados" :key="item.id" class="tarjeta-changelog">
                <div class="tarjeta-header">
                  <div v-if="item.actualizacion_imagen_destacada" class="imagen-container">
                    <img :src="obtenerUrlImagen(item.actualizacion_imagen_destacada)"
                      :alt="item.actualizacion_titulo"
                      class="imagen-destacada" loading="lazy" />
                  </div>
                  <div v-else class="sin-imagen">
                    <span>🖼️</span>
                    <p>Sin imagen destacada</p>
                  </div>
                </div>

                <div class="tarjeta-cuerpo" @click="irA(item.id)">
                  <div class="metadatos-top">
                    <span class="fecha">{{ formatearFecha(item.actualizacion_fecha_publicacion) }}</span>
                    <span class="separador">|</span>
                    <span class="version-number">v{{ item.actualizacion_version || '0.0' }}</span>
                  </div>
                  <h2 class="titulo-version">{{ item.actualizacion_titulo }}</h2>
                  <p class="resumen">{{ item.actualizacion_resumen }}</p>
                </div>

                <div class="tarjeta-pie">
                  <div class="tags-container">
                    <span class="tag-gris">
                      {{ item.area_servicio?.area_servicio_nombre || 'Sin área' }}
                    </span>
                  </div>
                  <button class="btn-enlace" @click="irA(item.id)">Ver más ➔</button>
                </div>
              </div>
            </div>

          </div>
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
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
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
const contenidoRef = ref<HTMLElement | null>(null)
const headingActivo = ref<string>('resumen')

// ── Obtener el contenedor scrolleable (.content del MainLayout) ───
// window.scrollTo no funciona porque el scroll real está en .content
const getScrollContainer = (): HTMLElement => {
  return document.querySelector('.content') as HTMLElement || document.documentElement
}

interface Heading {
  id: string
  text: string
  level: number
}

const headings = computed<Heading[]>(() => {
  const contenido = actualizacion.value?.actualizacion_contenido
  if (!contenido) return []
  try {
    const parsed = typeof contenido === 'string' ? JSON.parse(contenido) : contenido
    return (parsed.blocks ?? [])
      .filter((block: any) => block.type === 'header')
      .map((block: any) => ({
        id: `heading-${block.id}`,
        text: block.data.text ?? '',
        level: block.data.level ?? 2,
      }))
  } catch {
    return []
  }
})

const asignarIdsAlHtml = () => {
  if (!contenidoRef.value || headings.value.length === 0) return
  const nodos = contenidoRef.value.querySelectorAll('h1, h2, h3, h4, h5, h6')
  nodos.forEach((nodo, index) => {
    const heading = headings.value[index]
    if (heading) nodo.id = heading.id
  })
}

let observer: IntersectionObserver | null = null

const iniciarObserver = () => {
  if (observer) observer.disconnect()

  const scrollContainer = getScrollContainer()

  observer = new IntersectionObserver(
    (entries) => {
      for (const entry of entries) {
        if (entry.isIntersecting) {
          headingActivo.value = entry.target.id
        }
      }
    },
    {
      // Usar el contenedor real de scroll como root
      root: scrollContainer === document.documentElement ? null : scrollContainer,
      rootMargin: '-20% 0px -70% 0px',
      threshold: 0,
    }
  )

  const resumen = document.getElementById('resumen')
  if (resumen) observer.observe(resumen)

  if (contenidoRef.value) {
    contenidoRef.value.querySelectorAll('h1, h2, h3, h4, h5, h6').forEach(el => {
      observer!.observe(el)
    })
  }
}

// ── Scroll al elemento usando el contenedor real ──────────────────
const scrollToElement = (elementId: string) => {
  const el = document.getElementById(elementId)
  if (!el) return

  const container = getScrollContainer()
  const offset = 90

  // offsetTop relativo al contenedor scrolleable
  const containerRect = container.getBoundingClientRect()
  const elRect = el.getBoundingClientRect()
  const top = container.scrollTop + (elRect.top - containerRect.top) - offset

  container.scrollTo({ top, behavior: 'smooth' })
}

const irAHeading = (id: string) => {
  scrollToElement(id)
}

watch(contenidoRef, (el) => {
  if (!el) return
  asignarIdsAlHtml()
  iniciarObserver()
}, { immediate: true })

// ── Scroll al tope usando el contenedor real ──────────────────────
const scrollAlTope = () => {
  getScrollContainer().scrollTo({ top: 0, behavior: 'smooth' })
}

// ── Relacionados: siempre completa hasta 3 ────────────────────────
const obtenerRelacionados = async () => {
  if (!actualizacion.value) return
  cargandoRelacionados.value = true

  try {
    const currentId = Number(props.id)
    const areaId = actualizacion.value.actualizacion_area_servicio_id
    let resultado: any[] = []

    if (areaId) {
      const respArea = await api.get('/employee/actualizaciones', {
        params: { area_servicio_id: areaId, orden: 'recientes', per_page: 10 }
      })
      resultado = (respArea.data.data as any[]).filter(item => item.id !== currentId)
    }

    if (resultado.length < 3) {
      const idsYaIncluidos = new Set([currentId, ...resultado.map((i: any) => i.id)])
      const respRecientes = await api.get('/employee/actualizaciones', {
        params: { orden: 'recientes', per_page: 20 }
      })
      const extras = (respRecientes.data.data as any[]).filter(item => !idsYaIncluidos.has(item.id))
      resultado = [...resultado, ...extras]
    }

    relacionados.value = resultado.slice(0, 3)
  } catch (err) {
    console.error('Error al cargar relacionados:', err)
    relacionados.value = []
  } finally {
    cargandoRelacionados.value = false
  }
}

const irA = (id: number) => {
  router.push({ name: 'employee-actualizaciones-show', params: { id } })
  scrollAlTope()
}

const obtenerDetalle = async () => {
  cargando.value = true
  headingActivo.value = 'resumen'
  try {
    const respuesta = await api.get(`/employee/actualizaciones/${props.id}`)
    actualizacion.value = respuesta.data.data
    scrollAlTope()
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
    year: 'numeric', month: 'long', day: 'numeric'
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

onMounted(() => { obtenerDetalle() })
onUnmounted(() => { observer?.disconnect() })
watch(() => props.id, () => {
  observer?.disconnect()
  obtenerDetalle()
})
</script>

<style scoped>
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

/* ── Carga / Error ── */
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

.error-container {
  text-align: center;
  padding: 60px 40px;
  background: white;
  border-radius: 20px;
  box-shadow: var(--shadow-sm);
  max-width: 800px;
  margin: 40px auto;
}

.error-icon { font-size: 48px; margin-bottom: 16px; }
.error-container p { color: #6b7280; margin-bottom: 20px; }

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
.btn-retry:hover { transform: translateY(-2px); box-shadow: var(--shadow-md); }

/* ── Tarjeta principal ── */
.detalle-card {
  background: white;
  border-radius: 0 0 24px 24px;
  box-shadow: var(--shadow-md);
  overflow: visible;
}

/* ── Hero Banner ── */
.hero-banner {
  position: relative;
  width: 100%;
  min-height: 300px;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  overflow: hidden;
  background-color: var(--secondary);
}

.hero-banner.sin-imagen {
  min-height: 200px;
  background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
}

.hero-image {
  position: absolute;
  top: 0; left: 0;
  width: 100%; height: 100%;
  object-fit: cover;
  z-index: 1;
}

.hero-overlay {
  position: absolute;
  top: 0; left: 0;
  width: 100%; height: 100%;
  background: linear-gradient(to top, rgba(15, 23, 42, 0.9) 0%, rgba(15, 23, 42, 0.4) 35%, rgba(15, 23, 42, 0) 100%);
  z-index: 2;
}

.hero-content {
  position: relative;
  z-index: 3;
  padding: 30px 15% 24px 15%;
  height: 100%;
  display: flex;
  flex-direction: column;
  justify-content: flex-end;
  flex-grow: 1;
}

.btn-volver {
  display: inline-flex;
  align-items: center;
  gap: 8px;
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
.btn-volver:hover { background: rgba(255, 255, 255, 1); transform: translateX(-4px); }

.arrow-icon { font-size: 1.2rem; transition: var(--transition); }

.hero-btn-pos {
  position: absolute;
  top: 20px; left: 15%;
  z-index: 4;
}

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

.estado-dot { width: 8px; height: 8px; border-radius: 50%; display: inline-block; }

.estado-publicado { background: linear-gradient(135deg, #e8f5e9, #c8e6c9); color: #2e7d32; }
.estado-publicado .estado-dot { background: #4caf50; box-shadow: 0 0 0 2px rgba(76, 175, 80, 0.2); }
.estado-borrador { background: linear-gradient(135deg, #fff8e1, #ffecb3); color: #f57c00; }
.estado-borrador .estado-dot { background: #ff9800; box-shadow: 0 0 0 2px rgba(255, 152, 0, 0.2); }
.estado-revision { background: linear-gradient(135deg, #fce4ec, #f8bbd0); color: #c62828; }
.estado-revision .estado-dot { background: #f44336; box-shadow: 0 0 0 2px rgba(244, 67, 54, 0.2); }
.estado-inactivo { background: linear-gradient(135deg, #e0e0e0, #bdbdbd); color: #424242; }
.estado-inactivo .estado-dot { background: #757575; }
.estado-default { background: linear-gradient(135deg, #e3f2fd, #bbdefb); color: #1976d2; }
.estado-default .estado-dot { background: #2196f3; }

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

.hero-meta-left { display: flex; align-items: center; gap: 16px; flex-wrap: wrap; }
.hero-meta-right { display: flex; align-items: center; }

/* ── Layout principal ── */
.contenido-principal { padding: 0 20px; }

.contenido-wrapper {
  max-width: 1400px;
  margin: 0 auto;
  display: grid;
  grid-template-columns: 260px 1fr;
  gap: 48px;
  padding: 40px 5%;
  position: relative;
}

/* ── Índice lateral ── */
.indice-sidebar {
  position: sticky;
  top: 24px;
  align-self: flex-start;
  background: #f8fafc;
  border-radius: 16px;
  padding: 20px;
  border: 1px solid #e2e8f0;
  max-height: calc(100vh - 60px);
  overflow-y: auto;
  display: flex;
  flex-direction: column;
}

.indice-header {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 16px;
  padding-bottom: 12px;
  border-bottom: 2px solid #e2e8f0;
  flex-shrink: 0;
}

.indice-icon { font-size: 1rem; color: var(--primary); }

.indice-titulo {
  font-size: 0.85rem;
  font-weight: 700;
  color: #0f172a;
  margin: 0;
  text-transform: uppercase;
  letter-spacing: 0.06em;
}

.indice-nav {
  overflow-y: auto;
  flex: 1;
  scrollbar-width: thin;
  scrollbar-color: #cbd5e1 transparent;
}
.indice-nav::-webkit-scrollbar { width: 4px; }
.indice-nav::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }

.indice-lista {
  list-style: none;
  padding: 0; margin: 0;
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.indice-item { width: 100%; }

.indice-link {
  display: flex;
  align-items: flex-start;
  gap: 8px;
  color: #64748b;
  text-decoration: none;
  font-size: 0.82rem;
  line-height: 1.4;
  padding: 6px 10px;
  border-radius: 8px;
  transition: var(--transition);
  border-left: 2px solid transparent;
}
.indice-link:hover { background: #e2e8f0; color: var(--primary); border-left-color: var(--primary); }
.indice-link.activo { background: rgba(7, 126, 157, 0.08); color: var(--primary); border-left-color: var(--primary); font-weight: 600; }

.indice-bullet { color: var(--primary); flex-shrink: 0; margin-top: 1px; }

.indice-nivel-fijo,
.indice-nivel-1,
.indice-nivel-2 { font-weight: 600; }
.indice-nivel-3 { padding-left: 22px; font-size: 0.79rem; }
.indice-nivel-4 { padding-left: 34px; font-size: 0.77rem; color: #94a3b8; }
.indice-nivel-5,
.indice-nivel-6 { padding-left: 46px; font-size: 0.75rem; color: #94a3b8; }

.indice-vacio { font-size: 0.8rem; color: #94a3b8; padding: 8px 10px; font-style: italic; }

/* ── Columna de contenido ── */
.contenido-columna { min-width: 0; max-width: 720px; margin: 0; }

.resumen-container {
  margin: 0 0 32px 0;
  padding: 24px;
  background: #f8fafc;
  border-radius: 12px;
  border-left: 4px solid var(--primary);
}

.resumen-texto { font-size: 1.1rem; line-height: 1.6; color: #334155; margin: 0; }

.contenido-container { max-width: 100%; margin: 0; padding: 0; }

/* ── EditorJS ── */
.editorjs-editor {
  font-family: system-ui, -apple-system, sans-serif;
  font-size: 1.05rem;
  line-height: 1.8;
  color: #334155;
}

:deep(.editorjs-editor p) { margin-bottom: 1.2rem; }

:deep(.editorjs-editor h1),
:deep(.editorjs-editor h2),
:deep(.editorjs-editor h3),
:deep(.editorjs-editor h4) {
  margin-top: 2rem;
  margin-bottom: 1rem;
  color: #0f172a;
  scroll-margin-top: 90px;
}

:deep(.editorjs-editor ul),
:deep(.editorjs-editor ol) { margin-bottom: 1.2rem; padding-left: 2rem; }
:deep(.editorjs-editor li) { margin-bottom: 0.4rem; }

:deep(.editorjs-editor img) {
  max-width: 100%; height: auto;
  border-radius: 12px;
  box-shadow: var(--shadow-sm);
  margin: 24px auto; display: block;
}

:deep(.editorjs-editor blockquote) {
  border-left: 4px solid var(--primary);
  padding-left: 20px;
  margin: 20px 0;
  color: #4a5568;
  font-style: italic;
}

:deep(.editorjs-editor pre) {
  background: #1e293b; color: #e2e8f0;
  padding: 16px; border-radius: 12px;
  overflow-x: auto; margin: 20px 0;
}

:deep(.editorjs-editor code) {
  background: #f1f5f9;
  padding: 2px 6px; border-radius: 6px;
  font-size: 0.9em; color: #d32f2f;
}

/* ── Relacionados footer ── */
.relacionados-footer {
  background: #f8fafc;
  border-top: 1px solid #e2e8f0;
  margin-top: 40px;
  padding: 48px 0;
}

.relacionados-footer-wrapper {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 5%;
}

.relacionados-footer-header {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 32px;
  justify-content: center;
}

.relacionados-footer-icon { color: var(--primary); font-size: 1.5rem; }

.relacionados-footer-titulo {
  font-size: 1.8rem;
  font-weight: 700;
  color: #0f172a;
  margin: 0;
}

/* Grid fijo de 3 columnas */
.relacionados-footer-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 24px;
}

/* ── Cards ── */
.tarjeta-changelog {
  border: 1px solid #eaeaea;
  border-radius: 16px;
  background-color: white;
  transition: var(--transition);
  overflow: hidden;
  display: flex;
  flex-direction: column;
  cursor: pointer;
}
.tarjeta-changelog:hover { box-shadow: var(--shadow-md); transform: translateY(-2px); }

.tarjeta-header { padding: 0; display: block; }
.imagen-container { overflow: hidden; width: 100%; }

.imagen-destacada {
  width: 100%;
  aspect-ratio: 22 / 8;
  object-fit: cover;
  display: block;
  object-position: center;
  transition: var(--transition);
}
.imagen-container:hover .imagen-destacada { transform: scale(1.02); }

.sin-imagen {
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
  border-bottom: 1px solid #e1e7f0;
  color: #9ca3af;
  width: 100%;
  aspect-ratio: 22 / 8;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  text-align: center;
}
.sin-imagen span { font-size: 32px; margin-bottom: 8px; }
.sin-imagen p { margin: 0; font-size: 0.85rem; }

.tarjeta-cuerpo {
  padding: 14px 16px;
  flex-grow: 1;
  display: flex;
  flex-direction: column;
  cursor: pointer;
}

.metadatos-top {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 10px;
  flex-wrap: wrap;
}

.separador { color: #a0aec0; font-weight: 300; }
.fecha { font-size: 0.85rem; color: #888; font-weight: 500; }

.version-number {
  display: inline-block;
  padding: 3px 10px;
  background: white;
  color: var(--primary);
  border-radius: 20px;
  font-size: 0.75rem;
  font-weight: 600;
  font-family: 'Courier New', monospace;
  border: 1px solid #e1e7f0;
}

.titulo-version {
  font-size: 1.2rem;
  font-weight: 700;
  color: #1a1a1a;
  margin: 0 0 8px 0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.resumen {
  font-size: 0.95rem;
  color: #555;
  line-height: 1.6;
  margin: 0;
  display: -webkit-box;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 2;
  overflow: hidden;
  text-overflow: ellipsis;
}

.tarjeta-pie {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 10px 16px;
  border-top: 1px solid #f0f2f5;
  margin-top: auto;
}

.tags-container { display: flex; gap: 8px; }

.tag-gris {
  background-color: #f4f5f7;
  color: #4a5568;
  font-size: 0.75rem;
  font-weight: 600;
  padding: 5px 12px;
  border-radius: 20px;
  transition: var(--transition);
}

.btn-enlace {
  background: none;
  border: none;
  color: #1a1a1a;
  font-weight: 700;
  font-size: 0.9rem;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 6px 12px;
  border-radius: 8px;
  transition: var(--transition);
}
.btn-enlace:hover { color: var(--primary); background: rgba(7, 126, 157, 0.08); }

/* ── Skeletons ── */
.skeleton-card-footer {
  border-radius: 16px;
  border: 1px solid #e1e7f0;
  background: white;
  overflow: hidden;
}

.skeleton-img-footer {
  width: 100%;
  aspect-ratio: 22 / 8;
  background: linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 50%, #f1f5f9 75%);
  background-size: 200% 100%;
  animation: shimmer 1.4s infinite;
}

.skeleton-body-footer {
  padding: 14px 16px;
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.skeleton-line-footer {
  height: 12px;
  border-radius: 6px;
  background: linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 50%, #f1f5f9 75%);
  background-size: 200% 100%;
  animation: shimmer 1.4s infinite;
}
.skeleton-line-footer.largo { width: 90%; }
.skeleton-line-footer.medio { width: 70%; }
.skeleton-line-footer.corto { width: 45%; }

@keyframes shimmer {
  0%   { background-position: 200% 0; }
  100% { background-position: -200% 0; }
}

.relacionados-footer-vacio {
  padding: 40px;
  text-align: center;
  color: #94a3b8;
  font-size: 1rem;
  border: 1px dashed #e1e7f0;
  border-radius: 12px;
  background: white;
}

/* ── Responsive ── */
@media (max-width: 1100px) {
  .relacionados-footer-grid { grid-template-columns: repeat(2, 1fr); }
}

@media (max-width: 991px) {
  .contenido-wrapper { grid-template-columns: 1fr; gap: 24px; }
  .indice-sidebar { position: relative; top: 0; max-height: 300px; }
  .contenido-columna { max-width: 100%; margin: 0 auto; }
}

@media (max-width: 768px) {
  .contenido-principal { padding: 0 16px; }
  .contenido-wrapper { padding: 24px 0; }
  .hero-banner { min-height: 250px; }
  .hero-content { padding: 60px 20px 20px 20px; }
  .hero-titulo { font-size: 1.8rem; }
  .hero-btn-pos { left: 20px; top: 16px; }
  .resumen-container { padding: 16px; }
  .relacionados-footer-titulo { font-size: 1.4rem; }
  .relacionados-footer-grid { grid-template-columns: 1fr; }
}

@media (max-width: 480px) {
  .hero-titulo { font-size: 1.5rem; }
  .hero-bottom-info { flex-direction: column; align-items: flex-start; gap: 12px; }
}
</style>