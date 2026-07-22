<template>
  <div class="contenedor-lista">
    <PageHero eyebrow="Blog" titulo="Actualizaciones" />

    <div v-if="cargando && actualizaciones.length === 0" class="estado-mensaje">
      <div class="spinner-border text-primary mb-3" role="status">
        <span class="visually-hidden">Cargando...</span>
      </div>
      <p>Cargando actualizaciones...</p>
    </div>

    <div v-else-if="error" class="estado-mensaje error">
      <div class="error-icon">⚠️</div>
      <p>{{ error }}</p>
      <button @click="obtenerActualizaciones(1)" class="btn-retry">
        Reintentar
      </button>
    </div>

    <div v-else-if="actualizaciones.length === 0 && !hayFiltrosActivos" class="estado-mensaje vacio">
      <div class="empty-icon">📭</div>
      <h3>No hay actualizaciones para mostrar</h3>
      <p>Aún no se ha registrado ninguna publicación en el sistema.
      </p>
    </div>

    <div v-else class="vista-con-filtros">
      <!-- Botón toggle para filtros en móvil -->
      <div class="filtros-toggle-wrapper d-md-none">
        <button class="btn-toggle-filtros" @click="mostrarFiltros = !mostrarFiltros" type="button">
          <i class="bi bi-funnel"></i>
          {{ mostrarFiltros ? 'Ocultar filtros' : 'Mostrar filtros' }}
          <span v-if="hayFiltrosActivos" class="filtros-activos-badge">•</span>
        </button>
      </div>

      <!-- Filtros -->
      <div :class="['filtros-barra', { 'filtros-visible': mostrarFiltros }]">
        <div class="filtro-seccion-completa">
          <label class="filtro-label">Ordenar por</label>

          <div class="chips-grupo">
            <span v-for="op in opcionesOrden" :key="op.valor" class="chip"
              :class="{ activo: filtros.orden === op.valor }" @click="aplicarOrden(op.valor)">
              {{ op.label }}
            </span>
          </div>
        </div>

        <div class="filtro-grupo filtro-busqueda">
          <label for="busqueda" class="filtro-label">Buscar</label>

          <div class="input-busqueda-wrapper">
            <i class="bi bi-search icono-busqueda"></i>

            <input id="busqueda" v-model="filtros.busqueda" type="text" class="filtro-input"
              placeholder="Título o resumen..." @keydown.enter.prevent="buscarPorTexto" />

            <button v-if="filtros.busqueda" type="button" class="btn-limpiar-busqueda" @click="limpiarBusqueda"
              title="Limpiar búsqueda">
              <i class="bi bi-x-lg"></i>
            </button>

            <button type="button" class="btn-ejecutar-busqueda" @click="buscarPorTexto" title="Buscar">
              <i class="bi bi-arrow-return-left"></i>
            </button>
          </div>

          <!-- <div class="input-busqueda-wrapper">
            <i class="bi bi-search icono-busqueda"></i>
            <input id="busqueda" v-model="filtros.busqueda" type="text" class="filtro-input"
              placeholder="Título o resumen..." />
          </div> -->
        </div>

        <!-- Área / Servicio -->
        <div class="filtro-grupo">
          <label class="filtro-label">Área / Servicio</label>

          <div class="categoria-select-wrapper" :class="{ open: areaDropdownAbierto }">
            <div class="categoria-select-trigger" @click="areaDropdownAbierto = !areaDropdownAbierto">
              <div class="select-placeholder" v-if="!areaSeleccionada">
                <i class="bi bi-building"></i>
                <span>Selecciona un área...</span>
              </div>

              <div class="select-selected" v-else>
                <span class="selected-tag-single">
                  <i class="bi bi-building"></i>
                  {{ areaSeleccionada.area_servicio_nombre }}
                </span>
              </div>

              <i class="bi" :class="areaDropdownAbierto ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
            </div>

            <div v-if="areaDropdownAbierto" class="categoria-select-dropdown">
              <div class="dropdown-search" v-if="areasDisponibles.length > 5">
                <i class="bi bi-search"></i>
                <input type="text" v-model="busquedaArea" placeholder="Buscar área..." @click.stop />
              </div>

              <div class="dropdown-options">
                <button type="button" v-for="area in areasFiltradas" :key="area.area_servicio_id"
                  class="dropdown-option" :class="{
                    selected: Number(areaSeleccionada?.area_servicio_id) === Number(area.area_servicio_id)
                  }" @click="seleccionarArea(Number(area.area_servicio_id))">
                  <span class="option-name">
                    {{ area.area_servicio_nombre }}
                  </span>

                  <span class="option-check">
                    <i v-if="Number(areaSeleccionada?.area_servicio_id) === Number(area.area_servicio_id)"
                      class="bi bi-check-lg"></i>
                  </span>
                </button>

                <div v-if="areasFiltradas.length === 0" class="dropdown-empty">
                  No se encontraron áreas
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Categoría -->
        <div class="filtro-grupo">
          <label class="filtro-label">Categoría</label>

          <div class="categoria-select-wrapper" :class="{ open: categoriaDropdownAbierto }">
            <div class="categoria-select-trigger" @click="categoriaDropdownAbierto = !categoriaDropdownAbierto">
              <div class="select-placeholder" v-if="!categoriaSeleccionada">
                <i class="bi bi-tags-fill"></i>
                <span>Todas las categorías</span>
              </div>

              <div class="select-selected" v-else>
                <span class="selected-tag-single">
                  <i class="bi" :class="categoriaSeleccionada.icono"></i>
                  {{ categoriaSeleccionada.nombre }}
                </span>
              </div>

              <i class="bi" :class="categoriaDropdownAbierto ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
            </div>

            <div v-if="categoriaDropdownAbierto" class="categoria-select-dropdown">
              <div class="dropdown-search" v-if="categoriasDisponibles.length > 5">
                <i class="bi bi-search"></i>
                <input type="text" v-model="busquedaCategoria" placeholder="Buscar categoría..." @click.stop />
              </div>

              <div class="dropdown-options">
                <button type="button" class="dropdown-option" :class="{ selected: !categoriaSeleccionada }"
                  @click="seleccionarCategoria('')">
                  <span class="option-name">
                    <i class="bi bi-tags-fill" style="margin-right: 8px;"></i>
                    Todas las categorías
                  </span>

                  <span class="option-check">
                    <i v-if="!categoriaSeleccionada" class="bi bi-check-lg"></i>
                  </span>
                </button>

                <button type="button" v-for="cat in categoriasFiltradasConIcono" :key="cat.id" class="dropdown-option"
                  :class="{ selected: categoriaSeleccionada?.id === cat.id }" @click="seleccionarCategoria(cat.id)">
                  <span class="option-name">
                    <i class="bi" :class="cat.icono" style="margin-right: 8px;"></i>
                    {{ cat.nombre }}
                  </span>

                  <span class="option-check">
                    <i v-if="categoriaSeleccionada?.id === cat.id" class="bi bi-check-lg"></i>
                  </span>
                </button>

                <div v-if="categoriasFiltradasConIcono.length === 0" class="dropdown-empty">
                  No se encontraron categorías
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Fechas -->
        <div class="filtro-grupo">
          <label for="fechaDesde" class="filtro-label">Fecha Desde</label>
          <input id="fechaDesde" v-model="filtros.fechaDesde" type="date" class="filtro-input" />
        </div>

        <div class="filtro-grupo">
          <label for="fechaHasta" class="filtro-label">Fecha Hasta</label>
          <input id="fechaHasta" v-model="filtros.fechaHasta" type="date" class="filtro-input" />
        </div>

        <!-- Limpiar -->
        <div class="filtro-acciones">
          <button class="btn-limpiar" @click="limpiarFiltros">
            <i class="bi bi-trash d-md-none"></i>
            <span class="d-none d-md-inline">Limpiar filtros</span>
          </button>
        </div>
      </div>

      <div v-if="actualizaciones.length === 0 && hayFiltrosActivos" class="estado-mensaje vacio">
        <div class="empty-icon">🔎</div>
        <h3>Sin resultados</h3>
        <p>No se encontraron actualizaciones con los filtros aplicados.</p>
      </div>

      <div v-else>
        <div class="row lista-feed g-3 g-md-4">
          <div v-for="item in actualizaciones" :key="item.id" class="col-12 col-sm-6 col-lg-4 col-xl-3">
            <div class="tarjeta-changelog h-100">
              <!-- Cabecera -->
              <div class="tarjeta-header">
                <div v-if="item.actualizacion_imagen_destacada" class="imagen-container">
                  <img :src="obtenerUrlImagen(item.actualizacion_imagen_destacada)" alt="Imagen destacada"
                    class="imagen-destacada" loading="lazy" />

                  <div class="imagen-overlay">
                    <span class="area-label">
                      {{ item.area_servicio?.area_servicio_nombre || 'Sin área' }}
                    </span>
                  </div>
                </div>

                <div v-else class="sin-imagen">
                  <span class="sin-imagen-icono">🖼️</span>
                  <p>Sin imagen destacada</p>

                  <div class="imagen-overlay">
                    <span class="area-label">
                      {{ item.area_servicio?.area_servicio_nombre || 'Sin área' }}
                    </span>
                  </div>
                </div>
              </div>

              <!-- Cuerpo -->
              <div class="tarjeta-cuerpo" @click="verDetalle(item.id)">
                <div class="metadatos-top">
                  <span class="fecha">
                    {{ formatearFecha(item.actualizacion_fecha_publicacion) }}
                  </span>

                  <span class="separador">|</span>

                  <span class="version-number">
                    v{{ item.actualizacion_version || '0.0' }}
                  </span>

                  <span class="views-badge" title="Visualizaciones">
                    <i class="bi bi-eye-fill"></i>
                    {{ formatearNumero(item.actualizacion_lecturas || 0) }}
                  </span>
                </div>

                <h2 class="titulo-version">
                  {{ item.actualizacion_titulo }}
                </h2>

                <p class="resumen">
                  {{ item.actualizacion_resumen }}
                </p>

                <!-- Categorías con ícono desde BD -->
                <div class="categorias-iconos">
                  <div v-for="cat in obtenerCategorias(item)" :key="cat.id" class="icono-categoria">
                    <i :class="['bi', 'ico-icon', cat.icono || 'bi-tag-fill']"></i>

                    <span class="ico-label">
                      {{ cat.nombre }}
                    </span>
                  </div>
                </div>
              </div>

              <!-- Footer -->
              <div class="tarjeta-pie">
                <button class="btn-icon" :disabled="bookmarkEnProceso(item.id)" @click.stop="toggleBookmark(item.id)"
                  :title="isBookmarked(item.id) ? 'Quitar de guardados' : 'Guardar'">
                  <i class="bi" :class="isBookmarked(item.id) ? 'bi-bookmark-check-fill' : 'bi-bookmark'"></i>
                </button>

                <button class="btn-enlace" @click.stop="verDetalle(item.id)">
                  Ver más
                  <i class="bi bi-arrow-right"></i>
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Paginación -->
        <div class="paginacion-container">
          <div class="info-paginacion">
            Mostrando <strong>{{ actualizaciones.length }}</strong> de
            <strong>{{ totalRegistros }}</strong> registros
          </div>

          <nav v-if="totalPaginas > 1" aria-label="Navegación">
            <ul class="pagination-moderno">
              <li :class="{ disabled: paginaActual === 1 }">
                <a href="#" @click.prevent="cambiarPagina(paginaActual - 1)" aria-label="Anterior">
                  <i class="bi bi-chevron-left"></i>
                </a>
              </li>

              <li v-for="pag in paginasMostradas" :key="pag" :class="{ active: paginaActual === pag }">
                <a href="#" @click.prevent="cambiarPagina(pag)">
                  {{ pag }}
                </a>
              </li>

              <li :class="{ disabled: paginaActual === totalPaginas }">
                <a href="#" @click.prevent="cambiarPagina(paginaActual + 1)" aria-label="Siguiente">
                  <i class="bi bi-chevron-right"></i>
                </a>
              </li>
            </ul>
          </nav>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { storeToRefs } from 'pinia'
