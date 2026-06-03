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
            <input 
              v-model="filtros.busqueda" 
              type="text" 
              placeholder="Buscar..." 
              class="input-movil"
            />
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
        <div v-for="item in actualizaciones" :key="item.id" class="tarjeta-movil" @click="verDetalles(item.id)">
          <div class="tarjeta-header">
            <h3 class="tarjeta-titulo">{{ item.actualizacion_titulo }}</h3>
            <span :class="['estado-badge-movil', mapearClaseEstado(item.actualizacion_estado)]">
              {{ item.actualizacion_estado }}
            </span>
          </div>

          <div class="tarjeta-info">
            <div class="info-row">
              <i class="bi bi-tag"></i>
              <span class="info-label">Versión:</span>
              <span class="info-value">v{{ item.actualizacion_version }}</span>
            </div>

            <div class="info-row">
              <i class="bi bi-folder"></i>
              <span class="info-label">Área:</span>
              <span class="info-value">{{ obtenerNombreArea(item) }}</span>
            </div>

            <div class="info-row">
              <i class="bi bi-grid"></i>
              <span class="info-label">Categorías:</span>
              <div class="categorias-movil">
                <span 
                  v-for="(cat, idx) in obtenerListaCategorias(item)" 
                  :key="idx"
                  class="cat-badge-movil"
                  :style="{ backgroundColor: getCategoriaColor(cat) + '15', color: getCategoriaColor(cat) }"
                >
                  {{ cat }}
                </span>
                <span v-if="obtenerListaCategorias(item).length === 0" class="cat-vacia">Sin categoría</span>
              </div>
            </div>

            <div class="info-row">
              <i class="bi bi-calendar"></i>
              <span class="info-label">Fecha:</span>
              <span class="info-value">{{ formatearFecha(item.actualizacion_fecha_publicacion || item.actualizacion_fecha_creacion) }}</span>
            </div>

            <div v-if="esVistaSupervision" class="info-row">
              <i class="bi bi-person"></i>
              <span class="info-label">Empleado:</span>
              <span class="info-value">{{ obtenerNombreAutor(item) }}</span>
            </div>
          </div>

          <div class="tarjeta-acciones" @click.stop>
            <button class="accion-movil ver" @click="verDetalles(item.id)">
              <i class="bi bi-eye"></i>
              <span>Ver</span>
            </button>

            <template v-if="esVistaSupervision">
              <button v-if="item.actualizacion_estado !== 'revision'" class="accion-movil revision" 
                @click="confirmarRevision(item)">
                <i class="bi bi-clipboard-check"></i>
                <span>Revisar</span>
              </button>
            </template>

            <template v-else>
              <button class="accion-movil editar" @click="editarActualizacion(item)" data-bs-toggle="modal" 
                data-bs-target="#modalEditarRegistro">
                <i :class="item.actualizacion_estado === 'revision' ? 'bi bi-send-check' : 'bi bi-pencil-square'"></i>
                <span>{{ item.actualizacion_estado === 'revision' ? 'Corregir' : 'Editar' }}</span>
              </button>

              <button v-if="item.actualizacion_estado !== 'inactivo'" class="accion-movil archivar" 
                @click="confirmarEliminar(item)">
                <i class="bi bi-archive"></i>
                <span>Archivar</span>
              </button>

              <button v-else class="accion-movil activar" @click="confirmarActivar(item)">
                <i class="bi bi-box-arrow-in-up"></i>
                <span>Activar</span>
              </button>
            </template>
          </div>
        </div>

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

            <div v-if="!esVistaSupervision" class="filtro-grupo">
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
              <label for="categoria">Categoría</label>
              <select id="categoria" v-model="filtros.categoriaId" class="filtro-input" :disabled="cargandoFiltros">
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
                  <span class="titulo-texto">{{ item.actualizacion_titulo }}</span>
                </td>

                <td v-if="esVistaSupervision" class="cell-autor" data-label="Empleado">
                  <span class="autor-texto">{{ obtenerNombreAutor(item) }}</span>
                </td>

                <td class="cell-area" data-label="Área">
                  <span class="area-texto">{{ obtenerNombreArea(item) }}</span>
                </td>

                <td class="cell-categoria" data-label="Categoría">
                  <div class="categorias-container">
                    <span 
                      v-for="(cat, idx) in obtenerListaCategorias(item)" 
                      :key="idx"
                      class="categoria-badge"
                      :style="{ 
                        backgroundColor: getCategoriaColor(cat) + '15',
                        borderColor: getCategoriaColor(cat),
                        color: getCategoriaColor(cat)
                      }"
                    >
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
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title fw-bold" id="modalLabel">Editar Actualización</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" id="btnCerrarModal"
              @click="cerrarModalEdicion"></button>
          </div>
          <div class="modal-body">
            <Edit v-if="idEditando" :key="`${idEditando}-${notificacionCorreccionActual?.id || 'normal'}`"
              :id="idEditando" :modo-correccion="Boolean(notificacionCorreccionActual) || esCorreccionDesdeListado"
              @guardado="actualizacionGuardada" @cerrar="cerrarModalEdicion" />
          </div>
        </div>
      </div>
    </div>

    <!-- Offcanvas: Revisiones pendientes (solo mis-registros) -->
    <Teleport to="body">
      <template v-if="mostrarAlertaRevision">
        <!-- Overlay -->
        <div
          :class="['oc-overlay', { 'oc-overlay--open': offcanvasAbierto }]"
          @click="offcanvasAbierto = false"
        />

        <!-- Botón flotante -->
        <button
          class="fab-revision"
          :class="{ 'fab-revision--pulsing': !offcanvasAbierto }"
          @click="offcanvasAbierto = true"
          aria-label="Ver revisiones pendientes"
          title="Revisiones pendientes"
        >
          <i class="bi bi-clipboard-check"></i>
          <span class="fab-badge">{{ notificacionesRevision.length }}</span>
        </button>

        <!-- Panel offcanvas -->
        <aside
          :class="['oc-revision', { 'oc-revision--open': offcanvasAbierto }]"
          role="dialog"
          aria-modal="true"
          aria-label="Revisiones pendientes"
        >
          <div class="oc-header">
            <div class="oc-header-left">
              <i class="bi bi-exclamation-circle-fill oc-header-icon"></i>
              <span class="oc-title">Revisión pendiente</span>
              <span class="oc-count">{{ notificacionesRevision.length }}</span>
            </div>
            <button class="oc-close" @click="offcanvasAbierto = false" aria-label="Cerrar panel de revisiones">
              <i class="bi bi-x-lg"></i>
            </button>
          </div>

          <div class="oc-body">
            <div
              v-for="notificacion in notificacionesRevision"
              :key="notificacion.id"
              class="oc-item"
            >
              <div class="oc-item-inner">
                <div class="oc-item-icon-wrap">
                  <i class="bi bi-exclamation-triangle-fill oc-item-icon"></i>
                </div>
                <div class="oc-item-content">
                  <p class="oc-item-msg">{{ notificacion.mensaje }}</p>
                  <button
                    v-if="notificacion.actualizacion_id"
                    type="button"
                    class="oc-item-btn"
                    @click="handleCorreccionDesdeOffcanvas(notificacion)"
                  >
                    <i class="bi bi-pencil-square"></i>
                    Corregir registro
                  </button>
                </div>
              </div>
            </div>
          </div>
        </aside>
      </template>
    </Teleport>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted, computed, nextTick, watch } from 'vue'
