<template>
  <div class="vista-detalle-container">
    <div v-if="cargando" class="estado-carga">
      <div class="spinner-border text-primary mb-3" role="status">
        <span class="visually-hidden">Cargando...</span>
      </div>
      <p>Cargando actualización...</p>
    </div>

    <article v-else-if="actualizacion" class="detalle-card">
      <header class="hero-banner" :class="{ 'sin-imagen': !actualizacion.actualizacion_imagen_destacada }">
        <button class="btn-volver hero-btn-pos" type="button" @click="volver" aria-label="Volver atrás">
          <span class="arrow-icon" aria-hidden="true">←</span>
          Volver
        </button>

        <img
          v-if="actualizacion.actualizacion_imagen_destacada"
          :src="obtenerUrlImagen(actualizacion.actualizacion_imagen_destacada)"
          :alt="actualizacion.actualizacion_titulo || 'Imagen destacada'"
          class="hero-image"
        />

        <div class="hero-overlay" aria-hidden="true"></div>

        <div class="hero-content">
          <h1 class="hero-titulo">{{ actualizacion.actualizacion_titulo }}</h1>

          <div class="hero-bottom-info">
            <div class="hero-meta-left">
              <span class="version-badge" aria-label="Versión">
                v{{ actualizacion.actualizacion_version || '0.0' }}
              </span>
              <time class="fecha-texto" :datetime="actualizacion.actualizacion_fecha_publicacion">
                Publicado el: {{ formatearFecha(actualizacion.actualizacion_fecha_publicacion) }}
              </time>
            </div>

            <div class="hero-meta-right">
              <div class="tags-container2">
                <span class="tag-gris">
                  {{ actualizacion.area_servicio?.area_servicio_nombre || 'Sin área' }}
                </span>
                <span class="tag-gris">
                  {{ obtenerNombreCategorias(actualizacion) }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </header>

      <div class="contenido-principal">
        <div class="contenido-wrapper">
          <aside class="indice-sidebar" aria-label="Índice del documento">
            <div class="indice-header">
              <span class="indice-icon" aria-hidden="true">☰</span>
              <h2 class="indice-titulo">Índice</h2>
            </div>

            <nav class="indice-nav">
              <ul class="indice-lista">
                <li v-if="actualizacion.actualizacion_resumen" class="indice-item">
                  <a
                    href="#resumen"
                    class="indice-link indice-nivel-fijo"
                    :class="{ activo: headingActivo === 'resumen' }"
                    @click.prevent="irAHeading('resumen')"
                  >
                    <span class="indice-bullet">•</span>
                    <span>Resumen</span>
                  </a>
                </li>

                <li v-for="heading in headings" :key="heading.id" class="indice-item">
                  <a
                    :href="`#${heading.id}`"
                    class="indice-link"
                    :class="[`indice-nivel-${heading.level}`, { activo: headingActivo === heading.id }]"
                    @click.prevent="irAHeading(heading.id)"
                  >
                    <span class="indice-bullet">•</span>
                    <span v-html="heading.text"></span>
                  </a>
                </li>

                <li v-if="!actualizacion.actualizacion_resumen && headings.length === 0" class="indice-vacio">
                  <span>Sin secciones</span>
                </li>
              </ul>
            </nav>
          </aside>

          <main class="contenido-columna">
            <span v-if="actualizacion.actualizacion_resumen" id="resumen" class="resumen-anchor" aria-hidden="true"></span>

            <section class="contenido-container" aria-label="Contenido completo">
              <div
                v-if="actualizacion.actualizacion_contenido_html"
                ref="contenidoRef"
                class="editorjs-editor"
                v-html="actualizacion.actualizacion_contenido_html"
              ></div>
              <p v-else class="contenido-vacio">No hay contenido disponible para esta actualización.</p>
            </section>
          </main>

          <aside v-if="actualizacion.actualizacion_resumen" class="indice-resumen" aria-label="Resumen del documento">
            <div class="indice-header">
              <h2 class="indice-titulo">Resumen</h2>
            </div>

            <section class="resumen-container" aria-label="Resumen del artículo">
              <p class="resumen-texto">{{ actualizacion.actualizacion_resumen }}</p>
            </section>
          </aside>
        </div>

        <section class="relacionados-footer" aria-labelledby="relacionados-titulo">
          <div class="relacionados-footer-wrapper">
            <div class="relacionados-footer-header">
              <span class="relacionados-footer-icon" aria-hidden="true">✦</span>
              <h2 id="relacionados-titulo" class="relacionados-footer-titulo">También te puede interesar</h2>
            </div>

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

            <div v-else-if="relacionados.length === 0" class="relacionados-footer-vacio">
              <p>No hay otras publicaciones disponibles.</p>
            </div>

            <div v-else class="relacionados-footer-grid">
              <article
                v-for="item in relacionados"
                :key="item.id"
                class="tarjeta-changelog"
                tabindex="0"
                @click="irA(item.id)"
                @keyup.enter="irA(item.id)"
              >
                <div class="tarjeta-header">
                  <div v-if="item.actualizacion_imagen_destacada" class="imagen-container">
                    <img
                      :src="obtenerUrlImagen(item.actualizacion_imagen_destacada)"
                      :alt="item.actualizacion_titulo || 'Imagen destacada'"
                      class="imagen-destacada"
                      loading="lazy"
                    />
                    <div class="imagen-overlay">
                      <span class="area-label">{{ item.area_servicio?.area_servicio_nombre || 'Sin área' }}</span>
                    </div>
                  </div>

                  <div v-else class="sin-imagen-card">
                    <span class="sin-imagen-icono">🖼️</span>
                    <p>Sin imagen destacada</p>
                    <div class="imagen-overlay">
                      <span class="area-label">{{ item.area_servicio?.area_servicio_nombre || 'Sin área' }}</span>
                    </div>
                  </div>
                </div>

                <div class="tarjeta-cuerpo">
                  <div class="metadatos-top">
                    <span class="fecha">{{ formatearFecha(item.actualizacion_fecha_publicacion) }}</span>
                    <span class="separador">|</span>
                    <span class="version-number">v{{ item.actualizacion_version || '0.0' }}</span>
                  </div>

                  <h3 class="titulo-version">{{ item.actualizacion_titulo }}</h3>
                  <p class="resumen">{{ item.actualizacion_resumen || 'Sin resumen disponible.' }}</p>

                  <div class="categorias-iconos" aria-label="Categorías">
                    <div v-for="cat in obtenerCategorias(item)" :key="cat.id" class="icono-categoria">
                      <i class="ico-icon bi" :class="obtenerIconoCategoria(cat.nombre)" aria-hidden="true"></i>
                      <span class="ico-label">{{ cat.nombre }}</span>
                    </div>
                  </div>
                </div>

                <div class="tarjeta-pie">
                  <button class="btn-enlace" type="button" @click.stop="irA(item.id)">
                    Ver más
                    <i class="bi bi-arrow-right" aria-hidden="true"></i>
                  </button>
                </div>
              </article>
            </div>
          </div>
        </section>
      </div>
    </article>

    <div v-else class="error-container">
      <div class="error-icon" aria-hidden="true">⚠️</div>
      <p>No se pudo cargar la información de esta actualización.</p>
      <button class="btn-retry" type="button" @click="volver">Volver al inicio</button>
    </div>

    <footer v-if="actualizacion && !cargando" class="footer-movil" aria-label="Acciones móviles">
      <button class="btn-footer" type="button" @click="abrirModalIndice">
        <i class="bi bi-list-ul" aria-hidden="true"></i>
        <span>Índice</span>
      </button>
      <button class="btn-footer" type="button" @click="abrirModalResumen">
        <i class="bi bi-file-text" aria-hidden="true"></i>
        <span>Resumen</span>
      </button>
    </footer>

    <div v-if="mostrarModalIndice && actualizacion" class="modal-overlay" @click.self="cerrarModalIndice">
      <div class="modal-contenido" role="dialog" aria-modal="true" aria-labelledby="modal-indice-titulo">
        <header class="modal-header">
          <h3 id="modal-indice-titulo"><i class="bi bi-list-ul" aria-hidden="true"></i> Índice del documento</h3>
          <button class="btn-cerrar" type="button" @click="cerrarModalIndice" aria-label="Cerrar índice">&times;</button>
        </header>

        <div class="modal-body">
          <div v-if="!actualizacion.actualizacion_resumen && headings.length === 0" class="text-center text-muted py-4">
            No hay secciones disponibles.
          </div>

          <ul v-else class="lista-indice">
            <li v-if="actualizacion.actualizacion_resumen" @click="abrirResumenDesdeIndice">
              <span class="indice-titulo-lista">Resumen</span>
              <!-- <span class="indice-meta">Vista rápida</span> -->
            </li>

            <li v-for="heading in headings" :key="heading.id" @click="irAHeading(heading.id, true)">
              <span class="indice-titulo-lista" v-html="heading.text"></span>
            </li>
          </ul>
        </div>
      </div>
    </div>

    <div v-if="mostrarModalResumen && actualizacion" class="modal-overlay" @click.self="cerrarModalResumen">
      <div class="modal-contenido" role="dialog" aria-modal="true" aria-labelledby="modal-resumen-titulo">
        <header class="modal-header">
          <h3 id="modal-resumen-titulo"><i class="bi bi-file-text" aria-hidden="true"></i> Resumen</h3>
          <button class="btn-cerrar" type="button" @click="cerrarModalResumen" aria-label="Cerrar resumen">&times;</button>
        </header>

        <div class="modal-body">
          <div v-if="!actualizacion.actualizacion_resumen" class="text-center text-muted py-4">
            No hay resumen disponible.
          </div>

          <template v-else>
            <!-- <h4>{{ actualizacion.actualizacion_titulo }}</h4> -->
            <p class="resumen-texto">{{ actualizacion.actualizacion_resumen }}</p>
          </template>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import api from '../../api/api'
import type { Version } from '../../types/version'

const props = defineProps<{
  id: string | number
}>()

const router = useRouter()

const actualizacion = ref<any | null>(null)
const cargando = ref(true)
const relacionados = ref<any[]>([])
const cargandoRelacionados = ref(false)
const contenidoRef = ref<HTMLElement | null>(null)
const headingActivo = ref('resumen')
const mostrarModalIndice = ref(false)
const mostrarModalResumen = ref(false)

let observer: IntersectionObserver | null = null

interface Heading {
  id: string
  text: string
  level: number
}

interface CategoriaNormalizada {
  id: string | number
  nombre: string
}

const headings = computed<Heading[]>(() => {
  const contenido = actualizacion.value?.actualizacion_contenido
  if (!contenido) return []

  try {
    const parsed = typeof contenido === 'string' ? JSON.parse(contenido) : contenido

    return (parsed.blocks ?? [])
      .filter((block: any) => block.type === 'header')
      .map((block: any, index: number) => ({
        id: `heading-${block.id ?? index}`,
        text: block.data?.text ?? '',
        level: Math.min(Math.max(Number(block.data?.level ?? 2), 1), 6),
      }))
  } catch (error) {
    console.warn('No se pudo procesar el índice del contenido:', error)
    return []
  }
})

const getScrollContainer = (): HTMLElement => {
  return document.querySelector<HTMLElement>('.content') ?? document.documentElement
}

const asignarIdsAlHtml = () => {
  if (!contenidoRef.value || headings.value.length === 0) return

  const nodos = contenidoRef.value.querySelectorAll<HTMLElement>('h1, h2, h3, h4, h5, h6')
  nodos.forEach((nodo, index) => {
    const heading = headings.value[index]
    if (heading) nodo.id = heading.id
  })
}

const iniciarObserver = () => {
  observer?.disconnect()
  observer = null

  const elementosObservables: Element[] = []
  const resumen = document.getElementById('resumen')
  if (resumen) elementosObservables.push(resumen)

  if (contenidoRef.value) {
    elementosObservables.push(...Array.from(contenidoRef.value.querySelectorAll('h1, h2, h3, h4, h5, h6')))
  }

  if (elementosObservables.length === 0) return

  const scrollContainer = getScrollContainer()
  observer = new IntersectionObserver(
    (entries) => {
      const visibleEntry = entries.find(entry => entry.isIntersecting)
      if (visibleEntry?.target.id) headingActivo.value = visibleEntry.target.id
    },
    {
      root: scrollContainer === document.documentElement ? null : scrollContainer,
      rootMargin: '-20% 0px -70% 0px',
      threshold: 0,
    },
  )

  elementosObservables.forEach(element => observer?.observe(element))
}

const prepararContenido = async () => {
  await nextTick()
  asignarIdsAlHtml()
  iniciarObserver()
}

const scrollToElement = (elementId: string) => {
  const el = document.getElementById(elementId)
  if (!el) return

  const container = getScrollContainer()
  const offset = 90
  const containerRect = container.getBoundingClientRect()
  const elRect = el.getBoundingClientRect()
  const top = container.scrollTop + (elRect.top - containerRect.top) - offset

  headingActivo.value = elementId
  container.scrollTo({ top: Math.max(top, 0), behavior: 'smooth' })
}

const scrollAlTope = () => {
  getScrollContainer().scrollTo({ top: 0, behavior: 'smooth' })
}

const abrirModalIndice = () => {
  mostrarModalIndice.value = true
}

const cerrarModalIndice = () => {
  mostrarModalIndice.value = false
}

const abrirModalResumen = () => {
  mostrarModalResumen.value = true
}

const cerrarModalResumen = () => {
  mostrarModalResumen.value = false
}

const abrirResumenDesdeIndice = () => {
  cerrarModalIndice()
  abrirModalResumen()
}

const irAHeading = (id: string, cerrarModal = false) => {
  if (cerrarModal) cerrarModalIndice()
  nextTick(() => scrollToElement(id))
}

const normalizarTexto = (texto: string): string =>
  texto
    .toLowerCase()
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .trim()

const obtenerIconoCategoria = (nombre: string | undefined): string => {
  if (!nombre) return 'bi-tag-fill'

  const n = normalizarTexto(nombre)
  const mapa: Record<string, string> = {
    'manual de usuario': 'bi-person-lines-fill',
    'manual tecnico': 'bi-tools',
    instalador: 'bi-box-arrow-down',
    'actualizacion del sistema': 'bi-arrow-repeat',
    'nueva funcionalidad': 'bi-stars',
    mejora: 'bi-arrow-up-circle-fill',
    'correccion de errores': 'bi-bug-fill',
    'parche de seguridad': 'bi-shield-fill-check',
    'guia de instalacion': 'bi-journal-arrow-down',
    'guia rapida': 'bi-lightning-charge-fill',
    documentacion: 'bi-file-earmark-text-fill',
    'notas de version': 'bi-card-list',
    general: 'bi-info-circle-fill',
  }

  if (mapa[n]) return mapa[n]
  if (n.includes('manual')) return 'bi-journal-text'
  if (n.includes('instal')) return 'bi-box-arrow-down'
  if (n.includes('actualizacion')) return 'bi-arrow-repeat'
  if (n.includes('funcionalidad')) return 'bi-stars'
  if (n.includes('mejora')) return 'bi-arrow-up-circle-fill'
  if (n.includes('correccion') || n.includes('error')) return 'bi-bug-fill'
  if (n.includes('seguridad') || n.includes('parche')) return 'bi-shield-fill-check'
  if (n.includes('guia')) return 'bi-journal-bookmark-fill'
  if (n.includes('documentacion')) return 'bi-file-earmark-text-fill'
  if (n.includes('version')) return 'bi-card-list'
  if (n.includes('general')) return 'bi-info-circle-fill'

  return 'bi-tag-fill'
}

const obtenerCategorias = (item: Version | any): CategoriaNormalizada[] => {
  if (Array.isArray(item?.categorias) && item.categorias.length > 0) {
    return item.categorias.map((categoria: any, index: number) => ({
      id: categoria.categoria_actualizacion_id ?? categoria.id ?? `${item.id ?? 'item'}-cat-${index}`,
      nombre: categoria.categoria_actualizacion_nombre ?? categoria.nombre ?? 'Sin categoría',
    }))
  }

  if (item?.categoria) {
    const categoria = item.categoria
    return [{
      id: categoria.categoria_actualizacion_id ?? categoria.id ?? `${item.id ?? 'item'}-cat`,
      nombre: categoria.categoria_actualizacion_nombre ?? categoria.nombre ?? 'Sin categoría',
    }]
  }

  return [{ id: `${item?.id ?? 'item'}-sin-categoria`, nombre: 'Sin categoría' }]
}

const obtenerNombreCategorias = (item: any): string => {
  if (Array.isArray(item?.categorias) && item.categorias.length > 0) {
    const nombres = item.categorias
      .map((categoria: any) => categoria.categoria_actualizacion_nombre ?? categoria.nombre)
      .filter(Boolean)

    return nombres.length > 0 ? nombres.join(', ') : 'Sin categoría'
  }

  return item?.categoria?.categoria_actualizacion_nombre ?? item?.categoria?.nombre ?? 'Sin categoría'
}

const obtenerRelacionados = async () => {
  if (!actualizacion.value) return
  cargandoRelacionados.value = true

  try {
    const currentId = Number(props.id)
    const areaId = actualizacion.value.actualizacion_area_servicio_id
    let resultado: any[] = []

    if (areaId) {
      const respArea = await api.get('/actualizaciones', {
        params: { area_servicio_id: areaId, orden: 'recientes', per_page: 10 },
      })
      resultado = (respArea.data?.data ?? []).filter((item: any) => Number(item.id) !== currentId)
    }

    if (resultado.length < 3) {
      const idsYaIncluidos = new Set([currentId, ...resultado.map((item: any) => Number(item.id))])
      const respRecientes = await api.get('/actualizaciones', {
        params: { orden: 'recientes', per_page: 20 },
      })
      const extras = (respRecientes.data?.data ?? []).filter((item: any) => !idsYaIncluidos.has(Number(item.id)))
      resultado = [...resultado, ...extras]
    }

    relacionados.value = resultado.slice(0, 3)
  } catch (error) {
    console.error('Error al cargar relacionados:', error)
    relacionados.value = []
  } finally {
    cargandoRelacionados.value = false
  }
}

const obtenerDetalle = async () => {
  cargando.value = true
  headingActivo.value = 'resumen'
  relacionados.value = []
  actualizacion.value = null
  observer?.disconnect()

  try {
    const respuesta = await api.get(`/actualizaciones/${props.id}`)
    actualizacion.value = respuesta.data?.data ?? null
  } catch (error) {
    console.error('Error al cargar la actualización:', error)
    actualizacion.value = null
  } finally {
    cargando.value = false
  }

  if (actualizacion.value) {
    await prepararContenido()
    scrollAlTope()
    void obtenerRelacionados()
  }
}

const irA = (id: number) => {
  router.push({ name: 'actualizaciones-show', params: { id } })
}

const volver = () => {
  router.push({ name: 'actualizaciones' })
}

const formatearFecha = (fechaString?: string) => {
  if (!fechaString) return 'Sin fecha'

  const fecha = new Date(fechaString)
  if (Number.isNaN(fecha.getTime())) return 'Sin fecha'

  return fecha.toLocaleDateString('es-ES', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  })
}

