<template>
  <div class="contenedor-lista">
    <div class="cabecera mb-4">
      <h2>Actualizaciones</h2>
    </div>

    <div v-if="cargando && actualizaciones.length === 0" class="estado-mensaje">
      <div class="spinner-border text-primary mb-3" role="status">
        <span class="visually-hidden">Cargando...</span>
      </div>
      <p>Cargando actualizaciones...</p>
    </div>

    <div v-else-if="error" class="estado-mensaje error">
      <div class="error-icon">⚠️</div>
      <p>{{ error }}</p>
      <button @click="obtenerActualizaciones(1)" class="btn-retry">Reintentar</button>
    </div>

    <div v-else-if="actualizaciones.length === 0 && !hayFiltrosActivos" class="estado-mensaje vacio">
      <div class="empty-icon">📭</div>
      <h3>No hay actualizaciones para mostrar</h3>
      <p>Aún no se ha registrado ninguna publicación en el sistema.</p>
    </div>

    <div v-else class="vista-con-filtros">
      <!-- Botón toggle para filtros en móvil -->
      <div class="filtros-toggle-wrapper d-md-none">
        <button 
          class="btn-toggle-filtros" 
          @click="mostrarFiltros = !mostrarFiltros"
          type="button"
        >
          <i class="bi bi-funnel"></i>
          {{ mostrarFiltros ? 'Ocultar filtros' : 'Mostrar filtros' }}
          <span v-if="hayFiltrosActivos" class="filtros-activos-badge">•</span>
        </button>
      </div>

      <!-- Filtros -->
      <div :class="['filtros-barra', { 'filtros-visible': mostrarFiltros }]">
        <!-- Chips de orden rápido -->
        <div class="filtro-seccion-completa">
          <label class="filtro-label">Ordenar por</label>
          <div class="chips-grupo">
            <span 
              v-for="op in opcionesOrden" 
              :key="op.valor" 
              class="chip"
              :class="{ activo: filtros.orden === op.valor }" 
              @click="aplicarOrden(op.valor)"
            >
              {{ op.label }}
            </span>
          </div>
        </div>

        <!-- Búsqueda -->
        <div class="filtro-grupo filtro-busqueda">
          <label for="busqueda" class="filtro-label">Buscar</label>
          <div class="input-busqueda-wrapper">
            <i class="bi bi-search icono-busqueda"></i>
            <input 
              id="busqueda" 
              v-model="filtros.busqueda" 
              type="text" 
              class="filtro-input"
              placeholder="Título o resumen..." 
            />
          </div>
        </div>

        <!-- Área -->
        <div class="filtro-grupo">
          <label for="area" class="filtro-label">Área / Servicio</label>
          <select 
            id="area" 
            v-model="filtros.areaServicioId" 
            class="filtro-input" 
            :disabled="cargandoFiltros"
          >
            <option value="">Todas las áreas</option>
            <option 
              v-for="area in areasDisponibles" 
              :key="area.area_servicio_id"
              :value="Number(area.area_servicio_id)"
            >
              {{ area.area_servicio_nombre }}
            </option>
          </select>
        </div>

        <!-- Categoría -->
        <div class="filtro-grupo">
          <label for="categoria" class="filtro-label">Categoría</label>
          <select 
            id="categoria" 
            v-model="filtros.categoriaId" 
            class="filtro-input" 
            :disabled="cargandoFiltros"
          >
            <option value="">Todas las categorías</option>
            <option 
              v-for="categoria in categoriasDisponibles" 
              :key="categoria.categoria_actualizacion_id"
              :value="Number(categoria.categoria_actualizacion_id)"
            >
              {{ categoria.categoria_actualizacion_nombre }}
            </option>
          </select>
        </div>

        <!-- Fecha desde -->
        <div class="filtro-grupo">
          <label for="fechaDesde" class="filtro-label">Desde</label>
          <input 
            id="fechaDesde" 
            v-model="filtros.fechaDesde" 
            type="date" 
            class="filtro-input" 
          />
        </div>

        <!-- Fecha hasta -->
        <div class="filtro-grupo">
          <label for="fechaHasta" class="filtro-label">Hasta</label>
          <input 
            id="fechaHasta" 
            v-model="filtros.fechaHasta" 
            type="date" 
            class="filtro-input" 
          />
        </div>

        <!-- Limpiar -->
        <div class="filtro-acciones">
          <button class="btn-limpiar" @click="limpiarFiltros">
            <i class="bi bi-trash d-md-none"></i>
            <span class="d-none d-md-inline">Limpiar filtros</span>
          </button>
        </div>
      </div>

      <!-- Sin resultados con filtros activos -->
      <div v-if="actualizaciones.length === 0 && hayFiltrosActivos" class="estado-mensaje vacio">
        <div class="empty-icon">🔎</div>
        <h3>Sin resultados</h3>
        <p>No se encontraron actualizaciones con los filtros aplicados.</p>
      </div>

      <div v-else>
        <!-- Grid de tarjetas -->
        <div class="row lista-feed g-3 g-md-4">
          <div 
            v-for="item in actualizaciones" 
            :key="item.id" 
            class="col-12 col-sm-6 col-lg-4 col-xl-3"
          >
            <div class="tarjeta-changelog h-100">
              <div class="tarjeta-header">
                <div v-if="item.actualizacion_imagen_destacada" class="imagen-container">
                  <img 
                    :src="obtenerUrlImagen(item.actualizacion_imagen_destacada)" 
                    alt="Imagen destacada"
                    class="imagen-destacada" 
                    loading="lazy"
                  />
                </div>
                <div v-else class="sin-imagen">
                  <span>🖼️</span>
                  <p>Sin imagen destacada</p>
                </div>
              </div>

              <div class="tarjeta-cuerpo" @click="verDetalle(item.id)">
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
                  <span class="tag-gris">
                    {{ item.categoria?.categoria_actualizacion_nombre || 'Sin categoría' }}
                  </span>
                </div>
                <div class="tags-right">
                  <button class="btn-icon" @click.stop="toggleBookmark(item.id)" title="Guardar">
                    <i class="bi" :class="isBookmarked(item.id) ? 'bi-bookmark-fill' : 'bi-bookmark'"></i>
                  </button>

                  <button class="btn-enlace" @click="verDetalle(item.id)">
                    Ver más 
                    <i class="bi bi-arrow-right"></i>
                  </button>
                </div>
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
                <a href="#" @click.prevent="cambiarPagina(pag)">{{ pag }}</a>
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
import api from '../../api/api'
import type { Version } from '../../types/version'

