<template>
  <div :class="['contenedor-lista-tabla', { 'modo-supervision': esVistaSupervision }]">
    <!-- Vista móvil (menor a 420px) -->
    <div v-if="esMovil" class="vista-movil">
      <!-- Filtros compactos para móvil -->
      <div class="filtros-movil">
        <button class="btn-filtros-movil" @click="mostrarFiltrosMovil = !mostrarFiltrosMovil">
          <i class="bi bi-funnel"></i>
          <span>Filtros</span>
          <span v-if="hayFiltrosActivos" class="filtros-activos-badge">•</span>
        </button>

        <div v-if="mostrarFiltrosMovil" class="filtros-panel-movil">
          <div class="filtro-movil-item">
            <input v-model="filtros.busqueda" type="text" placeholder="Buscar..." class="input-movil" />
          </div>

          <div class="filtro-movil-item">
            <select v-model="filtros.areaServicioId" class="select-movil">
              <option value="">Todas las áreas</option>
              <option v-for="area in areasDisponibles" :key="area.area_servicio_id"
                :value="Number(area.area_servicio_id)">
                {{ area.area_servicio_nombre }}
              </option>
            </select>
          </div>

          <div class="filtro-movil-item">
            <select v-model="filtros.categoriaId" class="select-movil">
              <option value="">Todas las categorías</option>
              <option v-for="categoria in categoriasDisponibles" :key="categoria.categoria_actualizacion_id"
                :value="Number(categoria.categoria_actualizacion_id)">
                {{ categoria.categoria_actualizacion_nombre }}
              </option>
            </select>
          </div>

          <div class="filtro-movil-item">
            <select v-model="filtros.estado" class="select-movil">
              <option value="">Todos los estados</option>
              <option v-for="estado in estadosDisponibles" :key="estado.id" :value="estado.id">
                {{ estado.nombre }}
              </option>
            </select>
          </div>

          <div class="filtro-acciones-movil">
            <button class="btn-limpiar-movil" @click="limpiarFiltros">Limpiar</button>
          </div>
        </div>
      </div>

      <!-- Estado de carga -->
      <div v-if="cargando" class="estado-movil">
        <div class="spinner-border text-primary" role="status">
          <span class="visually-hidden">Cargando...</span>
        </div>
        <p>Cargando actualizaciones...</p>
      </div>

      <!-- Error -->
      <div v-else-if="error" class="estado-movil error">
        <i class="bi bi-exclamation-triangle-fill"></i>
        <p>{{ error }}</p>
        <button @click="obtenerActualizaciones()" class="btn-retry-movil">Reintentar</button>
      </div>

      <!-- Lista de tarjetas móvil -->
      <div v-else class="tarjetas-movil">
        <TarjetaMovilRegistro v-for="item in actualizaciones" :key="item.id" :item="item"
          :es-vista-supervision="esVistaSupervision" @ver="verDetalles" @editar="editarActualizacion"
          @archivar="confirmarEliminar" @activar="confirmarActivar" @revisar="confirmarRevision" />

        <div v-if="actualizaciones.length === 0 && !cargando" class="vacio-movil">
          <i class="bi bi-inbox"></i>
          <p v-if="hayFiltrosActivos">No hay resultados con los filtros aplicados</p>
          <p v-else>{{ esVistaSupervision ? 'No hay registros pendientes' : 'No hay registros para mostrar' }}</p>
        </div>

        <!-- Paginación móvil -->
        <div v-if="totalPaginas > 1" class="paginacion-movil">
          <button :disabled="paginaActual === 1" @click="cambiarPagina(paginaActual - 1)" class="pag-movil">
            <i class="bi bi-chevron-left"></i>
          </button>
          <span class="pag-info">{{ paginaActual }} / {{ totalPaginas }}</span>
          <button :disabled="paginaActual === totalPaginas" @click="cambiarPagina(paginaActual + 1)" class="pag-movil">
            <i class="bi bi-chevron-right"></i>
          </button>
        </div>
      </div>
    </div>

    <!-- Vista desktop (mayor o igual a 420px) -->
    <div v-else class="vista-desktop">
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
        <h3>{{ tituloVacio }}</h3>
        <p>{{ textoVacio }}</p>
      </div>

      <div v-else class="tabla-con-filtros">
        <!-- Filtros Desktop -->
        <div class="filtros-barra">
          <button class="btn-toggle-filtros d-md-none" @click="mostrarFiltros = !mostrarFiltros" type="button">
            <i class="bi bi-funnel"></i>
            {{ mostrarFiltros ? 'Ocultar filtros' : 'Mostrar filtros' }}
          </button>

          <div :class="['filtros-contenido', { 'filtros-visible': mostrarFiltros }]">
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
              <label class="filtro-label">Área / Servicio</label>
              <SelectorBusqueda v-model="filtros.areaServicioId" :opciones="areasConOpcion"
                placeholder="Selecciona un área..." icono-placeholder="bi-building" texto-busqueda="Buscar área..."
                texto-vacio="No se encontraron áreas" />
            </div>

            <div class="filtro-grupo">
              <label class="filtro-label">Categoría</label>
              <SelectorBusqueda v-model="filtros.categoriaId" :opciones="categoriasConIcono"
                placeholder="Todas las categorías" icono-placeholder="bi-tags-fill" texto-busqueda="Buscar categoría..."
                texto-vacio="No se encontraron categorías" mostrar-opcion-todas
                texto-opcion-todas="Todas las categorías" />
            </div>
            <div class="filtro-acciones">
              <button class="btn-limpiar" @click="limpiarFiltros">
                Limpiar
              </button>
            </div>
          </div>
        </div>

        <div v-if="actualizaciones.length === 0 && hayFiltrosActivos" class="estado-mensaje vacio">
          <div class="empty-icon">🔎</div>
          <h3>No hay resultados</h3>
          <p>No se encontraron actualizaciones con los filtros aplicados.</p>
        </div>

        <div class="table-container">
          <table class="base-table">
            <thead>
              <tr>
                <th class="col-titulo">TÍTULO</th>
                <th v-if="esVistaSupervision" class="col-autor">EMPLEADO</th>
                <th class="col-area">ÁREA</th>
                <th class="col-categoria">CATEGORÍA</th>
                <th class="col-version">VERSIÓN</th>
                <th class="col-fecha">FECHA</th>
                <th class="col-estado">ESTADO</th>
                <th class="col-acciones">ACCIONES</th>
              </tr>
            </thead>

            <tbody>
              <tr v-for="item in actualizaciones" :key="item.id" class="fila-registro" @click="verDetalles(item.id)">
                <td class="cell-titulo" data-label="Título">
                  <span class="titulo-texto titulo-tabla">{{ item.actualizacion_titulo }}</span>
                </td>

                <td v-if="esVistaSupervision" class="cell-autor" data-label="Empleado">
                  <span class="autor-texto">{{ obtenerNombreAutor(item) }}</span>
                </td>

                <td class="cell-area" data-label="Área">
                  <span class="area-texto">{{ obtenerNombreArea(item) }}</span>
                </td>

                <td class="cell-categoria" data-label="Categoría">
                  <div class="categorias-container">
                    <span v-for="(cat, idx) in obtenerListaCategorias(item)" :key="idx" class="categoria-badge" :style="{
                      backgroundColor: getCategoriaColor(cat) + '15',
                      borderColor: getCategoriaColor(cat),
                      color: getCategoriaColor(cat)
                    }">
                      <i :class="getCategoriaIcon(cat)" :style="{ color: getCategoriaColor(cat) }"></i>
                      {{ cat }}
                    </span>
                    <span v-if="obtenerListaCategorias(item).length === 0" class="categoria-vacia">
                      Sin categoría
                    </span>
                  </div>
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
                    <button title="Ver" class="btn-icon" @click.stop="verDetalles(item.id)">
                      <i class="bi bi-eye"></i>
                    </button>

                    <template v-if="esVistaSupervision">
                      <button v-if="item.actualizacion_estado !== 'revision'" title="Marcar como revisión"
                        class="btn-icon btn-icon-revision" @click.stop="confirmarRevision(item)">
                        <i class="bi bi-clipboard-check"></i>
                      </button>
                    </template>

                    <template v-else>
                      <button :title="item.actualizacion_estado === 'revision' ? 'Enviar corrección' : 'Editar'"
                        class="btn-icon" data-bs-toggle="modal" data-bs-target="#modalEditarRegistro"
                        @click.stop="editarActualizacion(item)">
                        <i :class="item.actualizacion_estado === 'revision'
                          ? 'bi bi-send-check'
                          : 'bi bi-pencil-square'"></i>
                      </button>

                      <button title="Duplicar" class="btn-icon" :disabled="duplicandoId === item.id"
                        @click.stop="duplicarActualizacion(item)">
                        <span v-if="duplicandoId === item.id" class="spinner-border spinner-border-sm"></span>
                        <i v-else class="bi bi-copy"></i>
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
                    </template>
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
    </div>
  </div>

  <!-- Modales -->
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
          <strong class="modal-item-title">{{ itemAEliminar?.actualizacion_titulo }}</strong>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="button" class="btn btn-danger" @click="inactivarActualizacion">Aceptar</button>
        </div>
      </div>
    </div>
  </div>

  <div class="modal fade" id="modalDesarchivarRegistro" tabindex="-1" aria-labelledby="modalDesarchivarLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="modalDesarchivarLabel">¿Deseas activar este registro?</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <p>Al activar el registro, se actualizará el estado del registro a <strong>Publicado</strong></p>
          <strong class="modal-item-title">{{ itemAEliminar?.actualizacion_titulo }}</strong>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="button" class="btn btn-primary" @click="activarActualizacion">Activar</button>
        </div>
      </div>
    </div>
  </div>

  <div class="modal fade" id="modalRevisionRegistro" tabindex="-1" aria-labelledby="modalRevisionLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="modalRevisionLabel">¿Deseas marcar este registro como revisión?</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <p>El registro quedará marcado con el estado <strong>Revisión</strong>.</p>
          <strong class="modal-item-title">{{ itemARevision?.actualizacion_titulo }}</strong>
          <div class="revision-observacion-group">
            <label for="observacionRevision" class="form-label">
              Motivo de revisión <span class="campo-obligatorio">*</span>
            </label>
            <textarea id="observacionRevision" v-model.trim="observacionRevision" class="form-control" rows="4"
              maxlength="2000"
              placeholder="Explica qué debe revisar o corregir el empleado antes de continuar."></textarea>
            <small class="form-text text-muted">Este mensaje quedará guardado como soporte de la revisión.</small>
            <p v-if="errorObservacionRevision" class="revision-observacion-error">
              {{ errorObservacionRevision }}
            </p>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="button" class="btn btn-warning" @click="marcarRevisionActualizacion">Marcar revisión</button>
        </div>
      </div>
    </div>
  </div>

  <div class="modal fade" id="modalEditarRegistro" tabindex="-1" aria-labelledby="modalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title fw-bold" id="modalLabel">Editar Actualización</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" id="btnCerrarModal"
            @click="cerrarModalEdicion"></button>
        </div>
        <div class="modal-body">
          <Edit v-if="idEditando" :key="`${idEditando}-${observacionCorreccionActual?.id || 'normal'}`" :id="idEditando"
            :modo-correccion="Boolean(observacionCorreccionActual) || esCorreccionDesdeListado"
            @guardado="actualizacionGuardada" @cerrar="cerrarModalEdicion" />
        </div>
      </div>
    </div>
  </div>

  <PanelRevisionesPendientes :mostrar="mostrarAlertaRevision" v-model:abierto="offcanvasAbierto"
    :observaciones="observacionesRevision" @corregir="handleCorreccionDesdeOffcanvas" />
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted, computed, nextTick, watch } from 'vue'
import { useRouter } from 'vue-router'
import { storeToRefs } from 'pinia'
import api from '../../api/api'
import type { Version } from '../../types/version';
import Edit from './EditVersion.vue'
import SelectorBusqueda from '../shared/SelectorBusqueda.vue'
import PanelRevisionesPendientes from './PanelRevisionesPendientes.vue'
import { normalizarCategoriaIds } from '../../composables/useCategoriaSelector'
import TarjetaMovilRegistro from './TarjetaMovilRegistro.vue'
import { Modal } from 'bootstrap'
import { useAreasStore } from '../../stores/areas'
import { useCategoriasStore } from '../../stores/categorias'
import '../../styles/listregister.css'
import {
  formatearFecha,
  mapearClaseEstado,
  obtenerNombreArea,
  obtenerListaCategorias,
  getCategoriaColor,
  getCategoriaIcon,
  obtenerNombreAutor,
} from '../../utils/formatoRegistro'
import {
  listarObservacionesPendientes,
  type ObservacionPendiente,
} from '../../api/observaciones'

