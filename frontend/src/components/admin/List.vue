<template>
  <div class="contenedor-lista-tabla">
    <div v-if="cargando" class="estado-mensaje">
      <div class="spinner-border text-primary mb-3" role="status">
        <span class="visually-hidden">Cargando...</span>
      </div>
      <p>Cargando actualizaciones...</p>
    </div>

    <div v-else-if="error" class="estado-mensaje error">
      <div class="error-icon">⚠️</div>
      <p>{{ error }}</p>
      <button @click="obtenerActualizaciones()" class="btn-retry">
        Reintentar
      </button>
    </div>

    <div v-else-if="actualizaciones.length === 0 && !hayFiltrosActivos" class="estado-mensaje vacio">
      <div class="empty-icon">📭</div>
      <h3>No hay registros para mostrar</h3>
      <p>Aún no se ha registrado ninguna actualización en el sistema.</p>
    </div>

    <div v-else class="tabla-con-filtros">
      <!-- Filtros -->
      <div class="filtros-barra">
        <div class="filtro-grupo filtro-busqueda">
          <label for="busqueda" class="filtro-label">Buscar</label>
          <div class="input-busqueda-wrapper">
            <i class="bi bi-search icono-busqueda"></i>
            <input id="busqueda" v-model="filtros.busqueda" type="text" class="filtro-input"
              placeholder="Título o resumen..." />
          </div>
        </div>

        <div class="filtro-grupo">
          <label for="fechaDesde">Fecha desde</label>
          <input id="fechaDesde" v-model="filtros.fechaDesde" type="date" class="filtro-input" />
        </div>

        <div class="filtro-grupo">
          <label for="fechaHasta">Fecha hasta</label>
          <input id="fechaHasta" v-model="filtros.fechaHasta" type="date" class="filtro-input" />
        </div>

        <div class="filtro-grupo">
          <label for="estado">Estado</label>
          <select id="estado" v-model="filtros.estado" class="filtro-input" :disabled="cargandoFiltros">
            <option value="">Todos</option>
            <option v-for="estado in estadosDisponibles" :key="estado.id" :value="estado.id">
              {{ estado.nombre }}
            </option>
          </select>
        </div>

        <div class="filtro-grupo">
          <label for="area">Área</label>
          <select id="area" v-model="filtros.areaServicioId" class="filtro-input" :disabled="cargandoFiltros">
            <option value="">Todas</option>
            <option v-for="area in areasDisponibles" :key="area.area_servicio_id"
              :value="Number(area.area_servicio_id)">
              {{ area.area_servicio_nombre }}
            </option>
          </select>
        </div>

        <div class="filtro-grupo">
          <label for="area">Categoria</label>
          <select id="area" v-model="filtros.categoriaId" class="filtro-input" :disabled="cargandoFiltros">
            <option value="">Todas</option>
            <option v-for="categoria in categoriasDisponibles" :key="categoria.categoria_actualizacion_id"
              :value="Number(categoria.categoria_actualizacion_id)">
              {{ categoria.categoria_actualizacion_nombre }}
            </option>
          </select>
        </div>

        <div class="filtro-acciones">
          <button class="btn-limpiar" @click="limpiarFiltros">
            Limpiar
          </button>
        </div>
      </div>

      <div v-if="actualizaciones.length === 0 && hayFiltrosActivos" class="estado-mensaje vacio">
        <div class="empty-icon">🔎</div>
        <h3>No hay resultados</h3>
        <p>No se encontraron actualizaciones con los filtros aplicados.</p>
      </div>

      <div v-else class="table-container">
        <table class="base-table">
          <thead>
            <tr>
              <th class="col-titulo">TÍTULO</th>
              <th class="col-area">ÁREA</th>
              <th class="col-version">VERSIÓN</th>
              <th class="col-fecha">FECHA</th>
              <th class="col-estado">ESTADO</th>
              <th class="col-acciones">ACCIONES</th>
            </tr>
          </thead>

          <tbody>
            <tr v-for="item in actualizaciones" :key="item.id" class="fila-registro" @click="verDetalles(item.id)">
              <td class="cell-titulo" data-label="Título">
                <span class="titulo-texto">{{ item.actualizacion_titulo }}</span>
              </td>

              <td class="cell-area" data-label="Área">
                <span class="area-texto">{{ obtenerNombreArea(item) }}</span>
              </td>

              <td class="cell-version" data-label="Versión">
                <span class="version-badge">
                  v{{ item.actualizacion_version }}
                </span>
              </td>

              <td class="cell-fecha" data-label="Fecha">
                <div class="fecha-info">
                  <span v-if="item.actualizacion_estado === 'publicado'" class="fecha-publicado">
                    <span class="fecha-label">Publicado:</span>
                    {{ formatearFecha(item.actualizacion_fecha_publicacion) }}
                  </span>
                  <span v-else class="fecha-creado">
                    <span class="fecha-label">Creado:</span>
                    {{ formatearFecha(item.actualizacion_fecha_creacion) }}
                  </span>
                </div>
              </td>

              <td class="cell-estado" data-label="Estado">
                <span :class="['badge-estado', mapearClaseEstado(item.actualizacion_estado)]">
                  {{ item.actualizacion_estado }}
                </span>
              </td>

              <td class="cell-acciones" data-label="Acciones">
                <div class="icon-group">
                  <button title="Editar" class="btn-icon" data-bs-toggle="modal" data-bs-target="#modalEditarRegistro"
                    @click.stop="editarActualizacion(item.id)">
                    <i class="bi bi-pencil-square"></i>
                  </button>

                  <button title="Ver" class="btn-icon" @click.stop="verDetalles(item.id)">
                    <i class="bi bi-eye"></i>
                  </button>

                  <div v-if="item.actualizacion_estado !== 'inactivo'">
                    <button title="Archivar" class="btn-icon" @click.stop="confirmarEliminar(item)">
                      <i class="bi bi-x-lg"></i>
                    </button>
                  </div>

                  <div v-else>
                    <button title="Desarchivar" class="btn-icon" @click.stop="confirmarActivar(item)">
                      <i class="bi bi-check-lg"></i>
                    </button>
                  </div>
                </div>
              </td>
            </tr>
          </tbody>
        </table>

        <div class="table-footer">
          <div class="info-registros">
            Mostrando {{ actualizaciones.length }} registros en esta página
            <span class="total-registros">(Total: {{ totalRegistros }})</span>
          </div>

          <nav v-if="totalPaginas > 1" aria-label="Navegación de página">
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

    <!-- Modal de eliminación -->
    <div class="modal fade" id="modalEliminarRegistro" tabindex="-1" aria-labelledby="modalEliminarLabel"
      aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="modalEliminarLabel">
              ¿Deseas desactivar este registro?
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>

          <div class="modal-body">
            <p>Al desactivar, ya no aparecerá en las búsquedas activas ni se podrá leer en la página.</p>
            <strong class="modal-item-title">
              {{ itemAEliminar?.actualizacion_titulo }}
            </strong>
          </div>

          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
              Cancelar
            </button>

            <button type="button" class="btn btn-danger" @click="inactivarActualizacion">
              Aceptar
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal de activación -->
    <div class="modal fade" id="modalDesarchivarRegistro" tabindex="-1" aria-labelledby="modalDesarchivarLabel"
      aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="modalDesarchivarLabel">
              ¿Deseas activar este registro?
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>

          <div class="modal-body">
            <p>
              Al activar el registro, se actualizará el estado del registro a
              <strong>Publicado</strong>
            </p>

            <strong class="modal-item-title">
              {{ itemAEliminar?.actualizacion_titulo }}
            </strong>
          </div>

          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
              Cancelar
            </button>

            <button type="button" class="btn btn-primary" @click="activarActualizacion">
              Activar
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="modal fade" id="modalEditarRegistro" tabindex="-1" aria-labelledby="modalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title fw-bold" id="modalLabel">Editar Actualización</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" id="btnCerrarModal"
            @click="cerrarModalEdicion"></button>
        </div>

        <div class="modal-body">
          <Edit v-if="idEditando" :key="idEditando" :id="idEditando" @guardado="actualizacionGuardada"
            @cerrar="cerrarModalEdicion" />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed, nextTick, watch } from 'vue'