import api from '../../api/api'
import { useAreasStore } from '../../stores/areas'
import { useCategoriasStore } from '../../stores/categorias'
import { obtenerIdsBookmarks, guardarBookmark, quitarBookmark } from '../../api/bookmarks'
import type { Version } from '../../types/version'
import { toast } from 'vue-sonner'
import PageHero from '../shared/PageHero.vue'

const router = useRouter()

const actualizaciones = ref<Version[]>([])
const cargando = ref(true)
const error = ref('')
const mostrarFiltros = ref(false)

const paginaActual = ref(1)
const totalPaginas = ref(1)
const totalRegistros = ref(0)

const bookmarkedItems = ref<Set<number>>(new Set())
const bookmarksProcesando = ref<Set<number>>(new Set())

const filtros = ref({
  busqueda: '',
  fechaDesde: '',
  fechaHasta: '',
  areaServicioId: '' as number | '',
  categoriaId: '' as number | '',
  orden: 'recientes'
})

const areasStore = useAreasStore()
const categoriasStore = useCategoriasStore()
const { areas: areasDisponibles } = storeToRefs(areasStore)
const { categorias: categoriasDisponibles } = storeToRefs(categoriasStore)

const opcionesOrden = [
  { valor: 'recientes', label: 'Más recientes' },
  { valor: 'antiguos', label: 'Más antiguos' },
  { valor: 'az', label: 'A → Z' },
  { valor: 'za', label: 'Z → A' },
]