const obtenerUrlImagen = (ruta?: string) => {
  if (!ruta) return ''
  if (ruta.startsWith('http')) return ruta

  const baseUrl = import.meta.env.VITE_API_URL || 'http://localhost:8000'
  return `${baseUrl}/storage/${ruta}`
}

onMounted(() => {
  void obtenerDetalle()
})

onUnmounted(() => {
  observer?.disconnect()
})

watch(
  () => props.id,
  () => {
    cerrarModalIndice()
    cerrarModalResumen()
    void obtenerDetalle()
  },
)

watch(
  () => actualizacion.value?.actualizacion_contenido_html,
  () => {
    if (actualizacion.value && !cargando.value) void prepararContenido()
  },
  { flush: 'post' },
)
</script>

<style scoped>
:global(:root) {
  --primary: #077e9d;
  --secondary: #025b7d;
  --warning: #fcbb1c;
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

.estado-carga,
.error-container {
  max-width: 800px;
  margin: 40px auto;
  padding: 60px 40px;
  text-align: center;
  background: #ffffff;
  border-radius: 20px;
  color: #6b7280;
}

.error-icon {
  margin-bottom: 16px;
  font-size: 48px;
}

.btn-retry,
.btn-ver-detalle {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 24px;
  color: #ffffff;
  font-weight: 600;
  border: 0;
  border-radius: 999px;
  background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
  cursor: pointer;
  transition: var(--transition);
}

.btn-retry:hover,
.btn-ver-detalle:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow-md);
}