type EstadoFiltro = {
  id: string
  nombre: string
}

const emit = defineEmits<{
  (e: 'duplicar', payload: {
    titulo: string
    resumen: string
    area_servicio_id: number | string
    actualizacion_categoria_ids: number[]
    contenidoBlocks: any[]
  }): void
}>()
const router = useRouter()

const props = withDefaults(defineProps<{
  vista?: 'mis-registros' | 'supervision'
}>(), {
  vista: 'mis-registros',
})

// Detectar si es móvil (menor a 420px)
const esMovil = ref(window.innerWidth < 420)
const mostrarFiltrosMovil = ref(false)

// Escuchar cambios de tamaño
const handleResize = () => {
  esMovil.value = window.innerWidth < 420
}

const esVistaSupervision = computed(() => props.vista === 'supervision')

const tituloVacio = computed(() =>
  esVistaSupervision.value ? 'No hay registros pendientes en tu área' : 'No hay registros para mostrar'
)

const textoVacio = computed(() =>
  esVistaSupervision.value
    ? 'Cuando los empleados de tu área creen registros, aparecerán en esta bandeja de supervisión.'
    : 'Aún no se ha registrado ninguna actualización en el sistema.'
)

const ENDPOINT_STATUS = '/estados-actualizacion'

const actualizaciones = ref<Version[]>([])
const cargando = ref(true)
const error = ref('')