const hayFiltrosActivos = computed(() =>
  Boolean(
    filtros.value.busqueda ||
    filtros.value.fechaDesde ||
    filtros.value.fechaHasta ||
    filtros.value.areaServicioId ||
    filtros.value.categoriaId ||
    filtros.value.orden !== 'recientes'
  )
)

const paginasMostradas = computed(() => {
  const maxPages = window.innerWidth < 768 ? 3 : 5
  const current = paginaActual.value
  const total = totalPaginas.value

  if (total <= maxPages) return Array.from({ length: total }, (_, i) => i + 1)

  let start = Math.max(1, current - Math.floor(maxPages / 2))
  let end = Math.min(total, start + maxPages - 1)

  if (end - start + 1 < maxPages) start = Math.max(1, end - maxPages + 1)

  return Array.from({ length: end - start + 1 }, (_, i) => start + i)
})

// ── API ───────────────────────────────────────────────────────────
const obtenerCatalogosFiltros = async () => {
  try {
    await Promise.all([
      areasStore.fetchAreas(),
      categoriasStore.fetchCategorias(),
    ])
  } catch (err) {
    console.error('Error al cargar catálogos:', err)
  }
}

const formatearNumero = (valor: number) => {
  return new Intl.NumberFormat('es-CO').format(valor || 0)
}

const obtenerActualizaciones = async (page = 1) => {
  cargando.value = true
  error.value = ''

  try {
    const params = new URLSearchParams()
    params.append('page', String(page))
    params.append('vista', 'blog')

    if (filtros.value.busqueda.trim()) params.append('busqueda', filtros.value.busqueda.trim())
    if (filtros.value.fechaDesde) params.append('fecha_desde', filtros.value.fechaDesde)
    if (filtros.value.fechaHasta) params.append('fecha_hasta', filtros.value.fechaHasta)
    if (filtros.value.areaServicioId !== '') params.append('area_servicio_id', String(filtros.value.areaServicioId))
    if (filtros.value.categoriaId !== '') params.append('actualizacion_categoria_id', String(filtros.value.categoriaId))
    if (filtros.value.orden) params.append('orden', filtros.value.orden)

    const respuesta = await api.get(`/actualizaciones?${params.toString()}`)
    actualizaciones.value = respuesta.data.data || []

    const meta = respuesta.data.meta || respuesta.data
    paginaActual.value = meta.current_page || 1
    totalPaginas.value = meta.last_page || 1
    totalRegistros.value = meta.total || actualizaciones.value.length

    if (window.innerWidth < 768 && hayFiltrosActivos.value) {
      mostrarFiltros.value = false
    }
  } catch (err) {
    console.error('Error al cargar la lista:', err)
    error.value = 'Error al conectar con el servidor.'
  } finally {
    cargando.value = false
  }
}

// ── Acciones ──────────────────────────────────────────────────────
const aplicarOrden = (valor: string) => { filtros.value.orden = valor }

const limpiarFiltros = () => {
  filtros.value = {
    busqueda: '',
    fechaDesde: '',
    fechaHasta: '',
    areaServicioId: '',
    categoriaId: '',
    orden: 'recientes'
  }

  if (filtroTimeout) {
    clearTimeout(filtroTimeout)
    filtroTimeout = null
  }

  obtenerActualizaciones(1)
}

const buscarPorTexto = () => {
  if (filtroTimeout) {
    clearTimeout(filtroTimeout)
    filtroTimeout = null
  }

  obtenerActualizaciones(1)
}

const limpiarBusqueda = () => {
  filtros.value.busqueda = ''

  if (filtroTimeout) {
    clearTimeout(filtroTimeout)
    filtroTimeout = null
  }

  obtenerActualizaciones(1)
}