import { useRouter } from 'vue-router'
import api from '../../api/api'
import type { Version } from '../../types/version';
import Edit from './EditVersion.vue'
import { Modal } from 'bootstrap'
import {
  listarNotificaciones,
  marcarNotificacionLeida,
  type BlogNotification,
} from '../../api/notificaciones'

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

const ENDPOINT_AREAS = '/area-servicio'
const ENDPOINT_CATEGORIAS = '/categorias'
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
const notificacionCorreccionActual = ref<BlogNotification | null>(null)

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

const areasDisponibles = ref<AreaFiltro[]>([])
const categoriasDisponibles = ref<CategoriaFiltro[]>([])
const estadosDisponibles = ref<EstadoFiltro[]>([])
const cargandoFiltros = ref(false)

const notificaciones = ref<BlogNotification[]>([])
const cargandoNotificaciones = ref(false)

const notificacionesRevision = computed(() => {
  return notificaciones.value.filter((item) => {
    return item.tipo === 'revision' && !item.leida_en
  })
})

const mostrarAlertaRevision = computed(() => {
  return !esVistaSupervision.value && notificacionesRevision.value.length > 0
})

// Cierra el offcanvas con Escape
const onKeydown = (e: KeyboardEvent) => {
  if (e.key === 'Escape' && offcanvasAbierto.value) {
    offcanvasAbierto.value = false
  }
}