import { useRouter } from 'vue-router'
import api from '../../api/api'
import type { Version } from '../../types/version';
import Edit from '../../components/admin/EditVersion.vue'
import { Modal } from 'bootstrap'

type AreaFiltro = {
  area_servicio_id: number | string
  area_servicio_nombre: string
}

type CategoriaFiltro = {
  categoria_actualizacion_id: number | string
  categoria_actualizacion_nombre: string
}

type EstadoFiltro = {
  id: string
  nombre: string
}

const router = useRouter()

const ENDPOINT_AREAS = '/admin/area-servicio'
const ENDPOINT_STATUS = '/admin/estados-actualizacion'

const actualizaciones = ref<Version[]>([])
const cargando = ref(true)
const error = ref('')

const paginaActual = ref(1)
const totalPaginas = ref(1)
const totalRegistros = ref(0)

const itemAEliminar = ref<Version | null>(null)
const idEditando = ref<number | null>(null)

const filtros = ref<{
  busqueda: string
  fechaDesde: string
  fechaHasta: string
  estado: string
  areaServicioId: number | ''
  categoriaId: number | ''
}>({
  busqueda: '',
  fechaDesde: '',
  fechaHasta: '',
  estado: '',
  areaServicioId: '',
  categoriaId: '',
})

const areasDisponibles = ref<AreaFiltro[]>([])
const categoriasDisponibles = ref<CategoriaFiltro[]>([])
const estadosDisponibles = ref<EstadoFiltro[]>([])
const cargandoFiltros = ref(false)