.detalle-card {
  overflow: visible;
  background: #ffffff;
  border-radius: 0 0 24px 24px;
  box-shadow: var(--shadow-md);
}

.hero-banner {
  position: relative;
  display: flex;
  min-height: 300px;
  overflow: hidden;
  background: var(--secondary);
}

.hero-banner.sin-imagen {
  min-height: 220px;
  background:
    radial-gradient(circle at 15% 10%, rgba(252, 187, 28, 0.28), transparent 30%),
    linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
}

.hero-image,
.hero-overlay {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
}

.hero-image {
  z-index: 1;
  object-fit: cover;
}

.hero-overlay {
  z-index: 2;
  background: linear-gradient(to top, rgba(15, 23, 42, 0.92) 0%, rgba(15, 23, 42, 0.48) 42%, rgba(15, 23, 42, 0.06) 100%);
}

.hero-content {
  position: relative;
  z-index: 3;
  display: flex;
  flex: 1;
  flex-direction: column;
  justify-content: flex-end;
  padding: 72px 15% 28px;
}

.hero-titulo {
  max-width: 980px;
  margin: 0 0 18px;
  color: #ffffff;
  font-size: clamp(1.65rem, 3vw, 2.55rem);
  font-weight: 760;
  line-height: 1.12;
  text-wrap: balance;
  text-shadow: 0 2px 8px rgba(0, 0, 0, 0.42);
}