const actualizarBookmarkProcesando = (id: number, enProceso: boolean) => {
  const siguiente = new Set(bookmarksProcesando.value)
  if (enProceso) siguiente.add(id)
  else siguiente.delete(id)
  bookmarksProcesando.value = siguiente
}

const bookmarkEnProceso = (id: number) => bookmarksProcesando.value.has(Number(id))

const toggleBookmark = async (id: number) => {
  const idNormalizado = Number(id)
  if (!Number.isFinite(idNormalizado) || bookmarkEnProceso(idNormalizado)) return

  const estabaGuardado = bookmarkedItems.value.has(idNormalizado)
  const estadoAnterior = new Set(bookmarkedItems.value)
  const siguienteEstado = new Set(bookmarkedItems.value)

  if (estabaGuardado) siguienteEstado.delete(idNormalizado)
  else siguienteEstado.add(idNormalizado)

  bookmarkedItems.value = siguienteEstado
  actualizarBookmarkProcesando(idNormalizado, true)

  try {
    if (estabaGuardado) {
      await quitarBookmark(idNormalizado)
      toast.success('¡Se quitó de tus guardados!')
    } else {
      await guardarBookmark(idNormalizado)
      toast.success('¡Registro añadido a guardados!')
    }
    window.dispatchEvent(new Event('bookmarks-updated'))
  } catch (err) {
    console.error('Error actualizando bookmark:', err)
    bookmarkedItems.value = estadoAnterior
    toast.error('No se pudo actualizar el guardado. Inténtalo nuevamente.')
  } finally {
    actualizarBookmarkProcesando(idNormalizado, false)
  }
}

const isBookmarked = (id: number) => bookmarkedItems.value.has(Number(id))

const loadBookmarks = async () => {
  try {
    const ids = await obtenerIdsBookmarks()
    bookmarkedItems.value = new Set(ids)
  } catch (err) {
    console.error('Error cargando bookmarks:', err)
    bookmarkedItems.value = new Set()
  }
}

let filtroTimeout: ReturnType<typeof setTimeout> | null = null

watch(
  () => [
    filtros.value.fechaDesde,
    filtros.value.fechaHasta,
    filtros.value.areaServicioId,
    filtros.value.categoriaId,
    filtros.value.orden,
  ],
  () => {
    if (filtroTimeout) clearTimeout(filtroTimeout)

    filtroTimeout = setTimeout(() => {
      obtenerActualizaciones(1)
    }, 250)
  }
)

const handleResize = () => {
  if (window.innerWidth >= 768) mostrarFiltros.value = false
}

// ── Helpers ───────────────────────────────────────────────────────
const verDetalle = (id: number) => {
  router.push({ name: 'actualizaciones-show', params: { id } })
}

const cambiarPagina = (pagina: number) => {
  if (pagina >= 1 && pagina <= totalPaginas.value && pagina !== paginaActual.value) {
    obtenerActualizaciones(pagina)
    window.scrollTo({ top: 0, behavior: 'smooth' })
  }
}

const obtenerUrlImagen = (ruta: string) => {
  if (!ruta) return ''
  if (ruta.startsWith('http')) return ruta
  const backendUrl = import.meta.env.VITE_BACKEND_URL || 'http://localhost:8000'
  if (ruta.startsWith('/storage/')) return `${backendUrl}${ruta}`
  if (ruta.startsWith('storage/')) return `${backendUrl}/${ruta}`
  return `${backendUrl}/storage/${ruta}`
}

const formatearFecha = (fechaString: string) => {
  if (!fechaString) return 'Sin fecha'
  const str = new Date(fechaString).toLocaleDateString('es-ES', {
    year: 'numeric', month: 'long', day: 'numeric'
  })
  return str.charAt(0).toUpperCase() + str.slice(1)
}

const categoriaDropdownAbierto = ref(false)

const categoriasConIcono = computed(() => {
  return categoriasDisponibles.value.map((categoria: any) => {
    const id = Number(categoria.categoria_actualizacion_id ?? categoria.id)
    const nombre =
      categoria.categoria_actualizacion_nombre ??
      categoria.nombre ??
      'Sin categoría'

    const icono =
      categoria.icono ||
      categoria.categoria_actualizacion_icono ||
      categoria.actualizacion_categoria_icono ||
      categoria.icono_categoria ||
      'bi-tag-fill'

    return {
      ...categoria,
      id,
      nombre,
      icono: obtenerIconoCategoria(icono),
    }
  })
})


const seleccionarCategoria = (id: number | '') => {
  filtros.value.categoriaId = id
  categoriaDropdownAbierto.value = false
}

/**
 * Normaliza las categorías de un item, soportando:
 * - item.categorias → array (relación hasMany)
 * - item.categoria  → objeto único (relación belongsTo)
 * - ninguno         → fallback "Sin categoría"
 */

const obtenerIconoCategoria = (icono?: string): string => {
  return icono && icono.trim() !== '' ? icono.trim() : 'bi-tag-fill'
}

const buscarCategoriaEnCatalogo = (id?: string | number, nombre?: string) => {
  const idNormalizado = Number(id)

  if (Number.isFinite(idNormalizado)) {
    const porId = categoriasConIcono.value.find(c => Number(c.id) === idNormalizado)
    if (porId) return porId
  }

  if (nombre) {
    const nombreNormalizado = nombre.toLowerCase().trim()

    const porNombre = categoriasConIcono.value.find(c =>
      c.nombre.toLowerCase().trim() === nombreNormalizado
    )

    if (porNombre) return porNombre
  }

  return null
}