const hayFiltrosActivos = computed(() => {
  return Boolean(
    filtros.value.busqueda ||
    filtros.value.fechaDesde ||
    filtros.value.fechaHasta ||
    filtros.value.estado ||
    filtros.value.areaServicioId
  )
})

const paginasMostradas = computed(() => {
  const maxPages = 5
  const current = paginaActual.value
  const total = totalPaginas.value

  if (total <= maxPages) {
    return Array.from({ length: total }, (_, i) => i + 1)
  }

  let start = Math.max(1, current - 2)
  let end = Math.min(total, start + maxPages - 1)

  if (end - start + 1 < maxPages) {
    start = Math.max(1, end - maxPages + 1)
  }

  return Array.from({ length: end - start + 1 }, (_, i) => start + i)
})

const cerrarModalBootstrap = () => {
  const botonCerrar = document.getElementById('btnCerrarModal')
  if (botonCerrar) botonCerrar.click()
}

const cerrarModalEdicion = () => {
  idEditando.value = null
  cerrarModalBootstrap()
}

const actualizacionGuardada = async () => {
  await obtenerActualizaciones(paginaActual.value)
  cerrarModalEdicion()
}

const obtenerCatalogosFiltros = async () => {
  cargandoFiltros.value = true

  try {
    const [areasResp, estadosResp] = await Promise.all([
      api.get(ENDPOINT_AREAS),
      api.get(ENDPOINT_STATUS)
    ])

    areasDisponibles.value = areasResp.data?.data || []
    estadosDisponibles.value = estadosResp.data?.data || []
  } catch (err) {
    console.error('Error al cargar catálogos de filtros:', err)
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

    if (filtros.value.busqueda.trim()) {
      params.append('busqueda', filtros.value.busqueda.trim())
    }

    if (filtros.value.fechaDesde) {
      params.append('fecha_desde', filtros.value.fechaDesde)
    }

    if (filtros.value.fechaHasta) {
      params.append('fecha_hasta', filtros.value.fechaHasta)
    }

    if (filtros.value.estado) {
      params.append('estado', filtros.value.estado)
    }

    if (filtros.value.areaServicioId !== '') {
      params.append('area_servicio_id', String(filtros.value.areaServicioId))
    }

    const respuesta = await api.get(`/admin/actualizaciones?${params.toString()}`)

    actualizaciones.value = respuesta.data.data
    paginaActual.value = respuesta.data.current_page
    totalPaginas.value = respuesta.data.last_page
    totalRegistros.value = respuesta.data.total
  } catch (err) {
    console.error('Error al cargar la lista:', err)
    error.value = 'No se pudo conectar con el servidor para obtener los datos.'
  } finally {
    cargando.value = false
  }
}