.hero-bottom-info,
.hero-meta-left,
.hero-meta-right,
.tags-container2 {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
}

.hero-bottom-info {
  justify-content: space-between;
  gap: 16px;
}

.hero-meta-left {
  gap: 16px;
}

.tags-container2 {
  gap: 10px;
}

.btn-volver {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 8px 16px;
  color: var(--primary);
  font-size: 0.95rem;
  font-weight: 700;
  background: rgba(255, 255, 255, 0.9);
  border: 0;
  border-radius: 12px;
  cursor: pointer;
  transition: var(--transition);
}

.btn-volver:hover {
  background: #ffffff;
  transform: translateX(-4px);
}

.hero-btn-pos {
  position: absolute;
  top: 20px;
  left: 15%;
  z-index: 4;
}

.arrow-icon {
  font-size: 1.2rem;
}

.version-badge,
.version-number {
  display: inline-block;
  font-family: 'Courier New', monospace;
  font-weight: 700;
  border-radius: 999px;
}

.version-badge {
  padding: 6px 14px;
  color: #ffffff;
  font-size: 0.85rem;
  background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
  box-shadow: var(--shadow-sm);
}

.fecha-texto {
  color: rgba(255, 255, 255, 0.92);
  font-size: 0.95rem;
  font-weight: 600;
  text-shadow: 0 1px 4px rgba(0, 0, 0, 0.46);
}

