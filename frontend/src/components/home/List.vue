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
import '../../styles/list.css'

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
  router.push({
    name: 'actualizaciones-show',
    params: { id },
  })
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

/* Botón limpiar */
.filtro-acciones {
  display: flex;
  align-items: flex-end;
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

.filtros-barra.filtros-visible {
  animation: slideDown 0.3s ease;
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
</style>