type AreaFiltro = {
  area_servicio_id: number | string
  area_servicio_nombre: string
}

type CategoriaFiltro = {
  categoria_actualizacion_id: number | string
  categoria_actualizacion_nombre: string
}

const router = useRouter()

// ── Estado principal ──────────────────────────────────────────────
const actualizaciones = ref<Version[]>([])
const cargando = ref(true)
const error = ref('')
const mostrarFiltros = ref(false)

const paginaActual = ref(1)
const totalPaginas = ref(1)
const totalRegistros = ref(0)

// Bookmark state
const bookmarkedItems = ref<Set<number>>(new Set())

// ── Filtros ───────────────────────────────────────────────────────
const filtros = ref({
  busqueda: '',
  fechaDesde: '',
  fechaHasta: '',
  areaServicioId: '' as number | '',
  categoriaId: '' as number | '',
  orden: 'recientes'
})

const areasDisponibles = ref<AreaFiltro[]>([])
const categoriasDisponibles = ref<CategoriaFiltro[]>([])
const cargandoFiltros = ref(false)

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
  cargandoFiltros.value = true
  try {
    const [areasResp, categoriasResp] = await Promise.all([
      api.get('/employee/area-servicio'),
      api.get('/employee/categorias')
    ])

    areasDisponibles.value = areasResp.data?.data || []
    categoriasDisponibles.value = categoriasResp.data?.data || []
  } catch (err) {
    console.error('Error al cargar catálogos:', err)
  } finally {
    cargandoFiltros.value = false
  }
}