.tag-gris {
  padding: 5px 12px;
  color: #4a5568;
  font-size: 0.75rem;
  font-weight: 700;
  background: #f4f5f7;
  border-radius: 999px;
  transition: var(--transition);
}

.tag-gris:hover {
  color: #ffffff;
  background: var(--primary);
}

.contenido-principal {
  padding: 0 20px;
}

.contenido-wrapper {
  display: grid;
  grid-template-columns: minmax(180px, 220px) minmax(0, 1fr) minmax(240px, 300px);
  gap: clamp(24px, 3vw, 40px);
  max-width: 1440px;
  margin: 0 auto;
  padding: 40px 5%;
}

.indice-sidebar,
.indice-resumen {
  position: sticky;
  top: 24px;
  align-self: flex-start;
  display: flex;
  flex-direction: column;
  max-height: calc(100vh - 60px);
  padding: 20px;
  overflow-y: auto;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 16px;
}

.indice-header {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-shrink: 0;
  margin-bottom: 16px;
  padding-bottom: 12px;
  border-bottom: 2px solid #e2e8f0;
}

.indice-icon {
  color: var(--primary);
  font-size: 1rem;
}

.indice-titulo {
  margin: 0;
  color: #0f172a;
  font-size: 0.85rem;
  font-weight: 800;
  letter-spacing: 0.06em;
  text-transform: uppercase;
}

.indice-nav {
  flex: 1;
  overflow-y: auto;
  scrollbar-color: #cbd5e1 transparent;
  scrollbar-width: thin;
}

.indice-nav::-webkit-scrollbar {
  width: 4px;
}

.indice-nav::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 4px;
}

.indice-lista,
.lista-indice {
  display: flex;
  flex-direction: column;
  gap: 4px;
  padding: 0;
  margin: 0;
  list-style: none;
}

.indice-link {
  display: flex;
  gap: 8px;
  align-items: flex-start;
  padding: 7px 10px;
  color: #64748b;
  font-size: 0.82rem;
  line-height: 1.4;
  text-decoration: none;
  border-left: 2px solid transparent;
  border-radius: 8px;
  transition: var(--transition);
}

.indice-link:hover,
.indice-link.activo {
  color: var(--primary);
  background: rgba(7, 126, 157, 0.08);
  border-left-color: var(--primary);
}

.indice-link.activo {
  font-weight: 700;
}

.indice-bullet {
  flex-shrink: 0;
  color: var(--primary);
}

.indice-nivel-fijo,
.indice-nivel-1,
.indice-nivel-2 {
  font-weight: 700;
}

.indice-nivel-3 {
  padding-left: 22px;
  font-size: 0.79rem;
}

.indice-nivel-4 {
  padding-left: 34px;
  color: #94a3b8;
  font-size: 0.77rem;
}

.indice-nivel-5,
.indice-nivel-6 {
  padding-left: 46px;
  color: #94a3b8;
  font-size: 0.75rem;
}

.indice-vacio,
.contenido-vacio {
  padding: 8px 10px;
  color: #94a3b8;
  font-size: 0.9rem;
  font-style: italic;
}

.contenido-columna {
  min-width: 0;
  max-width: 100%;
}

.contenido-container {
  max-width: 100%;
}

.resumen-anchor {
  display: block;
  height: 1px;
  scroll-margin-top: 90px;
}

.resumen-header {
  margin-bottom: 12px;
}

.resumen-icon {
  display: inline-grid;
  width: 26px;
  height: 26px;
  place-items: center;
  color: #ffffff;
  font-size: 0.78rem;
  background: linear-gradient(135deg, var(--primary), var(--secondary));
  border-radius: 999px;
  box-shadow: 0 8px 18px rgba(7, 126, 157, 0.18);
}