const normalizarCategoriaItem = (c: any) => {
  const id =
    c?.categoria_actualizacion_id ??
    c?.actualizacion_categoria_id ??
    c?.id ??
    0

  const nombre =
    c?.categoria_actualizacion_nombre ??
    c?.actualizacion_categoria_nombre ??
    c?.nombre ??
    'Sin categoría'

  const categoriaCatalogo = buscarCategoriaEnCatalogo(id, nombre)

  const icono =
    c?.icono ||
    c?.categoria_actualizacion_icono ||
    c?.actualizacion_categoria_icono ||
    c?.icono_categoria ||
    categoriaCatalogo?.icono ||
    'bi-tag-fill'

  return {
    id,
    nombre: categoriaCatalogo?.nombre || nombre,
    icono: obtenerIconoCategoria(icono),
  }
}

const obtenerCategorias = (
  item: Version
): { id: string | number; nombre: string; icono: string }[] => {
  const itemAny = item as any

  if (Array.isArray(itemAny.categorias) && itemAny.categorias.length > 0) {
    return itemAny.categorias.map((c: any) => normalizarCategoriaItem(c))
  }

  if (Array.isArray(itemAny.actualizacion_categorias) && itemAny.actualizacion_categorias.length > 0) {
    return itemAny.actualizacion_categorias.map((c: any) => normalizarCategoriaItem(c))
  }

  if (itemAny.categoria) {
    return [normalizarCategoriaItem(itemAny.categoria)]
  }

  if (itemAny.actualizacion_categoria) {
    return [normalizarCategoriaItem(itemAny.actualizacion_categoria)]
  }

  const categoriaId =
    itemAny.actualizacion_categoria_id ??
    itemAny.categoria_actualizacion_id

  const categoriaNombre =
    itemAny.actualizacion_categoria_nombre ??
    itemAny.categoria_actualizacion_nombre

  if (categoriaId || categoriaNombre) {
    const categoriaCatalogo = buscarCategoriaEnCatalogo(categoriaId, categoriaNombre)

    return [{
      id: categoriaId ?? categoriaCatalogo?.id ?? 0,
      nombre: categoriaCatalogo?.nombre ?? categoriaNombre ?? 'Sin categoría',
      icono: categoriaCatalogo?.icono ?? 'bi-tag-fill',
    }]
  }

  return [{
    id: 0,
    nombre: 'Sin categoría',
    icono: 'bi-tag-fill',
  }]
}

// ── Montaje ───────────────────────────────────────────────────────
onMounted(async () => {
  window.addEventListener('resize', handleResize)
  await Promise.all([
    loadBookmarks(),
    obtenerCatalogosFiltros(),
    obtenerActualizaciones(1),
  ])
})

onUnmounted(() => {
  window.removeEventListener('resize', handleResize)
})

// ── Área ──
const areaDropdownAbierto = ref(false)
const busquedaArea = ref('')

const areaSeleccionada = computed(() => {
  if (!filtros.value.areaServicioId) return null
  return areasDisponibles.value.find(a => Number(a.area_servicio_id) === Number(filtros.value.areaServicioId)) || null
})

const areasFiltradas = computed(() => {
  if (!busquedaArea.value) return areasDisponibles.value
  const q = busquedaArea.value.toLowerCase()
  return areasDisponibles.value.filter(a =>
    a.area_servicio_nombre.toLowerCase().includes(q)
  )
})

const seleccionarArea = (id: number) => {
  filtros.value.areaServicioId = id
  areaDropdownAbierto.value = false
  busquedaArea.value = ''
}

// ── Categoría (reutilizamos el dropdown existente pero con nuevo trigger) ──
const busquedaCategoria = ref('')
// Nota: ya tienes 'categoriaDropdownAbierto' y 'seleccionarCategoria'
// Solo necesitas el computed para la selección actual y el filtrado con íconos

const categoriaSeleccionada = computed(() => {
  if (!filtros.value.categoriaId) return null
  return categoriasConIcono.value.find(c => c.id === Number(filtros.value.categoriaId)) || null
})

const categoriasFiltradasConIcono = computed(() => {
  if (!busquedaCategoria.value) return categoriasConIcono.value
  const q = busquedaCategoria.value.toLowerCase()
  return categoriasConIcono.value.filter(c =>
    c.nombre.toLowerCase().includes(q)
  )
})

</script>

<style scoped>
.contenedor-lista {
  max-width: 1400px;
  margin: 0 auto;
  padding: 0 16px;
}

@media (min-width: 768px) {
  .contenedor-lista {
    padding: 0 24px;
  }
}

/* ── Estados ──────────────────────────────────────────── */
.estado-mensaje {
  text-align: center;
  padding: 40px 20px;
  background: white;
  border-radius: 20px;
  color: #6b7280;
}

@media (min-width: 768px) {
  .estado-mensaje {
    padding: 60px 40px;
  }
}

.estado-mensaje.error {
  border-top: 3px solid #ef4444;
}

.estado-mensaje.vacio {
  border-top: 3px solid var(--warning);
}

.error-icon,
.empty-icon {
  font-size: 48px;
  margin-bottom: 16px;
}