const paginaActual = ref(1)
const totalPaginas = ref(1)
const totalRegistros = ref(0)

const itemAEliminar = ref<Version | null>(null)
const itemARevision = ref<Version | null>(null)
const observacionRevision = ref('')
const errorObservacionRevision = ref('')
const idEditando = ref<number | null>(null)
const observacionCorreccionActual = ref<ObservacionPendiente | null>(null)

const mostrarFiltros = ref(false)

// Offcanvas state
const offcanvasAbierto = ref(false)

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

const areasStore = useAreasStore()
const categoriasStore = useCategoriasStore()
const { areas: areasDisponibles } = storeToRefs(areasStore)
const { categorias: categoriasDisponibles } = storeToRefs(categoriasStore)
const estadosDisponibles = ref<EstadoFiltro[]>([])
const cargandoFiltros = computed(() => areasStore.loading || categoriasStore.loading)

const observacionesRevision = ref<ObservacionPendiente[]>([])
const cargandoObservaciones = ref(false)

const mostrarAlertaRevision = computed(() => {
  return !esVistaSupervision.value && observacionesRevision.value.length > 0
})

const cargarObservacionesRevision = async () => {
  try {
    cargandoObservaciones.value = true
    const response = await listarObservacionesPendientes()
    observacionesRevision.value = response?.data || []
  } catch (error) {
    console.error('Error cargando observaciones pendientes:', error)
  } finally {
    cargandoObservaciones.value = false
  }
}

