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

        <img v-if="actualizacion.actualizacion_imagen_destacada"
          :src="obtenerUrlImagen(actualizacion.actualizacion_imagen_destacada)"
          :alt="actualizacion.actualizacion_titulo || 'Imagen destacada'" class="hero-image" />

        <div class="hero-overlay" aria-hidden="true"></div>

        <div class="hero-content">
          <h1 class="hero-titulo">{{ actualizacion.actualizacion_titulo }}</h1>

          <div class="hero-bottom-info">
            <div class="hero-meta-left">
              <span class="version-badge-list" aria-label="Versión">
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

                <li v-for="heading in headings" :key="heading.id" class="indice-item">
                  <a :href="`#${heading.id}`" class="indice-link"
                    :class="[`indice-nivel-${heading.level}`, { activo: headingActivo === heading.id }]"
                    @click.prevent="irAHeading(heading.id)">
                    <span class="indice-bullet">•</span>
                    <span v-html="heading.text"></span>
                  </a>
                </li>

                <li v-if="headings.length === 0" class="indice-vacio">
                  Sin secciones
                </li>
              </ul>
            </nav>
          </aside>

          <main class="contenido-columna">
            <span v-if="actualizacion.actualizacion_resumen" id="resumen" class="resumen-anchor"
              aria-hidden="true"></span>

            <section class="contenido-container" aria-label="Contenido completo">
              <div v-if="actualizacion.actualizacion_contenido_html" ref="contenidoRef" class="editorjs-editor"
                v-html="actualizacion.actualizacion_contenido_html"></div>
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

            <div v-else class="relacionados-carousel">
              

              <div ref="relacionadosTrackRef" class="relacionados-footer-grid">
                <article v-for="item in relacionados" :key="item.id" class="tarjeta-changelog" tabindex="0"
                  @click="irA(item.id)" @keyup.enter="irA(item.id)">
                  <div class="tarjeta-header">
                    <div v-if="item.actualizacion_imagen_destacada" class="imagen-container">
                      <img :src="obtenerUrlImagen(item.actualizacion_imagen_destacada)"
                        :alt="item.actualizacion_titulo || 'Imagen destacada'" class="imagen-destacada"
                        loading="lazy" />
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
          <button class="btn-cerrar" type="button" @click="cerrarModalIndice"
            aria-label="Cerrar índice">&times;</button>
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
          <button class="btn-cerrar" type="button" @click="cerrarModalResumen"
            aria-label="Cerrar resumen">&times;</button>
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
import '../../styles/listversion.css'

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

const registrarVisualizacion = async (actualizacionId: number) => {
  if (!Number.isFinite(actualizacionId)) {
    return
  }

  try {
    await api.post(
      `/actualizaciones/${actualizacionId}/visualizacion`
    )
  } catch (error: any) {
    console.error(
      'No se pudo registrar la visualización:',
      error.response?.data ?? error
    )
  }
}

const obtenerDetalle = async () => {
  cargando.value = true
  headingActivo.value = 'resumen'
  relacionados.value = []
  actualizacion.value = null
  observer?.disconnect()

  try {
    const respuesta = await api.get(
      `/actualizaciones/${props.id}`
    )

    actualizacion.value = respuesta.data?.data ?? null

    if (actualizacion.value) {
      const actualizacionId = Number(
        actualizacion.value.id ?? props.id
      )

      void registrarVisualizacion(actualizacionId)
    }
  } catch (error: any) {
    console.error(
      'Error al cargar la actualización:',
      error.response?.data ?? error
    )

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