.resumen-container {
  padding: 0;
  margin: 0;
  background: transparent;
}

.resumen-meta-mini {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-bottom: 12px;
}

.resumen-meta-mini span {
  display: inline-flex;
  align-items: center;
  width: fit-content;
  padding: 4px 10px;
  color: #0f5f78;
  font-size: 0.72rem;
  font-weight: 800;
  line-height: 1;
  background: rgba(7, 126, 157, 0.08);
  border: 1px solid rgba(7, 126, 157, 0.12);
  border-radius: 999px;
}

.resumen-texto {
  margin: 0;
  color: #334155;
  font-size: 0.95rem;
  line-height: 1.7;
}

.indice-resumen .resumen-texto {
  color: #243246;
  font-size: clamp(0.95rem, 0.85rem + 0.25vw, 1.02rem);
  line-height: 1.72;
}

.editorjs-editor {
  color: #334155;
  font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
  font-size: 1.05rem;
  line-height: 1.8;
}

:deep(.editorjs-editor p) {
  margin-bottom: 1.2rem;
}

:deep(.editorjs-editor h1),
:deep(.editorjs-editor h2),
:deep(.editorjs-editor h3),
:deep(.editorjs-editor h4),
:deep(.editorjs-editor h5),
:deep(.editorjs-editor h6) {
  margin-top: 2rem;
  margin-bottom: 1rem;
  color: #0f172a;
  scroll-margin-top: 90px;
}

:deep(.editorjs-editor ul),
:deep(.editorjs-editor ol) {
  margin-bottom: 1.2rem;
  padding-left: 2rem;
}

:deep(.editorjs-editor li) {
  margin-bottom: 0.4rem;
}

:deep(.editorjs-editor img) {
  display: block;
  max-width: 100%;
  height: auto;
  margin: 24px auto;
  border-radius: 12px;
  box-shadow: var(--shadow-sm);
}

:deep(.editorjs-editor blockquote) {
  padding-left: 20px;
  margin: 20px 0;
  color: #4a5568;
  font-style: italic;
  border-left: 4px solid var(--primary);
}

:deep(.editorjs-editor pre) {
  padding: 16px;
  margin: 20px 0;
  overflow-x: auto;
  color: #e2e8f0;
  background: #1e293b;
  border-radius: 12px;
}

:deep(.editorjs-editor code) {
  padding: 2px 6px;
  color: #d32f2f;
  font-size: 0.9em;
  background: #f1f5f9;
  border-radius: 6px;
}

:deep(.editorjs-checklist) {
  padding-left: 0;
  list-style: none;
}

:deep(.editorjs-checklist__row) {
  display: flex;
  gap: 0.65rem;
  align-items: flex-start;
}

:deep(.editorjs-checklist__checkbox) {
  width: 1rem;
  height: 1rem;
  margin-top: 0.38rem;
  accent-color: var(--primary);
}

:deep(.editorjs-checklist__item.is-checked .editorjs-checklist__content) {
  color: #64748b;
  text-decoration: line-through;
}

.relacionados-footer {
  padding: 48px 0;
  margin-top: 40px;
  background: #f8fafc;
  border-top: 1px solid #e2e8f0;
}

.relacionados-footer-wrapper {
  max-width: 1200px;
  padding: 0 5%;
  margin: 0 auto;
}

.relacionados-footer-header {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
  margin-bottom: 32px;
}

.relacionados-footer-icon {
  color: var(--primary);
  font-size: 1.5rem;
}

.relacionados-footer-titulo {
  margin: 0;
  color: #0f172a;
  font-size: 1.8rem;
  font-weight: 800;
}

.relacionados-footer-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 24px;
}

.tarjeta-changelog {
  display: flex;
  flex-direction: column;
  overflow: hidden;
  background: #ffffff;
  border: 1px solid #eaeaea;
  border-radius: 16px;
  cursor: pointer;
  transition: var(--transition);
}

.tarjeta-changelog:hover,
.tarjeta-changelog:focus-visible {
  outline: none;
  box-shadow: var(--shadow-md);
  transform: translateY(-2px);
}

.tarjeta-header,
.imagen-container,
.sin-imagen-card {
  position: relative;
  overflow: hidden;
}

.imagen-destacada,
.sin-imagen-card {
  width: 100%;
  aspect-ratio: 22 / 8;
}

.imagen-destacada {
  display: block;
  object-fit: cover;
  object-position: center;
  transition: var(--transition);
}

.tarjeta-changelog:hover .imagen-destacada {
  transform: scale(1.03);
}

.sin-imagen-card {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  color: #9ca3af;
  text-align: center;
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
  border-bottom: 1px solid #e1e7f0;
}

.sin-imagen-icono {
  margin-bottom: 8px;
  font-size: 32px;
}

.sin-imagen-card p {
  margin: 0;
  font-size: 0.85rem;
}

.imagen-overlay {
  position: absolute;
  inset: 0;
  display: flex;
  align-items: flex-end;
  justify-content: flex-end;
  padding: 10px 12px;
  pointer-events: none;
  background: linear-gradient(to top left, rgba(0, 0, 0, 0.78) 0%, rgba(0, 0, 0, 0.15) 38%, transparent 62%);
}

.area-label {
  display: inline-block;
  padding: 4px 12px;
  color: #ffffff;
  font-size: 0.72rem;
  font-weight: 800;
  letter-spacing: 0.03em;
  text-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
  background: rgba(255, 255, 255, 0.15);
  border: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: 999px;
  backdrop-filter: blur(6px);
}