const cargarNotificacionesRevision = async () => {
  try {
    cargandoNotificaciones.value = true
    const response = await listarNotificaciones(20)
    notificaciones.value = (response?.data || []).filter((item: BlogNotification) => {
      return item.tipo === 'revision'
    })
  } catch (error) {
    console.error('Error cargando notificaciones de revisión:', error)
  } finally {
    cargandoNotificaciones.value = false
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
  notificacionCorreccionActual.value = null
  esCorreccionDesdeListado.value = false
  cerrarModalBootstrap()
}

const actualizacionGuardada = async () => {
  if (notificacionCorreccionActual.value) {
    try {
      await marcarNotificacionLeida(notificacionCorreccionActual.value.id)
      notificacionCorreccionActual.value.leida_en = new Date().toISOString()
      window.dispatchEvent(new Event('notificaciones-updated'))
    } catch (error) {
      console.error('Error marcando notificación de corrección como leída:', error)
    }
  }

  notificacionCorreccionActual.value = null
  esCorreccionDesdeListado.value = false

  await obtenerActualizaciones(paginaActual.value)
  await cargarNotificacionesRevision()

  cerrarModalEdicion()
}

const obtenerCatalogosFiltros = async () => {
  cargandoFiltros.value = true
  try {
    const [areasResp, categoriasResp, estadosResp] = await Promise.all([
      api.get(ENDPOINT_AREAS),
      api.get(ENDPOINT_CATEGORIAS),
      api.get(ENDPOINT_STATUS)
    ])
    areasDisponibles.value = areasResp.data?.data || []
    categoriasDisponibles.value = categoriasResp.data?.data || []
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
}

const formatearFecha = (fechaString: string) => {
  if (!fechaString) return 'Sin fecha'
  const opciones: Intl.DateTimeFormatOptions = { year: 'numeric', month: 'short', day: '2-digit' }
  const fechaStr = new Date(fechaString).toLocaleDateString('es-ES', opciones)
  return fechaStr.charAt(0).toUpperCase() + fechaStr.slice(1)
}

const mapearClaseEstado = (estado: string) => {
  const estadoMin = (estado || '').toLowerCase()
  if (estadoMin === 'publicado') return 'estado-green'
  if (estadoMin === 'borrador') return 'estado-yellow'
  if (estadoMin === 'revision') return 'estado-blue'
  if (estadoMin === 'inactivo') return 'estado-dark-gray'
  return 'estado-gray'
}

let filtroTimeout: ReturnType<typeof setTimeout> | null = null

watch(
  filtros,
  () => {
    if (filtroTimeout) clearTimeout(filtroTimeout)
    filtroTimeout = setTimeout(() => { obtenerActualizaciones(1) }, 400)
  },
  { deep: true }
)

const obtenerNombreArea = (item: Version) => item.area_servicio?.area_servicio_nombre || 'Sin área'

const obtenerListaCategorias = (item: Version): string[] => {
  if (Array.isArray((item as any).categorias) && (item as any).categorias.length > 0) {
    return (item as any).categorias
      .map((categoria: any) => categoria.categoria_actualizacion_nombre)
      .filter(Boolean)
  }
  
  if (item.categoria?.categoria_actualizacion_nombre) {
    return [item.categoria.categoria_actualizacion_nombre]
  }
  
  return []
}

const getCategoriaColor = (nombre: string) => {
  const colorMap: Record<string, string> = {
    'inicio': '#077E9D',
    'noticias': '#FCBB1C',
    'actualizaciones': '#025B7D',
    'documentos': '#4F46E5',
    'tutoriales': '#10B981',
    'eventos': '#F59E0B',
    'avisos': '#EF4444',
    'novedades': '#8B5CF6',
    'seguridad': '#DC2626',
    'capacitacion': '#14B8A6',
    'proyectos': '#6366F1'
  }
  
  const lowerNombre = nombre.toLowerCase()
  for (const [key, color] of Object.entries(colorMap)) {
    if (lowerNombre.includes(key)) {
      return color
    }
  }
  return '#077E9D'
}

const getCategoriaIcon = (nombre: string) => {
  const iconMap: Record<string, string> = {
    'inicio': 'bi bi-house-fill',
    'noticias': 'bi bi-megaphone-fill',
    'actualizaciones': 'bi bi-arrow-repeat',
    'documentos': 'bi bi-file-text-fill',
    'tutoriales': 'bi bi-journal-bookmark-fill',
    'eventos': 'bi bi-calendar-event-fill',
    'avisos': 'bi bi-bell-fill',
    'novedades': 'bi bi-star-fill',
    'seguridad': 'bi bi-shield-lock-fill',
    'capacitacion': 'bi bi-mortarboard-fill',
    'proyectos': 'bi bi-kanban'
  }
  
  const lowerNombre = nombre.toLowerCase()
  for (const [key, icon] of Object.entries(iconMap)) {
    if (lowerNombre.includes(key)) {
      return icon
    }
  }
  return 'bi bi-tag-fill'
}

const obtenerNombreCategoria = (item: Version) => {
  const categorias = obtenerListaCategorias(item)
  return categorias.join(', ') || 'Sin categoría'
}

const obtenerNombreAutor = (item: any) => {
  const autor = item.usuario_autor || item.autor || item.usuario
  if (!autor) return 'Empleado del área'
  const nombres = [autor.usuario_nombre1, autor.usuario_nombre2, autor.usuario_apellido1, autor.usuario_apellido2]
    .filter(Boolean).join(' ').trim()
  return nombres || autor.usuario_nombre || autor.usuario_usuario || autor.usuario_login || 'Empleado del área'
}

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
  notificacionCorreccionActual.value = null
  esCorreccionDesdeListado.value = item.actualizacion_estado === 'revision'
  idEditando.value = item.id
}

const handleCorreccionDesdeOffcanvas = async (notificacion: BlogNotification) => {
  offcanvasAbierto.value = false
  await nextTick()
  abrirCorreccionDesdeAlerta(notificacion)
}

const abrirCorreccionDesdeAlerta = async (notificacion: BlogNotification) => {
  if (!notificacion.actualizacion_id) return

  notificacionCorreccionActual.value = notificacion
  esCorreccionDesdeListado.value = true
  idEditando.value = Number(notificacion.actualizacion_id)

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

onMounted(async () => {
  window.addEventListener('resize', handleResize)
  
  await Promise.all([
    obtenerCatalogosFiltros(),
    obtenerActualizaciones(1),
  ])

  if (!esVistaSupervision.value) {
    cargarNotificacionesRevision()
  }

  window.addEventListener('keydown', onKeydown)
})

onUnmounted(() => {
  window.removeEventListener('resize', handleResize)
  window.removeEventListener('keydown', onKeydown)
})

defineExpose({ obtenerActualizaciones })
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

/* Layout */
.contenedor-lista-tabla {
  width: 100%;
  max-width: 1400px;
  margin: 0 auto;
  padding: 0 16px;
}

@media (min-width: 768px) {
  .contenedor-lista-tabla { padding: 0 24px; }
}

.modo-supervision { max-width: 1500px; }

/* ============================================
   ESTILOS PARA MÓVIL (< 420px)
   ============================================ */

.vista-movil {
  min-height: 100vh;
}

/* Filtros móvil */
.filtros-movil {
  position: sticky;
  top: 0;
  z-index: 80;
  padding-bottom: 12px;
}

.btn-filtros-movil {
  width: 100%;
  padding: 10px 16px;
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  font-size: 0.85rem;
  font-weight: 600;
  color: var(--primary);
  cursor: pointer;
  transition: all 0.2s ease;
  position: relative;
  box-shadow: 0 0 0 5px rgba(7, 126, 157, 0.1);
}

.btn-filtros-movil:active {
  transform: scale(0.98);
}

.filtros-activos-badge {
  color: var(--warning);
  font-size: 1.2rem;
  position: absolute;
  top: -2px;
  right: -2px;
}

.filtros-panel-movil {
  margin-top: 8px;
  padding: 12px;
  background: white;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
  display: flex;
  flex-direction: column;
  gap: 10px;
  animation: slideDown 0.2s ease;
}

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

.filtro-movil-item {
  width: 100%;
}

.input-movil,
.select-movil {
  width: 100%;
  padding: 10px 12px;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  font-size: 0.85rem;
  background: white;
  transition: all 0.2s ease;
}

.input-movil:focus,
.select-movil:focus {
  outline: none;
  border-color: var(--primary);
  box-shadow: 0 0 0 2px rgba(7, 126, 157, 0.1);
}

.filtro-acciones-movil {
  display: flex;
  gap: 8px;
  margin-top: 4px;
}

.btn-limpiar-movil {
  flex: 1;
  padding: 10px;
  background: #f1f5f9;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  font-size: 0.8rem;
  font-weight: 600;
  color: #64748b;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-limpiar-movil:active {
  transform: scale(0.98);
}

/* Tarjetas móvil */
.tarjetas-movil {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.tarjeta-movil {
  background: white;
  border-radius: 16px;
  padding: 14px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
  transition: all 0.2s ease;
  border: 1px solid #e2e8f0;
}

.tarjeta-movil:active {
  transform: scale(0.99);
}

.tarjeta-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 8px;
  margin-bottom: 12px;
  padding-bottom: 10px;
  border-bottom: 1px solid #f1f5f9;
}

.tarjeta-titulo {
  font-size: 0.95rem;
  font-weight: 700;
  color: #1e293b;
  margin: 0;
  line-height: 1.3;
  flex: 1;
  overflow: hidden;
  text-overflow: ellipsis;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
}

.estado-badge-movil {
  display: inline-block;
  padding: 3px 8px;
  border-radius: 20px;
  font-size: 0.65rem;
  font-weight: 600;
  text-transform: capitalize;
  white-space: nowrap;
}

.tarjeta-info {
  display: flex;
  flex-direction: column;
  gap: 8px;
  margin-bottom: 12px;
}

.info-row {
  display: flex;
  align-items: flex-start;
  gap: 6px;
  font-size: 0.75rem;
}

.info-row i {
  color: var(--primary);
  font-size: 0.7rem;
  margin-top: 2px;
  min-width: 16px;
}

.info-label {
  color: #64748b;
  font-weight: 500;
  min-width: 65px;
}

.info-value {
  color: #334155;
  flex: 1;
  word-break: break-word;
}

.categorias-movil {
  flex: 1;
  display: flex;
  flex-wrap: wrap;
  gap: 4px;
}

.cat-badge-movil {
  display: inline-block;
  padding: 2px 6px;
  border-radius: 12px;
  font-size: 0.6rem;
  font-weight: 500;
  white-space: nowrap;
}

.cat-vacia {
  font-size: 0.65rem;
  color: #94a3b8;
  font-style: italic;
}

.tarjeta-acciones {
  display: flex;
  gap: 8px;
  padding-top: 10px;
  border-top: 1px solid #f1f5f9;
}

.accion-movil {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 4px;
  padding: 8px 6px;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  background: white;
  font-size: 0.7rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
}

.accion-movil i {
  font-size: 0.7rem;
}

.accion-movil:active {
  transform: scale(0.97);
}

.accion-movil.ver {
  color: var(--primary);
  border-color: rgba(7, 126, 157, 0.3);
}

.accion-movil.revision {
  color: var(--warning);
  border-color: rgba(252, 187, 28, 0.3);
}

.accion-movil.editar {
  color: #10b981;
  border-color: rgba(16, 185, 129, 0.3);
}

.accion-movil.archivar {
  color: #ef4444;
  border-color: rgba(239, 68, 68, 0.3);
}

.accion-movil.activar {
  color: #3b82f6;
  border-color: rgba(59, 130, 246, 0.3);
}

/* Estado vacío móvil */
.vacio-movil {
  text-align: center;
  padding: 40px 20px;
  color: #94a3b8;
}

.vacio-movil i {
  font-size: 2.5rem;
  margin-bottom: 12px;
  opacity: 0.5;
}

.vacio-movil p {
  font-size: 0.85rem;
  margin: 0;
}

/* Estado de carga móvil */
.estado-movil {
  text-align: center;
  padding: 60px 20px;
}

.estado-movil.error {
  color: #ef4444;
}

.estado-movil i {
  font-size: 2rem;
  margin-bottom: 12px;
}

.estado-movil p {
  font-size: 0.85rem;
  margin-bottom: 16px;
}

.btn-retry-movil {
  padding: 10px 20px;
  background: var(--primary);
  color: white;
  border: none;
  border-radius: 10px;
  font-size: 0.8rem;
  font-weight: 600;
  cursor: pointer;
}

/* Paginación móvil */
.paginacion-movil {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 16px;
  padding: 16px;
  margin-top: 8px;
}

.pag-movil {
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  cursor: pointer;
  transition: all 0.2s ease;
}

.pag-movil:active {
  transform: scale(0.95);
}

.pag-movil:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.pag-info {
  font-size: 0.85rem;
  font-weight: 600;
  color: #475569;
}

/* ============================================
   ESTILOS PARA DESKTOP (>= 420px)
   ============================================ */

/* Estado vacío / error */
.estado-mensaje {
  text-align: center;
  padding: 60px 40px;
  background: white;
  border-radius: 20px;
  box-shadow: var(--shadow-sm);
  color: #6b7280;
}

.estado-mensaje.error  { border-top: 3px solid #ef4444; }
.estado-mensaje.vacio  { border-top: 3px solid var(--warning); }

.error-icon, .empty-icon { font-size: 48px; margin-bottom: 16px; }

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

.btn-retry:hover { transform: translateY(-2px); box-shadow: var(--shadow-md); }

/* Filtros */
.tabla-con-filtros { display: flex; flex-direction: column; gap: 16px; }

.filtros-barra {
  background: white;
  border: 1px solid #e1e7f0;
  border-radius: 16px;
  padding: 18px;
  box-shadow: var(--shadow-sm);
}

.btn-toggle-filtros {
  width: 100%;
  padding: 12px;
  background: #f8f9fa;
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
}

.btn-toggle-filtros:hover { background: #f0f2f5; }

.filtros-contenido {
  display: grid;
  grid-template-columns: 1fr;
  gap: 16px;
  margin-top: 16px;
  transition: all 0.3s ease;
}

@media (min-width: 768px) {
  .filtros-contenido { grid-template-columns: repeat(2, 1fr); margin-top: 0; }
  .btn-toggle-filtros { display: none; }
}

@media (min-width: 1024px) { 
  .filtros-contenido { grid-template-columns: repeat(3, 1fr); } 
}

.filtros-visible { display: grid; }

@media (max-width: 767px) {
  .filtros-contenido:not(.filtros-visible) { display: none; }
}

.filtro-grupo { display: flex; flex-direction: column; gap: 8px; }

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

.filtro-acciones { display: flex; gap: 10px; align-items: end; }

.btn-limpiar {
  height: 42px;
  padding: 0 16px;
  border-radius: 12px;
  border: 1px solid #e1e7f0;
  font-weight: 600;
  cursor: pointer;
  transition: var(--transition);
  white-space: nowrap;
  background: #f3f4f6;
  color: #374151;
}

.btn-limpiar:hover { transform: translateY(-2px); box-shadow: var(--shadow-sm); }

/* Tabla */
.table-container {
  background: white;
  border-radius: 16px;
  overflow-x: auto;
  border: 1px solid #e1e7f0;
  box-shadow: var(--shadow-md);
  transition: var(--transition);
}

.table-container:hover { box-shadow: var(--shadow-lg); }

.base-table {
  width: 100%;
  border-collapse: collapse;
  min-width: 600px;
}

@media (max-width: 768px) { 
  .base-table { min-width: 100%; } 
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

.base-table tbody tr:hover td { color: #1a202c; }
.base-table tbody tr:hover .titulo-texto { color: var(--primary); }
.base-table tbody tr:hover .version-badge {
  background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
  color: white;
  border-color: transparent;
  transform: scale(1.05);
}
.base-table tbody tr:hover .badge-estado,
.base-table tbody tr:hover .btn-icon { transform: scale(1.05); }

.base-table td {
  padding: 12px 20px;
  font-size: 0.95rem;
  color: #555c68;
  vertical-align: middle;
  transition: var(--transition-smooth);
}

/* Tarjetas en móvil para desktop */
@media (max-width: 768px) {
  .base-table thead { display: none; }
  .base-table tbody tr {
    display: block;
    margin-bottom: 16px;
    border-radius: 12px;
    box-shadow: var(--shadow-sm);
    border: 1px solid #e1e7f0;
  }
  .base-table tbody tr:hover { transform: translateX(4px) scale(1.02); }
  .base-table td {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    border-bottom: 1px solid #f0f2f5;
    text-align: right;
  }
  .base-table td:last-child { border-bottom: none; }
  .base-table td::before {
    content: attr(data-label);
    font-weight: 600;
    color: var(--primary);
    font-size: 0.8rem;
    text-align: left;
    min-width: 100px;
  }
  .icon-group { justify-content: flex-end; }
}

/* Celdas */
.titulo-texto { font-weight: 600; color: #1a202c; transition: var(--transition-smooth); }
.area-texto { color: #4b5563; font-weight: 500; }

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

.fecha-info { display: flex; flex-direction: column; gap: 4px; }
.fecha-label {
  font-size: 0.7rem;
  font-weight: 600;
  text-transform: uppercase;
  color: #9ca3af;
  letter-spacing: 0.5px;
  transition: var(--transition-smooth);
}
.fecha-publicado { font-size: 0.85rem; color: var(--primary); transition: var(--transition-smooth); }
.fecha-creado { font-size: 0.85rem; color: #9ca3af; transition: var(--transition-smooth); }

.badge-estado {
  display: inline-block;
  padding: 4px 10px;
  border-radius: 20px;
  font-size: 0.8rem;
  font-weight: 600;
  text-transform: capitalize;
  transition: var(--transition-smooth);
}
.estado-green { background-color: #e6f7e9; color: #2e7d32; }
.estado-yellow { background-color: #fef8e3; color: #f9a825; }
.estado-blue { background-color: #eaf4fd; color: #2f84d3; }
.estado-dark-gray { background-color: #e5e7eb; color: #374151; }
.estado-gray { background-color: #f2f4f7; color: #5f6671; }

.icon-group { display: flex; gap: 15px; justify-content: flex-end; }

.btn-icon {
  background: none;
  border: none;
  padding: 0;
  color: #9ca3af;
  font-size: 1.1rem;
  cursor: pointer;
  transition: var(--transition-smooth);
}
.btn-icon:hover { color: #4b5563; }

.col-autor, .cell-autor { min-width: 170px; }
.autor-texto {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-weight: 700;
  color: #1f2937;
}

.btn-icon-revision { width: auto; padding: 8px 11px; gap: 6px; }

/* Categorías en desktop */
.categorias-container {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  align-items: center;
}

.categoria-badge {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 4px 8px 4px 6px;
  border-radius: 20px;
  font-size: 0.7rem;
  font-weight: 500;
  border: 1px solid;
  white-space: nowrap;
  transition: all 0.2s ease;
}

.categoria-badge i {
  font-size: 0.65rem;
}

.categoria-badge:hover {
  transform: translateY(-1px);
  filter: brightness(0.95);
}

.categoria-vacia {
  font-size: 0.75rem;
  color: #94a3b8;
  font-style: italic;
}

/* Footer tabla */
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

@media (max-width: 768px) { 
  .table-footer { flex-direction: column; text-align: center; } 
}

.info-registros { font-size: 0.85rem; color: #6b7280; }
.total-registros { color: var(--primary); font-weight: 500; }

.pagination-moderno {
  display: flex;
  gap: 8px;
  list-style: none;
  margin: 0;
  padding: 0;
  flex-wrap: wrap;
  justify-content: center;
}
.pagination-moderno li { display: inline-block; }
.pagination-moderno li a {
  display: flex;
  align-items: center;
  justify-content: center;
  min-width: 30px;
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
.pagination-moderno li.disabled a { opacity: 0.5; cursor: not-allowed; }

/* Input búsqueda con ícono */
.input-busqueda-wrapper { position: relative; display: flex; align-items: center; }
.icono-busqueda {
  position: absolute;
  left: 12px;
  color: #9ca3af;
  font-size: 0.95rem;
  pointer-events: none;
}
.filtro-busqueda .filtro-input { padding-left: 34px; }

/* Filtros desktop optimizados */
@media (min-width: 1200px) {
  .filtros-barra { padding: 22px 24px; border-radius: 20px; }
  .filtros-contenido {
    display: grid;
    grid-template-columns: repeat(20, minmax(0, 1fr));
    gap: 14px 18px;
    align-items: end;
    margin-top: 0;
  }
  .filtro-busqueda { grid-column: span 3; }
  .filtro-grupo:nth-child(2) { grid-column: span 3; }
  .filtro-grupo:nth-child(3) { grid-column: span 3; }
  .filtro-grupo:nth-child(4) { grid-column: span 2; }
  .filtro-grupo:nth-child(5) { grid-column: span 3; }
  .filtro-grupo:nth-child(6) { grid-column: span 4; }
  .filtro-acciones {
    grid-column: span 2;
    display: flex;
    align-items: end;
    justify-content: flex-start;
    padding-left: 6px;
  }
  .filtro-input { height: 44px; }
  .filtro-grupo label { min-height: 16px; margin-bottom: 2px; }
  .btn-limpiar { width: auto; min-width: 76px; height: 44px; padding: 0 12px; }
}

/* Modales */
.modal-content { margin: 16px; }
@media (min-width: 768px) { .modal-content { margin: 0; } }

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
  flex-wrap: wrap;
}

.revision-observacion-group { margin-top: 16px; display: flex; flex-direction: column; gap: 6px; }
.campo-obligatorio { color: #ef4444; }
.revision-observacion-error { color: #ef4444; font-size: 0.85rem; margin: 0; }
</style>

<!-- Estilos globales para los elementos renderizados con Teleport -->
<style>
/* Overlay */
.oc-overlay {
  position: fixed;
  inset: 0;
  background: transparent;
  z-index: 1040;
  pointer-events: none;
  transition: background 0.25s ease;
}
.oc-overlay--open {
  background: rgba(0, 0, 0, 0.35);
  pointer-events: all;
}

/* Botón flotante */
.fab-revision {
  position: fixed;
  bottom: 28px;
  right: 28px;
  z-index: 1050;
  width: 54px;
  height: 54px;
  border-radius: 50%;
  background: linear-gradient(135deg, #077E9D 0%, #025B7D 100%);
  border: none;
  cursor: pointer;
  color: white;
  font-size: 1.3rem;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 4px 20px rgba(7, 126, 157, 0.4);
  transition: transform 0.2s ease, box-shadow 0.2s ease;
  outline: none;
}

.fab-revision:hover {
  transform: translateY(-3px) scale(1.05);
  box-shadow: 0 8px 28px rgba(7, 126, 157, 0.5);
}

.fab-revision:active {
  transform: scale(0.97);
}

.fab-revision .bi {
  line-height: 1;
  display: block;
}

/* Badge del FAB */
.fab-badge {
  position: absolute;
  top: -5px;
  right: -5px;
  background: #FCBB1C;
  color: #7a5a00;
  font-size: 10px;
  font-weight: 700;
  min-width: 20px;
  height: 20px;
  border-radius: 10px;
  padding: 0 4px;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 2px solid white;
  line-height: 1;
}

/* Animación de pulso en el FAB cuando está cerrado */
.fab-revision--pulsing {
  animation: fab-pulse 2.5s infinite;
}

@keyframes fab-pulse {
  0%, 100% { box-shadow: 0 4px 20px rgba(7, 126, 157, 0.4); }
  50% { box-shadow: 0 4px 20px rgba(7, 126, 157, 0.4), 0 0 0 8px rgba(7, 126, 157, 0.12); }
}

/* Panel offcanvas */
.oc-revision {
  position: fixed;
  top: 0;
  right: 0;
  width: 390px;
  max-width: calc(100vw - 40px);
  height: 100%;
  background: #ffffff;
  z-index: 1055;
  transform: translateX(100%);
  transition: transform 0.32s cubic-bezier(0.4, 0, 0.2, 1);
  display: flex;
  flex-direction: column;
  box-shadow: -6px 0 32px rgba(0, 0, 0, 0.14);
}

.oc-revision--open {
  transform: translateX(0);
}

/* Header */
.oc-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 15px 18px;
  border-bottom: 1px solid #e1e7f0;
  background: #fffef7;
  border-left: 4px solid #FCBB1C;
  flex-shrink: 0;
}

.oc-header-left {
  display: flex;
  align-items: center;
  gap: 8px;
}

.oc-header-icon {
  color: #FCBB1C;
  font-size: 1rem;
  line-height: 1;
}

.oc-title {
  font-size: 0.88rem;
  font-weight: 700;
  color: #856404;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.oc-count {
  background: #FCBB1C;
  color: #7a5a00;
  font-size: 0.68rem;
  font-weight: 700;
  padding: 2px 8px;
  border-radius: 10px;
  line-height: 1.4;
}

.oc-close {
  width: 32px;
  height: 32px;
  border: 1px solid #e1e7f0;
  background: #f9fafb;
  border-radius: 8px;
  cursor: pointer;
  color: #6b7280;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.85rem;
  transition: background 0.15s, color 0.15s;
  flex-shrink: 0;
}

.oc-close:hover {
  background: #f3f4f6;
  color: #374151;
}

/* Cuerpo desplazable */
.oc-body {
  flex: 1;
  overflow-y: auto;
  padding: 14px 16px;
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.oc-body::-webkit-scrollbar { width: 4px; }
.oc-body::-webkit-scrollbar-track { background: #f9fafb; }
.oc-body::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 4px; }

/* Item de revisión */
.oc-item {
  background: #ffffff;
  border-radius: 12px;
  border: 1px solid #f0e0b0;
  border-left: 3px solid #FCBB1C;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.oc-item:hover {
  transform: translateX(4px);
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.07);
}

.oc-item-inner {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  padding: 12px 14px;
}

.oc-item-icon-wrap {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  background: #fef8e3;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  margin-top: 1px;
}

.oc-item-icon {
  color: #f59e0b;
  font-size: 0.9rem;
  line-height: 1;
}

.oc-item-content {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 7px;
}

.oc-item-msg {
  margin: 0;
  font-size: 0.88rem;
  color: #374151;
  line-height: 1.5;
  word-break: break-word;
}

.oc-item-btn {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  font-size: 0.8rem;
  font-weight: 600;
  color: #077E9D;
  background: none;
  border: none;
  cursor: pointer;
  padding: 0;
  transition: color 0.15s;
  text-align: left;
}

.oc-item-btn:hover {
  color: #025B7D;
  text-decoration: underline;
}

.oc-item-btn .bi {
  font-size: 0.82rem;
  line-height: 1;
}

/* Responsive móvil */
@media (max-width: 480px) {
  .oc-revision {
    width: 100%;
    max-width: 100%;
  }
  .fab-revision {
    bottom: 20px;
    right: 20px;
  }
}
</style>