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

      <!-- Filtros -->
      <div class="filtros-barra">
        <!-- Chips de orden rápido -->
        <div class="filtro-seccion-completa">
          <label class="filtro-label">Ordenar por</label>
          <div class="chips-grupo">
            <span v-for="op in opcionesOrden" :key="op.valor" class="chip"
              :class="{ activo: filtros.orden === op.valor }" @click="aplicarOrden(op.valor)">{{ op.label }}</span>
          </div>
        </div>

        <!-- Búsqueda -->
        <div class="filtro-grupo filtro-busqueda">
          <label for="busqueda" class="filtro-label">Buscar</label>
          <div class="input-busqueda-wrapper">
            <i class="bi bi-search icono-busqueda"></i>
            <input id="busqueda" v-model="filtros.busqueda" type="text" class="filtro-input"
              placeholder="Título o resumen..." />
          </div>
        </div>

        <!-- Área -->
        <div class="filtro-grupo">
          <label for="area" class="filtro-label">Área / Servicio</label>
          <select id="area" v-model="filtros.areaServicioId" class="filtro-input" :disabled="cargandoFiltros">
            <option value="">Todas las áreas</option>
            <option v-for="area in areasDisponibles" :key="area.area_servicio_id"
              :value="Number(area.area_servicio_id)">{{ area.area_servicio_nombre }}</option>
          </select>
        </div>

        <!-- Fecha desde -->
        <div class="filtro-grupo">
          <label for="fechaDesde" class="filtro-label">Desde</label>
          <input id="fechaDesde" v-model="filtros.fechaDesde" type="date" class="filtro-input" />
        </div>

        <!-- Fecha hasta -->
        <div class="filtro-grupo">
          <label for="fechaHasta" class="filtro-label">Hasta</label>
          <input id="fechaHasta" v-model="filtros.fechaHasta" type="date" class="filtro-input" />
        </div>

        <!-- Limpiar -->
        <div class="filtro-acciones">
          <button class="btn-limpiar" @click="limpiarFiltros">Limpiar filtros</button>
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
        <div class="row lista-feed g-4">
          <div v-for="item in actualizaciones" :key="item.id" class="col-12 col-md-6 col-lg-4">
            <div class="tarjeta-changelog h-100">

              <div class="tarjeta-header">
                <div v-if="item.actualizacion_imagen_destacada" class="imagen-container">
                  <img :src="obtenerUrlImagen(item.actualizacion_imagen_destacada)" alt="Imagen destacada"
                    class="imagen-destacada" />
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
                </div>
                <button class="btn-enlace" @click="verDetalle(item.id)">Ver más ➔</button>
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
                <a href="#" @click.prevent="cambiarPagina(paginaActual - 1)">‹</a>
              </li>
              <li v-for="pag in paginasMostradas" :key="pag" :class="{ active: paginaActual === pag }">
                <a href="#" @click.prevent="cambiarPagina(pag)">{{ pag }}</a>
              </li>
              <li :class="{ disabled: paginaActual === totalPaginas }">
                <a href="#" @click.prevent="cambiarPagina(paginaActual + 1)">›</a>
              </li>
            </ul>
          </nav>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import api from '../../api/api'
import type { Version } from '../../types/version'

type AreaFiltro = {
  area_servicio_id: number | string
  area_servicio_nombre: string
}

const router = useRouter()

// ── Estado principal ──────────────────────────────────────────────
const actualizaciones = ref<Version[]>([])
const cargando = ref(true)
const error = ref('')

const paginaActual = ref(1)
const totalPaginas = ref(1)
const totalRegistros = ref(0)

// ── Filtros ───────────────────────────────────────────────────────
const filtros = ref({
  busqueda: '',
  fechaDesde: '',
  fechaHasta: '',
  areaServicioId: '' as number | '',
  orden: 'recientes'
})

const areasDisponibles = ref<AreaFiltro[]>([])
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
    filtros.value.orden !== 'recientes'
  )
)

const paginasMostradas = computed(() => {
  const maxPages = 5
  const current = paginaActual.value
  const total = totalPaginas.value
  if (total <= maxPages) return Array.from({ length: total }, (_, i) => i + 1)
  let start = Math.max(1, current - 2)
  let end = Math.min(total, start + maxPages - 1)
  if (end - start + 1 < maxPages) start = Math.max(1, end - maxPages + 1)
  return Array.from({ length: end - start + 1 }, (_, i) => start + i)
})

// ── API ───────────────────────────────────────────────────────────
const obtenerCatalogosFiltros = async () => {
  cargandoFiltros.value = true
  try {
    const resp = await api.get('/employee/area-servicio')
    areasDisponibles.value = resp.data?.data || []
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

    if (filtros.value.orden)
      params.append('orden', filtros.value.orden)

    const respuesta = await api.get(`/employee/actualizaciones?${params.toString()}`)

    actualizaciones.value = respuesta.data.data
    paginaActual.value = respuesta.data.current_page
    totalPaginas.value = respuesta.data.last_page
    totalRegistros.value = respuesta.data.total
  } catch (err) {
    console.error('Error al cargar la lista:', err)
    error.value = 'Error al conectar con el servidor.'
  } finally {
    cargando.value = false
  }
}