.tarjeta-cuerpo {
  display: flex;
  flex: 1;
  flex-direction: column;
  padding: 14px 16px;
}

.metadatos-top {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 10px;
  margin-bottom: 10px;
}

.fecha {
  color: #6b7280;
  font-size: 0.85rem;
  font-weight: 600;
}

.separador {
  color: #a0aec0;
  font-weight: 300;
}

.version-number {
  padding: 3px 10px;
  color: var(--primary);
  font-size: 0.75rem;
  background: #ffffff;
  border: 1px solid #e1e7f0;
}

.titulo-version {
  display: -webkit-box;
  margin: 0 0 8px;
  overflow: hidden;
  color: #1a1a1a;
  font-size: 1.1rem;
  font-weight: 800;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 2;
}

.resumen {
  display: -webkit-box;
  margin: 0 0 10px;
  overflow: hidden;
  color: #555555;
  font-size: 0.9rem;
  line-height: 1.5;
  text-overflow: ellipsis;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 3;
}

.categorias-iconos {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  padding-top: 8px;
  margin-top: auto;
}

.icono-categoria {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 30px;
  max-width: 30px;
  height: 30px;
  padding: 0 7px;
  overflow: hidden;
  cursor: default;
  background: #f4f5f7;
  border: 1px solid transparent;
  border-radius: 8px;
  transition: max-width 0.32s cubic-bezier(0.4, 0, 0.2, 1), background 0.22s ease, border-color 0.22s ease, padding 0.32s cubic-bezier(0.4, 0, 0.2, 1);
}

.icono-categoria:hover {
  max-width: 220px;
  padding: 0 10px;
  background: rgba(7, 126, 157, 0.1);
  border-color: rgba(7, 126, 157, 0.25);
}

.ico-icon {
  flex-shrink: 0;
  width: 16px;
  color: var(--secondary);
  font-size: 16px;
  opacity: 1;
  transition: opacity 0.15s ease, width 0.22s ease, margin 0.22s ease;
}

.icono-categoria:hover .ico-icon {
  width: 0;
  margin: 0;
  opacity: 0;
}

.ico-label {
  max-width: 0;
  overflow: hidden;
  color: var(--primary);
  font-size: 11px;
  font-weight: 800;
  white-space: nowrap;
  opacity: 0;
  transition: max-width 0.32s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.18s ease 0.08s;
}

.icono-categoria:hover .ico-label {
  max-width: 190px;
  opacity: 1;
}

.tarjeta-pie {
  display: flex;
  justify-content: flex-end;
  padding: 10px 16px;
  margin-top: auto;
  border-top: 1px solid #f0f2f5;
}

.btn-enlace {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 10px;
  color: #1a1a1a;
  font-size: 0.85rem;
  font-weight: 700;
  background: transparent;
  border: 0;
  border-radius: 8px;
  cursor: pointer;
  transition: var(--transition);
}

.btn-enlace i {
  font-size: 0.9rem;
  transition: transform 0.3s ease;
}

.btn-enlace:hover {
  color: var(--primary);
  background: rgba(7, 126, 157, 0.08);
}

.btn-enlace:hover i {
  transform: translateX(4px);
}

.skeleton-card-footer {
  overflow: hidden;
  background: #ffffff;
  border: 1px solid #e1e7f0;
  border-radius: 16px;
}

.skeleton-img-footer {
  width: 100%;
  aspect-ratio: 22 / 8;
}

.skeleton-body-footer {
  display: flex;
  flex-direction: column;
  gap: 10px;
  padding: 14px 16px;
}

.skeleton-img-footer,
.skeleton-line-footer {
  background: linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 50%, #f1f5f9 75%);
  background-size: 200% 100%;
  animation: shimmer 1.4s infinite;
}

.skeleton-line-footer {
  height: 12px;
  border-radius: 6px;
}

.skeleton-line-footer.largo {
  width: 90%;
}

.skeleton-line-footer.medio {
  width: 70%;
}

.skeleton-line-footer.corto {
  width: 45%;
}

@keyframes shimmer {
  0% { background-position: 200% 0; }
  100% { background-position: -200% 0; }
}

.relacionados-footer-vacio {
  padding: 40px;
  color: #94a3b8;
  font-size: 1rem;
  text-align: center;
  background: #ffffff;
  border-radius: 12px;
}

.footer-movil {
  display: none;
}

.btn-footer {
  display: flex;
  flex: 1;
  flex-direction: column;
  align-items: center;
  max-width: 140px;
  gap: 3px;
  padding: 6px 20px;
  color: #4b5563;
  font-size: 0.7rem;
  font-weight: 700;
  background: transparent;
  border: 0;
  border-radius: 999px;
  cursor: pointer;
  transition: var(--transition);
}

.btn-footer i {
  color: var(--secondary);
  font-size: 1.55rem;
  transition: var(--transition);
}

.btn-footer:active {
  transform: scale(0.94);
}

.btn-footer:hover i {
  color: var(--primary);
}

.modal-overlay {
  position: fixed;
  inset: 0;
  z-index: 1050;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
  background: rgba(0, 0, 0, 0.45);
  backdrop-filter: blur(6px);
  animation: fadeIn 0.25s ease;
}