const obtenerActualizaciones = async (page = 1) => {
  cargando.value = true
  error.value = ''

  try {
    const params = new URLSearchParams()
    params.append('page', String(page))

    if (filtros.value.busqueda.trim())
      params.append('busqueda', filtros.value.busqueda.trim())

    if (filtros.value.fechaDesde)
      params.append('fecha_desde', filtros.value.fechaDesde)

    if (filtros.value.fechaHasta)
      params.append('fecha_hasta', filtros.value.fechaHasta)

    if (filtros.value.areaServicioId !== '')
      params.append('area_servicio_id', String(filtros.value.areaServicioId))

    if (filtros.value.categoriaId !== '')
      params.append('actualizacion_categoria_id', String(filtros.value.categoriaId))

    if (filtros.value.orden)
      params.append('orden', filtros.value.orden)

    const respuesta = await api.get(`/employee/actualizaciones?${params.toString()}`)

    actualizaciones.value = respuesta.data.data
    paginaActual.value = respuesta.data.current_page
    totalPaginas.value = respuesta.data.last_page
    totalRegistros.value = respuesta.data.total

    // En móvil, ocultar filtros después de buscar
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
const aplicarOrden = (valor: string) => {
  filtros.value.orden = valor
}

const limpiarFiltros = () => {
  filtros.value = {
    busqueda: '',
    fechaDesde: '',
    fechaHasta: '',
    areaServicioId: '',
    categoriaId: '',
    orden: 'recientes'
  }
}

const toggleBookmark = (id: number) => {
  if (bookmarkedItems.value.has(id)) {
    bookmarkedItems.value.delete(id)
  } else {
    bookmarkedItems.value.add(id)
  }
  // Aquí puedes guardar en localStorage o hacer una llamada API
  localStorage.setItem('bookmarkedUpdates', JSON.stringify([...bookmarkedItems.value]))
}

const isBookmarked = (id: number) => {
  return bookmarkedItems.value.has(id)
}

// Cargar bookmarks guardados
const loadBookmarks = () => {
  const saved = localStorage.getItem('bookmarkedUpdates')
  if (saved) {
    const bookmarks = JSON.parse(saved)
    bookmarkedItems.value = new Set(bookmarks)
  }
}

// Watch con debounce
let filtroTimeout: ReturnType<typeof setTimeout> | null = null

watch(
  filtros,
  () => {
    if (filtroTimeout) clearTimeout(filtroTimeout)
    filtroTimeout = setTimeout(() => {
      obtenerActualizaciones(1)
    }, 400)
  },
  { deep: true }
)

// Cerrar filtros al cambiar tamaño de pantalla
const handleResize = () => {
  if (window.innerWidth >= 768) {
    mostrarFiltros.value = false
  }
}

// ── Helpers ───────────────────────────────────────────────────────
const verDetalle = (id: number) => {
  router.push({ name: 'employee-actualizaciones-show', params: { id } })
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
  const baseUrl = import.meta.env.VITE_API_URL || 'http://localhost:8000'
  return `${baseUrl}/storage/${ruta}`
}

const formatearFecha = (fechaString: string) => {
  if (!fechaString) return 'Sin fecha'
  const str = new Date(fechaString).toLocaleDateString('es-ES', {
    year: 'numeric', month: 'long', day: 'numeric'
  })
  return str.charAt(0).toUpperCase() + str.slice(1)
}

// ── Montaje ───────────────────────────────────────────────────────
onMounted(async () => {
  loadBookmarks()
  await obtenerCatalogosFiltros()
  await obtenerActualizaciones(1)
  window.addEventListener('resize', handleResize)
})

onUnmounted(() => {
  window.removeEventListener('resize', handleResize)
})
</script>

<style scoped>
:root {
  --primary: #077E9D;
  --secondary: #025B7D;
  --warning: #FCBB1C;
  --shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.08);
  --shadow-md: 0 4px 16px rgba(0, 0, 0, 0.12);
  --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  --transition-smooth: all 0.4s cubic-bezier(0.34, 1.2, 0.64, 1);
}

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
  box-shadow: var(--shadow-sm);
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

/* Chips de orden */
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
  overflow: hidden;
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

.tarjeta-header {
  padding: 0;
  display: block;
}

.imagen-container {
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

.sin-imagen {
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

.sin-imagen span {
  font-size: 32px;
  margin-bottom: 8px;
}

.sin-imagen p {
  margin: 0;
  font-size: 0.85rem;
}

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
  color: #555;
  line-height: 1.5;
  margin: 0;
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

.tarjeta-pie {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 10px 16px;
  border-top: 1px solid #f0f2f5;
  margin-top: auto;
}

.tags-container {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}

.tag-gris {
  background-color: #f4f5f7;
  color: #4a5568;
  font-size: 0.7rem;
  font-weight: 600;
  padding: 4px 10px;
  border-radius: 20px;
  transition: var(--transition);
  white-space: nowrap;
}

@media (min-width: 768px) {
  .tag-gris {
    font-size: 0.75rem;
    padding: 5px 12px;
  }
  
  .tag-gris:hover {
    background-color: var(--primary);
    color: white;
  }
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
  font-size: 1.2rem;
  color: var(--secondary);
}

.btn-icon:active {
  transform: scale(0.9);
}

@media (min-width: 768px) {
  .btn-icon:hover {
    background: rgba(7, 126, 157, 0.1);
  }
  
  .btn-icon:hover i {
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

.tags-right {
  gap: 8px;
  display: flex;
  align-items: center;
}

@media (max-width: 480px) {
  .tarjeta-pie {
    flex-direction: column;
    align-items: stretch;
  }
  
  .tags-container {
    justify-content: center;
  }
  
  .tags-right {
    justify-content: center;
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

.filtro-input {
  padding-left: 34px;
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

/* Cabecera */
.cabecera h2 {
  font-size: 1.5rem;
  margin: 0;
}

@media (min-width: 768px) {
  .cabecera h2 {
    font-size: 1.75rem;
  }
}
</style>