// ── Acciones de filtros ───────────────────────────────────────────
const aplicarOrden = (valor: string) => {
  filtros.value.orden = valor
}

const limpiarFiltros = () => {
  filtros.value = {
    busqueda: '',
    fechaDesde: '',
    fechaHasta: '',
    areaServicioId: '',
    orden: 'recientes'
  }
}

// Watch con debounce — igual que el admin
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
  await obtenerCatalogosFiltros()
  await obtenerActualizaciones(1)
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
}

.contenedor-lista {
  max-width: 1200px;
  margin: 0 auto;

  font-family: system-ui, -apple-system, sans-serif;
}

/* ── Estados ──────────────────────────────────────────── */
.estado-mensaje {
  text-align: center;
  padding: 60px 40px;
  background: white;
  border-radius: 20px;
  box-shadow: var(--shadow-sm);
  color: #6b7280;
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

/* ── Barra de filtros ─────────────────────────────────── */
.filtros-barra {
  display: grid;
  grid-template-columns: repeat(4, minmax(160px, 1fr)) auto;
  gap: 16px;
  align-items: end;
  background: white;
  border: 1px solid #e1e7f0;
  border-radius: 16px;
  padding: 18px;
  box-shadow: var(--shadow-sm);
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

.chip:hover {
  border-color: var(--primary);
  color: var(--primary);
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
}

.btn-limpiar:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow-sm);
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
}

.tarjeta-changelog:hover {
  box-shadow: var(--shadow-md);
  transform: translateY(-2px);
}

.tarjeta-header {
  padding: 0;
  display: block;
}

.imagen-container {
  overflow: hidden;
  width: 100%;
}

.imagen-destacada {
  width: 100%;
  aspect-ratio: 22/8;
  object-fit: cover;
  display: block;
  object-position: center;
  transition: var(--transition);
}

.imagen-container:hover .imagen-destacada {
  transform: scale(1.02);
}

.sin-imagen {
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
  border-bottom: 1px solid #e1e7f0;
  color: #9ca3af;
  width: 100%;
  aspect-ratio: 22/8;
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
  cursor: pointer;
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

.tags-container {
  display: flex;
  gap: 8px;
}

.tag-gris {
  background-color: #f4f5f7;
  color: #4a5568;
  font-size: 0.75rem;
  font-weight: 600;
  padding: 5px 12px;
  border-radius: 20px;
  transition: var(--transition);
}

.tag-gris:hover {
  background-color: var(--primary);
  color: white;
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

.btn-enlace:hover {
  color: var(--primary);
  background: rgba(7, 126, 157, 0.08);
}

/* ── Paginación ───────────────────────────────────────── */
.paginacion-container {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px 0;
  border-top: 1px solid #eee;
}

.info-paginacion {
  color: #6b7280;
  font-size: 0.88rem;
}

.pagination-moderno {
  display: flex;
  gap: 6px;
  list-style: none;
  margin: 0;
  padding: 0;
}

.pagination-moderno li a {
  display: flex;
  align-items: center;
  justify-content: center;
  min-width: 36px;
  height: 36px;
  padding: 0 8px;
  background: white;
  color: var(--primary);
  text-decoration: none;
  border-radius: 8px;
  font-weight: 500;
  transition: var(--transition);
  border: 1px solid #e1e7f0;
}

.pagination-moderno li.active a {
  background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
  color: white;
  border-color: transparent;
  box-shadow: var(--shadow-sm);
}

.pagination-moderno li:not(.disabled):not(.active) a:hover {
  background: #f0f2f5;
  border-color: var(--primary);
  transform: translateY(-2px);
}

.pagination-moderno li.disabled a {
  opacity: 0.5;
  cursor: not-allowed;
}

/* ── Responsive ───────────────────────────────────────── */
@media (max-width: 1024px) {
  .filtros-barra {
    grid-template-columns: repeat(2, 1fr);
  }

  .filtro-seccion-completa {
    grid-column: 1 / -1;
  }

  .filtro-acciones {
    grid-column: 1 / -1;
  }
}

@media (max-width: 640px) {
  .filtros-barra {
    grid-template-columns: 1fr;
  }

  .paginacion-container {
    flex-direction: column;
    gap: 16px;
    text-align: center;
  }

  .tarjeta-pie {
    flex-direction: column;
    gap: 12px;
    align-items: stretch;
  }

  .btn-enlace {
    justify-content: center;
  }
}

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
  pointer-events: none; /* para que el click pase al input */
}

.filtro-input {
  padding-left: 36px; /* espacio para el icono */
  /* tus estilos actuales del input... */
}
</style>