const limpiarFiltros = () => {
  filtros.value = {
    busqueda: '',
    fechaDesde: '',
    fechaHasta: '',
    estado: '',
    areaServicioId: '',
    categoriaId: ''
  }
}

const formatearFecha = (fechaString: string) => {
  if (!fechaString) return 'Sin fecha'

  const opciones: Intl.DateTimeFormatOptions = {
    year: 'numeric',
    month: 'short',
    day: '2-digit'
  }

  const fechaStr = new Date(fechaString).toLocaleDateString('es-ES', opciones)
  return fechaStr.charAt(0).toUpperCase() + fechaStr.slice(1)
}

const mapearClaseEstado = (estado: string) => {
  const estadoMin = (estado || '').toLowerCase()

  if (estadoMin === 'publicado') return 'estado-green'
  if (estadoMin === 'borrador') return 'estado-yellow'
  if (estadoMin === 'revision') return 'estado-red'
  if (estadoMin === 'inactivo') return 'estado-dark-gray'

  return 'estado-gray'
}

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

const obtenerNombreArea = (item: Version) => {
  return item.area_servicio?.area_servicio_nombre || 'Sin área'
}

const cambiarPagina = (pagina: number) => {
  if (pagina >= 1 && pagina <= totalPaginas.value && pagina !== paginaActual.value) {
    obtenerActualizaciones(pagina)
  }
}

const verDetalles = (id: number) => {
  router.push({
    name: 'admin-actualizaciones-show',
    params: { id }
  })
}

const editarActualizacion = (id: number) => {
  idEditando.value = id
}

const confirmarEliminar = async (item: Version) => {
  itemAEliminar.value = item

  await nextTick()

  const modalElement = document.getElementById('modalEliminarRegistro')
  if (modalElement) {
    const modalInstance = Modal.getOrCreateInstance(modalElement)
    modalInstance.show()
  }
}

const confirmarActivar = async (item: Version) => {
  itemAEliminar.value = item

  await nextTick()

  const modalElement = document.getElementById('modalDesarchivarRegistro')
  if (modalElement) {
    const modalInstance = Modal.getOrCreateInstance(modalElement)
    modalInstance.show()
  }
}

const limpiarFondoModal = () => {
  const backdrops = document.querySelectorAll('.modal-backdrop')
  backdrops.forEach(backdrop => backdrop.remove())
  document.body.classList.remove('modal-open')
  document.body.style.overflow = ''
  document.body.style.paddingRight = ''
}

const inactivarActualizacion = async () => {
  if (!itemAEliminar.value) return

  const modalElement = document.getElementById('modalEliminarRegistro')
  const modalInstance = modalElement ? Modal.getInstance(modalElement) || new Modal(modalElement) : null

  try {
    await api.post(`/admin/actualizaciones/${itemAEliminar.value.id}/inactivar`)

    itemAEliminar.value = null
    await obtenerActualizaciones(paginaActual.value)

    if (modalInstance) modalInstance.hide()
    limpiarFondoModal()
  } catch (err) {
    console.error('Error al inactivar:', err)
    if (modalInstance) modalInstance.hide()
    limpiarFondoModal()
  }
}

const activarActualizacion = async () => {
  if (!itemAEliminar.value) return

  const modalElement = document.getElementById('modalDesarchivarRegistro')
  const modalInstance = modalElement ? Modal.getInstance(modalElement) || new Modal(modalElement) : null

  try {
    await api.post(`/admin/actualizaciones/${itemAEliminar.value.id}/activar`)

    itemAEliminar.value = null
    await obtenerActualizaciones(paginaActual.value)

    if (modalInstance) modalInstance.hide()
    limpiarFondoModal()
  } catch (err) {
    console.error('Error al activar:', err)
    error.value = 'No se pudo activar la actualización.'
    if (modalInstance) modalInstance.hide()
    limpiarFondoModal()
  }
}

onMounted(async () => {
  await obtenerCatalogosFiltros()
  await obtenerActualizaciones(1)
})