.btn-retry {
  margin-top: 20px;
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

/* ── Layout general ───────────────────────────────────── */
.vista-con-filtros {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

/* ── Botón toggle filtros ─────────────────────────────── */
.filtros-toggle-wrapper {
  margin-bottom: 8px;
}

.btn-toggle-filtros {
  width: 100%;
  padding: 12px 16px;
  background: white;
  border: 1px solid #e1e7f0;
  border-radius: 12px;
  font-weight: 600;
  color: var(--primary);
  cursor: pointer;
  transition: var(--transition);
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  font-size: 0.95rem;
}

.btn-toggle-filtros:hover {
  background: #f8f9fa;
  border-color: var(--primary);
}

.filtros-activos-badge {
  color: var(--warning);
  font-size: 20px;
  line-height: 1;
}

/* ── Barra de filtros ─────────────────────────────────── */
.filtros-barra {
  display: none;
  grid-template-columns: 1fr;
  gap: 16px;
  background: white;
  border: 1px solid #e1e7f0;
  border-radius: 16px;
  padding: 18px;
  box-shadow: var(--shadow-sm);
}

.filtros-barra.filtros-visible {
  display: grid;
}

@media (min-width: 768px) {
  .filtros-barra {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (min-width: 992px) {
  .filtros-barra {
    grid-template-columns: repeat(3, 1fr);
  }
}

@media (min-width: 1200px) {
  .filtros-barra {
    grid-template-columns: repeat(6, minmax(160px, 1fr)) auto;
  }
}

.filtro-seccion-completa {
  grid-column: 1 / -1;
}

.filtro-label {
  display: block;
  font-size: 0.78rem;
  font-weight: 700;
  color: #4b5563;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  margin-bottom: 8px;
}

.filtro-grupo {
  display: flex;
  flex-direction: column;
}

.filtro-input {
  height: 42px;
  border: 1px solid #d7deea;
  border-radius: 12px;
  padding: 0 12px;
  font-size: 0.95rem;
  color: #374151;
  background: #fff;
  transition: var(--transition);
  width: 100%;
}

.filtro-input:focus {
  outline: none;
  border-color: var(--primary);
  box-shadow: 0 0 0 4px rgba(7, 126, 157, 0.12);
}

.filtro-input:disabled {
  background: #f9fafb;
  opacity: 0.7;
  cursor: not-allowed;
}

/* Chips */
.chips-grupo {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}

.chip {
  padding: 7px 16px;
  border-radius: 20px;
  font-size: 0.82rem;
  font-weight: 600;
  cursor: pointer;
  border: 1px solid #e1e7f0;
  background: white;
  color: #4b5563;
  transition: var(--transition);
}

.chip:active {
  transform: scale(0.96);
}

@media (min-width: 768px) {
  .chip:hover {
    border-color: var(--primary);
    color: var(--primary);
  }
}

.chip.activo {
  background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
  color: white;
  border-color: transparent;
}

/* Botón limpiar */
.filtro-acciones {
  display: flex;
  align-items: flex-end;
}

.btn-limpiar {
  height: 42px;
  padding: 0 18px;
  border-radius: 12px;
  border: 1px solid #e1e7f0;
  background: #f3f4f6;
  color: #374151;
  font-size: 0.9rem;
  font-weight: 600;
  cursor: pointer;
  white-space: nowrap;
  transition: var(--transition);
  display: flex;
  align-items: center;
  gap: 8px;
}

.btn-limpiar:active {
  transform: scale(0.96);
}

@media (min-width: 768px) {
  .btn-limpiar:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-sm);
  }
}

/* ── Tarjetas ─────────────────────────────────────────── */
.lista-feed {
  margin-top: 4px;
}

.tarjeta-changelog {
  border: 1px solid #eaeaea;
  border-radius: 16px;
  background-color: white;
  transition: var(--transition);
  overflow: visible;
  /* visible para que el chip no se corte */
  display: flex;
  flex-direction: column;
  cursor: pointer;
}

.tarjeta-changelog:active {
  transform: scale(0.98);
}

@media (min-width: 768px) {
  .tarjeta-changelog:hover {
    box-shadow: var(--shadow-md);
    transform: translateY(-4px);
  }
}

/* ── CABECERA ─────────────────────────────────────────── */
.tarjeta-header {
  position: relative;
  padding: 0;
  display: block;
  border-radius: 16px 16px 0 0;
  overflow: hidden;
}

.imagen-container {
  position: relative;
  overflow: hidden;
  width: 100%;
  background: #f5f5f5;
}

.imagen-destacada {
  width: 100%;
  aspect-ratio: 22/9;
  object-fit: cover;
  display: block;
  object-position: center;
  transition: var(--transition);
}

@media (min-width: 768px) {
  .imagen-container:hover .imagen-destacada {
    transform: scale(1.05);
  }
}

/*
  OVERLAY: degradado de transparente → negro
  origen en la esquina inferior DERECHA, con label de área
*/
.imagen-overlay {
  position: absolute;
  inset: 0;
  background: linear-gradient(to top left,
      rgba(0, 0, 0, 0.78) 0%,
      rgba(0, 0, 0, 0.15) 38%,
      transparent 62%);
  display: flex;
  align-items: flex-end;
  justify-content: flex-end;
  padding: 10px 12px;
  pointer-events: none;
}

.area-label {
  display: inline-block;
  background: rgba(255, 255, 255, 0.15);
  backdrop-filter: blur(6px);
  -webkit-backdrop-filter: blur(6px);
  color: #ffffff;
  font-size: 0.72rem;
  font-weight: 700;
  padding: 4px 12px;
  border-radius: 20px;
  letter-spacing: 0.03em;
  text-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
  border: 1px solid rgba(255, 255, 255, 0.2);
}

.sin-imagen {
  position: relative;
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
  border-bottom: 1px solid #e1e7f0;
  color: #9ca3af;
  width: 100%;
  aspect-ratio: 22/9;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  text-align: center;
}

.sin-imagen-icono {
  font-size: 32px;
  margin-bottom: 8px;
}

.sin-imagen p {
  margin: 0;
  font-size: 0.85rem;
}

/* ── CUERPO ──────────────────────────────────────────── */
.tarjeta-cuerpo {
  padding: 14px 16px;
  flex-grow: 1;
  display: flex;
  flex-direction: column;
}

.metadatos-top {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 10px;
  flex-wrap: wrap;
}

.separador {
  color: #a0aec0;
  font-weight: 300;
}

.fecha {
  font-size: 0.85rem;
  color: #888;
  font-weight: 500;
}

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
  font-size: 1.1rem;
  font-weight: 700;
  color: #1a1a1a;
  margin: 0 0 8px 0;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

@media (min-width: 768px) {
  .titulo-version {
    font-size: 1.2rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    -webkit-line-clamp: 1;
  }
}

.resumen {
  font-size: 0.9rem;
  color: #979797;
  line-height: 1.5;
  margin: 0 0 10px 0;
  display: -webkit-box;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 3;
  overflow: hidden;
  text-overflow: ellipsis;
}

@media (min-width: 768px) {
  .resumen {
    -webkit-line-clamp: 2;
  }
}

/* ── ICONOS DE CATEGORÍA → texto al hover ─────────────── */
.categorias-iconos {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
  margin-top: auto;
  padding-top: 8px;
}

.icono-categoria {
  /* Dimensiones base: cuadrado del tamaño del ícono */
  display: inline-flex;
  align-items: center;
  justify-content: center;
  height: 30px;
  min-width: 30px;
  max-width: 30px;
  border-radius: 8px;
  background: #f4f5f7;
  border: 1px solid transparent;
  overflow: hidden;
  cursor: default;
  /* Animar width/max-width y colores */
  transition:
    max-width 0.32s cubic-bezier(0.4, 0, 0.2, 1),
    background 0.22s ease,
    border-color 0.22s ease,
    padding 0.32s cubic-bezier(0.4, 0, 0.2, 1);
  padding: 0 7px;
}

/* Al hover: expande horizontalmente para mostrar el texto */
.icono-categoria:hover {
  max-width: 200px;
  background: rgba(7, 126, 157, 0.10);
  border-color: rgba(7, 126, 157, 0.25);
  padding: 0 10px;
}

/* El ícono: visible por defecto → se oculta en hover */
.icono-categoria .ico-icon {
  font-size: 16px;
  color: var(--secondary);
  flex-shrink: 0;
  /* ancho fijo → colapsa */
  width: 16px;
  opacity: 1;
  transition:
    opacity 0.15s ease,
    width 0.22s ease,
    margin 0.22s ease;
}

.icono-categoria:hover .ico-icon {
  opacity: 0;
  width: 0;
  margin: 0;
}

/* El texto: oculto por defecto → aparece en hover */
.icono-categoria .ico-label {
  font-size: 11px;
  font-weight: 700;
  color: var(--primary);
  white-space: nowrap;
  /* colapsa cuando no hay hover */
  max-width: 0;
  opacity: 0;
  overflow: hidden;
  transition:
    max-width 0.32s cubic-bezier(0.4, 0, 0.2, 1),
    opacity 0.18s ease 0.08s;
}

.icono-categoria:hover .ico-label {
  max-width: 180px;
  opacity: 1;
}

/* ── FOOTER ──────────────────────────────────────────── */
.tarjeta-pie {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 8px;
  padding: 10px 16px;
  border-top: 1px solid #f0f2f5;
  margin-top: auto;
}

.btn-icon {
  background: none;
  border: none;
  cursor: pointer;
  padding: 6px;
  border-radius: 8px;
  transition: var(--transition);
  display: flex;
  align-items: center;
  justify-content: center;
}

.btn-icon i {
  font-size: 1.25rem;
  color: var(--secondary);
}

.btn-icon:active {
  transform: scale(0.9);
}

.btn-icon:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

@media (min-width: 768px) {
  .btn-icon:hover:not(:disabled) {
    background: rgba(7, 126, 157, 0.1);
  }

  .btn-icon:hover:not(:disabled) i {
    color: var(--primary);
  }
}

.btn-enlace {
  background: none;
  border: none;
  color: #1a1a1a;
  font-weight: 600;
  font-size: 0.85rem;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 6px 10px;
  border-radius: 8px;
  transition: var(--transition);
}

.btn-enlace i {
  font-size: 0.9rem;
  transition: transform 0.3s ease;
}

.btn-enlace:active {
  transform: scale(0.95);
}

@media (min-width: 768px) {
  .btn-enlace:hover {
    color: var(--primary);
    background: rgba(7, 126, 157, 0.08);
  }

  .btn-enlace:hover i {
    transform: translateX(4px);
  }
}

/* ── Paginación ───────────────────────────────────────── */
.paginacion-container {
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  align-items: center;
  padding: 20px 0;
  border-top: 1px solid #eee;
  gap: 16px;
}

@media (min-width: 768px) {
  .paginacion-container {
    flex-direction: row;
  }
}

.info-paginacion {
  color: #6b7280;
  font-size: 0.85rem;
  text-align: center;
}

@media (min-width: 768px) {
  .info-paginacion {
    font-size: 0.88rem;
  }
}

.pagination-moderno {
  display: flex;
  gap: 4px;
  list-style: none;
  margin: 0;
  padding: 0;
  flex-wrap: wrap;
  justify-content: center;
}

@media (min-width: 768px) {
  .pagination-moderno {
    gap: 6px;
  }
}

.pagination-moderno li a {
  display: flex;
  align-items: center;
  justify-content: center;
  min-width: 34px;
  height: 34px;
  padding: 0 8px;
  background: white;
  color: var(--primary);
  text-decoration: none;
  border-radius: 8px;
  font-weight: 500;
  transition: var(--transition);
  border: 1px solid #e1e7f0;
  font-size: 0.9rem;
}

@media (min-width: 768px) {
  .pagination-moderno li a {
    min-width: 36px;
    height: 36px;
  }
}

.pagination-moderno li.active a {
  background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
  color: white;
  border-color: transparent;
  box-shadow: var(--shadow-sm);
}

@media (min-width: 768px) {
  .pagination-moderno li:not(.disabled):not(.active) a:hover {
    background: #f0f2f5;
    border-color: var(--primary);
    transform: translateY(-2px);
  }
}

.pagination-moderno li.disabled a {
  opacity: 0.5;
  cursor: not-allowed;
}

.pagination-moderno li a:active {
  transform: scale(0.95);
}

/* ── Inputs ───────────────────────────────────────────── */
.input-busqueda-wrapper {
  position: relative;
  display: flex;
  align-items: center;
}

.icono-busqueda {
  position: absolute;
  left: 12px;
  color: #9ca3af;
  font-size: 0.95rem;
  pointer-events: none;
}

.filtro-busqueda .filtro-input {
  padding-left: 34px;
  grid-column: span 2;
}

/* Animaciones */
@keyframes slideDown {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }

  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.filtros-barra.filtros-visible {
  animation: slideDown 0.3s ease;
}

.select-categoria-wrap {
  position: relative;
  display: flex;
  align-items: center;
}

.select-categoria-icon {
  position: absolute;
  left: 12px;
  z-index: 1;
  pointer-events: none;
  font-size: 1rem;
}

.filtro-input-con-icono {
  padding-left: 38px;
}

.categoria-dropdown {
  position: relative;
  width: 100%;
}

.categoria-dropdown-btn {
  width: 100%;
  min-height: 42px;
  border: 1px solid #d7dde8;
  border-radius: 10px;
  background: #fff;
  padding: 0 12px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  cursor: pointer;
}

.categoria-dropdown-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.categoria-dropdown-seleccion {
  display: flex;
  align-items: center;
  gap: 10px;
  min-width: 0;
}

.categoria-dropdown-seleccion span {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.categoria-dropdown-seleccion i {
  font-size: 1rem;
  flex-shrink: 0;
}

.categoria-dropdown-flecha {
  font-size: 0.85rem;
  transition: transform 0.2s ease;
}

.categoria-dropdown.abierto .categoria-dropdown-flecha {
  transform: rotate(180deg);
}

.categoria-dropdown-menu {
  position: absolute;
  top: calc(100% + 6px);
  left: 0;
  right: 0;
  z-index: 30;
  background: #fff;
  border: 1px solid #d7dde8;
  border-radius: 12px;
  box-shadow: 0 14px 35px rgba(15, 23, 42, 0.14);
  padding: 6px;
  max-height: 260px;
  overflow-y: auto;
}

.categoria-dropdown-item {
  width: 100%;
  border: 0;
  background: transparent;
  padding: 10px 12px;
  border-radius: 9px;
  display: flex;
  align-items: center;
  gap: 10px;
  text-align: left;
  cursor: pointer;
}

.categoria-dropdown-item:hover {
  background: #f3f6fb;
}

.categoria-dropdown-item.activo {
  background: #eaf1ff;
  font-weight: 600;
}

.categoria-dropdown-item i {
  font-size: 0.9rem;
  flex-shrink: 0;
}

/* ── Select de Área con apariencia igual al dropdown de Categoría ── */
.filtro-grupo select.filtro-input {
  appearance: none;
  -webkit-appearance: none;
  -moz-appearance: none;

  /* misma altura y forma que .categoria-dropdown-btn */
  min-height: 42px;
  height: auto;
  border: 1px solid #d7dde8;
  border-radius: 10px;
  background-color: #fff;
  padding: 0 36px 0 12px;

  /* flecha manual igual a bi-chevron-down */
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%236b7280' viewBox='0 0 16 16'%3E%3Cpath fill-rule='evenodd' d='M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 12px center;
  background-size: 14px;

  font-size: 0.95rem;
  color: #374151;
  cursor: pointer;
  width: 100%;
  transition: var(--transition);
}

.filtro-grupo select.filtro-input:focus {
  outline: none;
  border-color: var(--primary);
  box-shadow: 0 0 0 4px rgba(7, 126, 157, 0.12);
}

.filtro-grupo select.filtro-input:disabled {
  background-color: #f9fafb;
  opacity: 0.7;
  cursor: not-allowed;
}

/* Hover solo en desktop */
@media (min-width: 768px) {
  .filtro-grupo select.filtro-input:hover:not(:disabled) {
    border-color: var(--primary);
  }
}

/* ============================================================
   SELECTORES MODERNOS (Área y Categoría) – igual que en el modal
   ============================================================ */
.categoria-select-wrapper {
  position: relative;
  margin-bottom: 0;
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
}

.selected-tag-single {
  background: rgba(7, 126, 157, 0.1);
  color: var(--primary);
  padding: 2px 10px;
  border-radius: 12px;
  font-size: 0.8rem;
  font-weight: 500;
}

.categoria-select-trigger .bi-chevron-down,
.categoria-select-trigger .bi-chevron-up {
  color: #94a3b8;
  transition: transform 0.2s ease;
}

/* Dropdown (igual que en el modal) */
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

/* Contador (igual que en el modal) */
.categoria-counter {
  display: flex;
  align-items: center;
  justify-content: flex-start;
  margin-top: 8px;
  padding-top: 6px;
  border-top: 1px solid #f1f5f9;
}

.counter-text {
  font-size: 0.7rem;
  color: #64748b;
}

.views-badge {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 3px 9px;
  background: rgba(7, 126, 157, 0.08);
  color: var(--primary);
  border: 1px solid rgba(7, 126, 157, 0.16);
  border-radius: 999px;
  font-size: 0.75rem;
  font-weight: 700;
  line-height: 1;
}

.views-badge i {
  font-size: 0.85rem;
}

.input-busqueda-wrapper {
  position: relative;
  display: flex;
  align-items: center;
}

.filtro-busqueda .filtro-input {
  padding-left: 34px;
  padding-right: 78px;
}

.btn-limpiar-busqueda,
.btn-ejecutar-busqueda {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  width: 28px;
  height: 28px;
  border: none;
  border-radius: 8px;
  background: transparent;
  color: #64748b;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: var(--transition);
}

.btn-limpiar-busqueda {
  right: 42px;
}

.btn-ejecutar-busqueda {
  right: 8px;
  background: rgba(7, 126, 157, 0.08);
  color: var(--primary);
}

.btn-limpiar-busqueda:hover,
.btn-ejecutar-busqueda:hover {
  background: rgba(7, 126, 157, 0.14);
  color: var(--primary);
}
</style>