const hayFiltrosActivos = computed(() => {
  return Boolean(
    filtros.value.busqueda ||
    filtros.value.fechaDesde ||
    filtros.value.fechaHasta ||
    filtros.value.estado ||
    filtros.value.areaServicioId ||
    filtros.value.categoriaId
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
  observacionCorreccionActual.value = null
  esCorreccionDesdeListado.value = false
  cerrarModalBootstrap()
}

const actualizacionGuardada = async () => {
  // Ya no hay que "marcar como leída" nada: en cuanto guardamos, el
  // registro deja de estar en estado "revision" y el próximo
  // cargarObservacionesRevision() ya no lo va a traer de vuelta.
  observacionCorreccionActual.value = null
  esCorreccionDesdeListado.value = false

  await obtenerActualizaciones(paginaActual.value)
  await cargarObservacionesRevision()

  cerrarModalEdicion()
}

const obtenerCatalogosFiltros = async () => {
  try {
    const [, , estadosResp] = await Promise.all([
      areasStore.fetchAreas(),
      categoriasStore.fetchCategorias(),
      api.get(ENDPOINT_STATUS),
    ])
    estadosDisponibles.value = estadosResp.data?.data || []
  } catch (err) {
    console.error('Error al cargar catálogos de filtros:', err)
  }
}

const obtenerActualizaciones = async (page = 1) => {
  cargando.value = true
  error.value = ''

  try {
    const params = new URLSearchParams()
    params.append('page', String(page))
    params.append('vista', props.vista)

    if (filtros.value.busqueda.trim()) params.append('busqueda', filtros.value.busqueda.trim())
    if (filtros.value.fechaDesde) params.append('fecha_desde', filtros.value.fechaDesde)
    if (filtros.value.fechaHasta) params.append('fecha_hasta', filtros.value.fechaHasta)
    if (filtros.value.estado) params.append('estado', filtros.value.estado)
    if (filtros.value.areaServicioId !== '') params.append('area_servicio_id', String(filtros.value.areaServicioId))
    if (filtros.value.categoriaId !== '') params.append('actualizacion_categoria_id', String(filtros.value.categoriaId))

    const respuesta = await api.get(`/actualizaciones?${params.toString()}`)

    actualizaciones.value = respuesta.data.data || []

    const meta = respuesta.data.meta || respuesta.data
    paginaActual.value = meta.current_page || 1
    totalPaginas.value = meta.last_page || 1
    totalRegistros.value = meta.total || actualizaciones.value.length
  } catch (err) {
    console.error('Error al cargar la lista:', err)
    error.value = 'No se pudo conectar con el servidor para obtener los datos.'
  } finally {
    cargando.value = false
    if (window.innerWidth < 768) mostrarFiltros.value = false
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

let filtroTimeout: ReturnType<typeof setTimeout> | null = null

watch(
  () => [
    filtros.value.fechaDesde,
    filtros.value.fechaHasta,
    filtros.value.areaServicioId,
    filtros.value.categoriaId,
  ],
  () => {
    if (filtroTimeout) clearTimeout(filtroTimeout)

    filtroTimeout = setTimeout(() => {
      obtenerActualizaciones(1)
    }, 250)
  }
)

const cambiarPagina = (pagina: number) => {
  if (pagina >= 1 && pagina <= totalPaginas.value && pagina !== paginaActual.value) {
    obtenerActualizaciones(pagina)
  }
}

const verDetalles = (id: number) => {
  router.push({
    name: esVistaSupervision.value ? 'supervision-show' : 'mis-registros-show',
    params: { id },
  })
}

const esCorreccionDesdeListado = ref(false)

const editarActualizacion = (item: Version) => {
  observacionCorreccionActual.value = null
  esCorreccionDesdeListado.value = item.actualizacion_estado === 'revision'
  idEditando.value = item.id
}

const duplicandoId = ref<number | null>(null)

const extraerCategoriaIds = (data: any): number[] => {
  if (Array.isArray(data.actualizacion_categoria_ids)) {
    return normalizarCategoriaIds(data.actualizacion_categoria_ids)
  }
  if (Array.isArray(data.categorias)) {
    return normalizarCategoriaIds(
      data.categorias.map((c: any) => c.categoria_actualizacion_id ?? c.id)
    )
  }
  return normalizarCategoriaIds(data.actualizacion_categoria_id)
}

const duplicarActualizacion = async (item: Version) => {
  duplicandoId.value = item.id
  try {
    const respuesta = await api.get(`/actualizaciones/${item.id}`)
    const data = respuesta.data.data || respuesta.data

    let contenidoBlocks: any[] = []
    try {
      const contenidoParseado =
        typeof data.actualizacion_contenido === 'string'
          ? JSON.parse(data.actualizacion_contenido)
          : data.actualizacion_contenido
      contenidoBlocks = contenidoParseado?.blocks || []
    } catch (e) {
      console.error('No se pudo parsear el contenido a duplicar:', e)
    }

    emit('duplicar', {
      titulo: `${data.actualizacion_titulo} (copia)`,
      resumen: data.actualizacion_resumen || '',
      area_servicio_id: data.actualizacion_area_servicio_id,
      actualizacion_categoria_ids: extraerCategoriaIds(data),
      contenidoBlocks,
    })
  } catch (err) {
    console.error('Error al preparar la duplicación:', err)
    error.value = 'No se pudo duplicar el registro. Intenta de nuevo.'
  } finally {
    duplicandoId.value = null
  }
}

const handleCorreccionDesdeOffcanvas = async (observacion: ObservacionPendiente) => {
  offcanvasAbierto.value = false
  await nextTick()
  abrirCorreccionDesdeAlerta(observacion)
}

const abrirCorreccionDesdeAlerta = async (observacion: ObservacionPendiente) => {
  if (!observacion.actualizacion_id) return

  observacionCorreccionActual.value = observacion
  esCorreccionDesdeListado.value = true
  idEditando.value = Number(observacion.actualizacion_id)

  await nextTick()

  const modalElement = document.getElementById('modalEditarRegistro')
  if (modalElement) {
    const modalInstance = Modal.getOrCreateInstance(modalElement)
    modalInstance.show()
  }
}

const confirmarEliminar = async (item: Version) => {
  itemAEliminar.value = item
  await nextTick()
  const modalElement = document.getElementById('modalEliminarRegistro')
  if (modalElement) Modal.getOrCreateInstance(modalElement).show()
}

const confirmarActivar = async (item: Version) => {
  itemAEliminar.value = item
  await nextTick()
  const modalElement = document.getElementById('modalDesarchivarRegistro')
  if (modalElement) Modal.getOrCreateInstance(modalElement).show()
}

const confirmarRevision = async (item: Version) => {
  itemARevision.value = item
  observacionRevision.value = ''
  errorObservacionRevision.value = ''
  await nextTick()
  const modalElement = document.getElementById('modalRevisionRegistro')
  if (modalElement) Modal.getOrCreateInstance(modalElement).show()
}

const limpiarFondoModal = () => {
  document.querySelectorAll('.modal-backdrop').forEach(b => b.remove())
  document.body.classList.remove('modal-open')
  document.body.style.overflow = ''
  document.body.style.paddingRight = ''
}

const inactivarActualizacion = async () => {
  if (!itemAEliminar.value) return
  const modalElement = document.getElementById('modalEliminarRegistro')
  const modalInstance = modalElement ? Modal.getInstance(modalElement) || new Modal(modalElement) : null
  try {
    await api.post(`/actualizaciones/${itemAEliminar.value.id}/inactivar`)
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
    await api.post(`/actualizaciones/${itemAEliminar.value.id}/activar`)
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

const marcarRevisionActualizacion = async () => {
  if (!itemARevision.value) return
  const motivo = observacionRevision.value.trim()
  if (motivo.length < 10) {
    errorObservacionRevision.value = 'Escribe un motivo de al menos 10 caracteres.'
    return
  }
  const modalElement = document.getElementById('modalRevisionRegistro')
  const modalInstance = modalElement ? Modal.getInstance(modalElement) || new Modal(modalElement) : null
  try {
    await api.post(`/actualizaciones/${itemARevision.value.id}/revision`, { observacion: motivo })
    itemARevision.value = null
    observacionRevision.value = ''
    errorObservacionRevision.value = ''
    await obtenerActualizaciones(paginaActual.value)
    if (modalInstance) modalInstance.hide()
    limpiarFondoModal()
  } catch (err) {
    console.error('Error al marcar revisión:', err)
    error.value = 'No se pudo marcar el registro como revisión.'
    if (modalInstance) modalInstance.hide()
    limpiarFondoModal()
  }
}

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

const obtenerIconoCategoria = (icono?: string): string => {
  return icono && icono.trim() !== '' ? icono.trim() : 'bi-tag-fill'
}

// Área mapeada a la forma genérica { id, nombre } que espera <SelectorBusqueda>
const areasConOpcion = computed(() => {
  return areasDisponibles.value.map((area: any) => ({
    id: Number(area.area_servicio_id),
    nombre: area.area_servicio_nombre,
  }))
})

onMounted(async () => {
  window.addEventListener('resize', handleResize)

  await Promise.all([
    obtenerCatalogosFiltros(),
    obtenerActualizaciones(1),
  ])

  if (!esVistaSupervision.value) {
    cargarObservacionesRevision()
  }
})

onUnmounted(() => {
  window.removeEventListener('resize', handleResize)
})

defineExpose({ obtenerActualizaciones })
</script>

<style scoped>
/* Filtros desktop optimizados */
@media (min-width: 1200px) {
  .filtros-barra {
    padding: 22px 24px;
    border-radius: 20px;
  }

  .filtros-contenido {
    display: grid;
    grid-template-columns: repeat(20, minmax(0, 1fr));
    gap: 14px 18px;
    align-items: end;
    margin-top: 0;
  }

  .filtro-busqueda {
    grid-column: span 4;
  }

  .filtro-grupo:nth-child(2) {
    grid-column: span 3;
  }

  .filtro-grupo:nth-child(3) {
    grid-column: span 3;
  }

  .filtro-grupo:nth-child(4) {
    grid-column: span 2;
  }

  .filtro-grupo:nth-child(5) {
    grid-column: span 4;
  }

  .filtro-grupo:nth-child(6) {
    grid-column: span 4;
  }

  .filtro-acciones {
    grid-column: span 2;
    display: flex;
    align-items: end;
    justify-content: flex-start;
    padding-left: 6px;
  }

  .filtro-input {
    height: 44px;
  }

  .filtro-grupo label {
    min-height: 16px;
    margin-bottom: 2px;
  }

  .btn-limpiar {
    width: auto;
    min-width: 76px;
    height: 44px;
    padding: 0 12px;
  }
}
</style>