.modal-contenido {
  display: flex;
  flex-direction: column;
  width: 100%;
  max-width: 560px;
  max-height: 85vh;
  overflow: hidden;
  background: #ffffff;
  border-radius: 24px;
  box-shadow: 0 24px 64px rgba(0, 0, 0, 0.25);
  animation: slideUp 0.3s cubic-bezier(0.34, 1.2, 0.64, 1);
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

@keyframes slideUp {
  from {
    opacity: 0;
    transform: translateY(30px) scale(0.96);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

.modal-header {
  display: flex;
  flex-shrink: 0;
  align-items: center;
  justify-content: space-between;
  padding: 18px 24px;
  border-bottom: 1px solid #eef2f6;
}

.modal-header h3 {
  display: flex;
  align-items: center;
  gap: 10px;
  margin: 0;
  color: #1f2937;
  font-size: 1.2rem;
  font-weight: 800;
}

.modal-header h3 i {
  color: var(--primary);
}

.btn-cerrar {
  padding: 0 6px;
  color: #9ca3af;
  font-size: 2rem;
  line-height: 1;
  background: transparent;
  border: 0;
  cursor: pointer;
  transition: var(--transition);
}

.btn-cerrar:hover {
  color: #1f2937;
  transform: rotate(90deg);
}

.modal-body {
  flex: 1;
  padding: 20px 24px;
  overflow-y: auto;
}

.lista-indice li {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 12px 14px;
  background: #f8fafc;
  border: 1px solid transparent;
  border-radius: 12px;
  cursor: pointer;
  transition: var(--transition);
}

.lista-indice li:hover {
  background: #f1f5f9;
  border-color: #e2e8f0;
}

.lista-indice li:active {
  transform: scale(0.97);
}

.indice-titulo-lista {
  flex: 1;
  overflow: hidden;
  color: #1f2937;
  font-size: 0.9rem;
  font-weight: 700;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.indice-meta {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  color: #6b7280;
  font-size: 0.75rem;
  white-space: nowrap;
}

.resumen-metadata {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
  margin: 8px 0 16px;
}

.badge-version {
  padding: 4px 14px;
  color: var(--primary);
  font-family: monospace;
  font-size: 0.85rem;
  font-weight: 800;
  background: rgba(7, 126, 157, 0.12);
  border-radius: 999px;
}

.badge-fecha {
  color: #6b7280;
  font-size: 0.85rem;
  font-weight: 600;
}

@media (max-width: 1280px) and (min-width: 992px) {
  .contenido-wrapper {
    grid-template-columns: minmax(170px, 220px) minmax(0, 1fr);
    align-items: start;
    gap: 28px;
    padding-inline: 4%;
  }

  .indice-sidebar {
    grid-column: 1;
    grid-row: 1 / span 2;
  }

  .indice-resumen {
    grid-column: 2;
    grid-row: 1;
    position: relative;
    top: auto;
    max-height: none;
    padding: 18px 20px 20px 22px;
    overflow: visible;
    border-radius: 18px;
  }

  .contenido-columna {
    grid-column: 2;
    grid-row: 2;
  }

  .indice-resumen .resumen-container {
    display: grid;
    gap: 2px;
  }
}

@media (max-width: 991px) {
  .contenido-wrapper {
    grid-template-columns: 1fr;
    gap: 20px;
  }

  .indice-sidebar {
    position: relative;
    top: 0;
    order: 1;
    max-height: 300px;
  }

  .indice-resumen {
    position: relative;
    top: 0;
    order: 2;
    max-height: none;
    padding: 20px 22px 22px 24px;
    overflow: visible;
    border-radius: 18px;
  }

  .contenido-columna {
    order: 3;
  }

  .indice-resumen .resumen-texto {
    font-size: 1rem;
    line-height: 1.65;
  }

  .relacionados-footer-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 767px) {
  .contenido-principal {
    padding: 0 16px 86px;
  }

  .contenido-wrapper {
    padding: 24px 0;
  }

  .indice-sidebar,
  .indice-resumen {
    display: none;
  }

  .hero-banner {
    min-height: 250px;
  }

  .hero-content {
    padding: 60px 20px 20px;
  }

  .hero-btn-pos {
    top: 16px;
    left: 20px;
  }

  .hero-bottom-info {
    align-items: flex-start;
    flex-direction: column;
  }

  .relacionados-footer-titulo {
    font-size: 1.4rem;
  }

  .relacionados-footer-grid {
    grid-template-columns: 1fr;
  }

  .footer-movil {
    position: fixed;
    right: 0;
    bottom: 0;
    left: 0;
    z-index: 900;
    display: flex;
    align-items: center;
    justify-content: space-around;
    padding: 10px 16px;
    padding-bottom: max(10px, env(safe-area-inset-bottom));
    background: rgba(255, 255, 255, 0.92);
    border-top: 1px solid rgba(0, 0, 0, 0.06);
    box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.08);
    backdrop-filter: blur(12px);
  }
}

@media (min-width: 768px) {
  .titulo-version {
    font-size: 1.2rem;
    white-space: nowrap;
    text-overflow: ellipsis;
    -webkit-line-clamp: 1;
  }

  .resumen {
    -webkit-line-clamp: 2;
  }
}

@media (max-width: 480px) {
  .modal-contenido {
    max-width: 100%;
    margin: 10px;
    border-radius: 20px;
  }

  .modal-header {
    padding: 14px 16px;
  }

  .modal-header h3 {
    font-size: 1rem;
  }

  .modal-body {
    padding: 16px;
  }

  .lista-indice li {
    align-items: flex-start;
    flex-direction: column;
    gap: 6px;
  }

  .btn-footer {
    padding: 4px 12px;
    font-size: 0.65rem;
  }

  .btn-footer i {
    font-size: 1.4rem;
  }
}
</style>