defineExpose({
  obtenerActualizaciones
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
  --transition-smooth: all 0.4s cubic-bezier(0.34, 1.2, 0.64, 1);
}

.contenedor-lista-tabla {
  width: 100%;
  max-width: 1400px;
  margin: 0 auto;
}

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

.tabla-con-filtros {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.filtros-barra {
  display: grid;
  grid-template-columns: repeat(6, minmax(180px, 1fr)) auto;
  gap: 16px;
  align-items: end;
  background: white;
  border: 1px solid #e1e7f0;
  border-radius: 16px;
  padding: 18px;
  box-shadow: var(--shadow-sm);
}

.filtro-grupo {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.filtro-grupo label {
  font-size: 0.82rem;
  font-weight: 700;
  color: #4b5563;
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

.filtro-input {
  width: 100%;
  height: 42px;
  border: 1px solid #d7deea;
  border-radius: 12px;
  padding: 0 12px;
  font-size: 0.95rem;
  color: #374151;
  background: #fff;
  transition: var(--transition);
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

.filtro-acciones {
  display: flex;
  gap: 10px;
  align-items: end;
}

.btn-filtrar,
.btn-limpiar {
  height: 42px;
  padding: 0 16px;
  border-radius: 12px;
  border: none;
  font-weight: 600;
  cursor: pointer;
  transition: var(--transition);
  white-space: nowrap;
}

.btn-filtrar {
  background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
  color: white;
}

.btn-limpiar {
  background: #f3f4f6;
  color: #374151;
  border: 1px solid #e1e7f0;
}

.btn-filtrar:hover,
.btn-limpiar:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow-sm);
}

.table-container {
  background: white;
  border-radius: 16px;
  overflow: hidden;
  border: 1px solid #e1e7f0;
  box-shadow: var(--shadow-md);
  transition: var(--transition);
}

.table-container:hover {
  box-shadow: var(--shadow-lg);
}

.base-table {
  width: 100%;
  border-collapse: collapse;
}

.base-table thead tr {
  background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
}

.base-table th {
  padding: 15px 20px;
  color: white;
  font-weight: bold;
  font-size: 0.85rem;
  letter-spacing: 0.05em;
  text-transform: uppercase;
  border: none;
}

.base-table tbody tr {
  border-bottom: 1px solid #e1e7f0;
  transition: var(--transition-smooth);
}

.base-table tbody tr:hover {
  background-color: rgba(0, 0, 0, 0.04);
  transform: translateX(5px) scale(1.01);
  box-shadow: -4px 0 0 var(--primary);
  cursor: pointer;
}

.base-table tbody tr:hover td {
  color: #1a202c;
}

.base-table tbody tr:hover .titulo-texto {
  color: var(--primary);
}

.base-table tbody tr:hover .version-badge {
  background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
  color: white;
  border-color: transparent;
  transform: scale(1.05);
}

.base-table tbody tr:hover .badge-estado,
.base-table tbody tr:hover .btn-icon {
  transform: scale(1.05);
}

.base-table td {
  padding: 12px 20px;
  font-size: 0.95rem;
  color: #555c68;
  vertical-align: middle;
  transition: var(--transition-smooth);
}

.col-titulo {
  width: 28%;
}

.col-area {
  width: 16%;
}

.col-version {
  width: 12%;
}

.col-fecha {
  width: 18%;
}

.col-estado {
  width: 12%;
}

.col-acciones {
  width: 14%;
  text-align: right;
}

.titulo-texto {
  font-weight: 600;
  color: #1a202c;
  transition: var(--transition-smooth);
}

.area-texto {
  color: #4b5563;
  font-weight: 500;
}

.version-badge {
  display: inline-block;
  padding: 4px 10px;
  background-color: #f0f2f5;
  color: var(--primary);
  border-radius: 20px;
  font-size: 0.85rem;
  font-weight: 600;
  border: 1px solid #e1e7f0;
  transition: var(--transition-smooth);
}

.filtro-busqueda {
  grid-column: span 2;
}

@media (max-width: 1024px) {
  .filtro-busqueda {
    grid-column: span 2;
  }
}

@media (max-width: 640px) {
  .filtro-busqueda {
    grid-column: span 1;
  }
}

/* 
.filtro-input::placeholder {
  color: #9ca3af;
} */

.fecha-info {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.fecha-label {
  font-size: 0.7rem;
  font-weight: 600;
  text-transform: uppercase;
  color: #9ca3af;
  letter-spacing: 0.5px;
  transition: var(--transition-smooth);
}

.fecha-publicado,
.fecha-creado {
  font-size: 0.85rem;
  transition: var(--transition-smooth);
}

.fecha-publicado {
  color: var(--primary);
}

.fecha-creado {
  color: #9ca3af;
}

.badge-estado {
  display: inline-block;
  padding: 4px 10px;
  border-radius: 20px;
  font-size: 0.8rem;
  font-weight: 600;
  text-transform: capitalize;
  transition: var(--transition-smooth);
}

.estado-green {
  background-color: #e6f7e9;
  color: #2e7d32;
}

.estado-yellow {
  background-color: #fef8e3;
  color: #f9a825;
}

.estado-red {
  background-color: #fdeaea;
  color: #d32f2f;
}

.estado-dark-gray {
  background-color: #e5e7eb;
  color: #374151;
}

.estado-gray {
  background-color: #f2f4f7;
  color: #5f6671;
}

.icon-group {
  display: flex;
  gap: 15px;
  justify-content: flex-end;
}

.btn-icon {
  background: none;
  border: none;
  padding: 0;
  color: #9ca3af;
  font-size: 1.1rem;
  cursor: pointer;
  transition: var(--transition-smooth);
}

.btn-icon:hover {
  color: #4b5563;
}

.table-footer {
  padding: 16px 24px;
  background: white;
  border-top: 1px solid #e1e7f0;
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 16px;
}

.info-registros {
  font-size: 0.85rem;
  color: #6b7280;
}

.total-registros {
  color: var(--primary);
  font-weight: 500;
}

.pagination-moderno {
  display: flex;
  gap: 8px;
  list-style: none;
  margin: 0;
  padding: 0;
}

.pagination-moderno li {
  display: inline-block;
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
  transform: translateY(-2px);
  border-color: var(--primary);
}

.pagination-moderno li.disabled a {
  opacity: 0.5;
  cursor: not-allowed;
}

.modal-header {
  border-bottom: none;
  border-top: 3px solid var(--warning);
}

.modal-item-title {
  display: block;
  color: var(--primary);
  font-size: 1rem;
  margin: 8px 0;
  padding: 8px 12px;
  background: #f0f7fa;
  border-radius: 10px;
  text-align: center;
}

.modal-footer {
  padding: 16px 24px;
  border-top: 1px solid #e1e7f0;
  display: flex;
  justify-content: flex-end;
  gap: 12px;
}

@media (max-width: 1024px) {
  .filtros-barra {
    grid-template-columns: repeat(2, minmax(180px, 1fr));
  }

  .filtro-acciones {
    grid-column: 1 / -1;
  }
}

@media (max-width: 768px) {
  .base-table thead {
    display: none;
  }

  .base-table tbody tr {
    display: block;
    margin-bottom: 16px;
    border-radius: 12px;
    box-shadow: var(--shadow-sm);
    border: 1px solid #e1e7f0;
  }

  .base-table tbody tr:hover {
    transform: translateX(4px) scale(1.02);
  }

  .base-table td {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    border-bottom: 1px solid #f0f2f5;
    text-align: right;
  }

  .base-table td::before {
    content: attr(data-label);
    font-weight: 600;
    color: var(--primary);
    font-size: 0.8rem;
    text-align: left;
  }

  .table-footer {
    flex-direction: column;
    text-align: center;
  }

  .icon-group {
    justify-content: center;
  }
}

@media (max-width: 640px) {
  .filtros-barra {
    grid-template-columns: 1fr;
  }

  .filtro-acciones {
    flex-direction: column;
    align-items: stretch;
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
  pointer-events: none;
  /* para que el click pase al input */
}

.filtro-input {
  padding-left: 36px;
  /* espacio para el icono */
  /* tus estilos actuales del input... */
}
</style>