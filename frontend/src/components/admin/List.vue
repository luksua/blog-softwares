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

    <div v-else-if="actualizaciones.length === 0" class="estado-mensaje vacio">
      <div class="empty-icon">📭</div>
      <h3>No hay registros para mostrar</h3>
      <p>Aún no se ha registrado ninguna actualización en el sistema.</p>
    </div>

    <div v-else class="table-container">
      <table class="base-table">
        <thead>
          <tr>
            <th class="col-titulo">TÍTULO</th>
            <th class="col-version">VERSIÓN</th>
            <th class="col-fecha">FECHA</th>
            <th class="col-estado">ESTADO</th>
            <th class="col-acciones">ACCIONES</th>
          </tr>
        </thead>

        <tbody>
          <tr v-for="item in actualizaciones" :key="item.id" class="fila-registro" @click="verDetalles(item.id)">
            <td class="cell-titulo">
              <span class="titulo-texto">{{ item.actualizacion_titulo }}</span>
            </td>

            <td class="cell-version">
              <span class="version-badge">
                v{{ item.actualizacion_version }}
              </span>
            </td>

            <td class="cell-fecha">
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

            <td class="cell-estado">
              <span :class="['badge-estado', mapearClaseEstado(item.actualizacion_estado)]">
                {{ item.actualizacion_estado }}
              </span>
            </td>

            <td class="cell-acciones">
              <div class="icon-group">
                <button title="Editar" class="btn-icon" data-bs-toggle="modal" data-bs-target="#modalEditarRegistro"
                  @click.stop="editarActualizacion(item.id)">
                  <i class="bi bi-pencil-square"></i>
                </button>

                <button title="Ver" class="btn-icon" @click.stop="verDetalles(item.id)">
                  <i class="bi bi-eye"></i>
                </button>

                <div v-if="item.actualizacion_estado !== 'inactivo'">
                  <button title="Archivar" class="btn-icon" data-bs-toggle="modal"
                    data-bs-target="#modalEliminarRegistro" @click.stop="confirmarEliminar(item)">
                    <i class="bi bi-x-lg"></i>
                  </button>
                </div>

                <div v-else>
                  <button title="Desarchivar" class="btn-icon" data-bs-toggle="modal"
                    data-bs-target="#modalDesarchivarRegistro" @click.stop="confirmarActivar(item)">
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

    <!-- Modal de eliminación -->
    <div class="modal fade" id="modalEliminarRegistro" ref="modalEliminarRef" tabindex="-1"
      aria-labelledby="modalEliminarLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="modalEliminarLabel">¿Deseas desactivar este registro?</h5>
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

    <!-- Modal de activacion -->
    <div class="modal fade" id="modalDesarchivarRegistro" ref="modalEliminarRef" tabindex="-1"
      aria-labelledby="modalEliminarLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="modalEliminarLabel">¿Deseas activar este registro?</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>

          <div class="modal-body">
            <p>Al activar el registro, se actualizará el estado del registro a <strong>Publicado</strong></p>

            <strong class="modal-item-title">
              {{ itemAEliminar?.actualizacion_titulo }}
            </strong>
          </div>

          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
              Cancelar
            </button>

            <button type="button" class="btn-primary" @click="activarActualizacion">
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
import { ref, onMounted, computed } from 'vue';
import { useRouter } from 'vue-router'
import api from '../../api/api';
import type { Version } from '../../types/version';
import Edit from '../../components/admin/EditVersion.vue';
import { Modal } from 'bootstrap';

const router = useRouter()

const cerrarModalBootstrap = () => {
  const botonCerrar = document.getElementById('btnCerrarModal');
  if (botonCerrar) {
    botonCerrar.click();
  }
};

const cerrarModalEdicion = () => {
  idEditando.value = null;
  cerrarModalBootstrap();
};

const actualizacionGuardada = async () => {
  await obtenerActualizaciones(paginaActual.value);
  cerrarModalEdicion();
};

const actualizaciones = ref<Version[]>([]);
const cargando = ref(true);
const error = ref('');

const paginaActual = ref(1);
const totalPaginas = ref(1);
const totalRegistros = ref(0);

const itemAEliminar = ref<Version | null>(null);

// Computed para mostrar páginas con límite
const paginasMostradas = computed(() => {
  const maxPages = 5;
  const current = paginaActual.value;
  const total = totalPaginas.value;

  if (total <= maxPages) {
    return Array.from({ length: total }, (_, i) => i + 1);
  }

  let start = Math.max(1, current - 2);
  let end = Math.min(total, start + maxPages - 1);

  if (end - start + 1 < maxPages) {
    start = Math.max(1, end - maxPages + 1);
  }

  return Array.from({ length: end - start + 1 }, (_, i) => start + i);
});

const obtenerActualizaciones = async (page = 1) => {
  cargando.value = true;
  error.value = '';

  try {
    const respuesta = await api.get(`/admin/actualizaciones?page=${page}`);

    actualizaciones.value = respuesta.data.data;

    paginaActual.value = respuesta.data.current_page;
    totalPaginas.value = respuesta.data.last_page;
    totalRegistros.value = respuesta.data.total;

  } catch (err) {
    console.error('Error al cargar la lista:', err);
    error.value = 'No se pudo conectar con el servidor para obtener los datos.';
  } finally {
    cargando.value = false;
  }
};

const formatearFecha = (fechaString: string) => {
  if (!fechaString) return 'Sin fecha';
  const opciones: Intl.DateTimeFormatOptions = { year: 'numeric', month: 'short', day: '2-digit' };
  const fechaStr = new Date(fechaString).toLocaleDateString('en-US', opciones);
  return fechaStr.charAt(0).toUpperCase() + fechaStr.slice(1);
};

const mapearClaseEstado = (estado: string) => {
  const estadoMin = estado.toLowerCase();
  if (estadoMin === 'publicado') return 'estado-green';
  if (estadoMin === 'borrador') return 'estado-yellow';
  if (estadoMin === 'revision') return 'estado-red';
  if (estadoMin === 'inactivo') return 'estado-dark-gray';
  return 'estado-gray';
};

const cambiarPagina = (pagina: number) => {
  if (pagina >= 1 && pagina <= totalPaginas.value && pagina !== paginaActual.value) {
    obtenerActualizaciones(pagina);
  }
};

const verDetalles = (id: number) => {
  router.push({
    name: 'admin-actualizaciones-show',
    params: { id },
  });
};

const idEditando = ref<number | null>(null);

const editarActualizacion = (id: number) => {
  idEditando.value = id;
};

const confirmarEliminar = (item: Version) => {
  itemAEliminar.value = item;
};

const confirmarActivar = (item: Version) => {
  itemAEliminar.value = item;
};

const inactivarActualizacion = async () => {
  if (!itemAEliminar.value) return;

  // 1. Obtenemos el modal de Bootstrap
  const modalElement = document.getElementById('modalEliminarRegistro');
  const modalInstance = modalElement ? Modal.getInstance(modalElement) || new Modal(modalElement) : null;

  try {
    await api.post(`/admin/actualizaciones/${itemAEliminar.value.id}/inactivar`);

    itemAEliminar.value = null;
    await obtenerActualizaciones(paginaActual.value);

    // 2. Ocultamos el modal usando la instancia de Bootstrap
    if (modalInstance) {
      modalInstance.hide();
    }

    // 3. SEGURO CONTRA FALLOS: Si el backdrop oscuro se queda, lo eliminamos a la fuerza
    const backdrops = document.querySelectorAll('.modal-backdrop');
    backdrops.forEach(backdrop => backdrop.remove());
    document.body.classList.remove('modal-open');
    document.body.style.overflow = '';
    document.body.style.paddingRight = '';

  } catch (err) {
    console.error('Error al inactivar:', err);
    error.value = 'No se pudo inactivar la actualización.';
  }
};

const activarActualizacion = async () => {
  if (!itemAEliminar.value) return;

  // 1. Obtenemos el modal de Bootstrap
  const modalElement = document.getElementById('modalDesarchivarRegistro');
  const modalInstance = modalElement ? Modal.getInstance(modalElement) || new Modal(modalElement) : null;

  try {
    await api.post(`/admin/actualizaciones/${itemAEliminar.value.id}/activar`);

    itemAEliminar.value = null;
    await obtenerActualizaciones(paginaActual.value);

    // 2. Ocultamos el modal usando la instancia de Bootstrap
    if (modalInstance) {
      modalInstance.hide();
    }

    // 3. SEGURO CONTRA FALLOS: Si el backdrop oscuro se queda, lo eliminamos a la fuerza
    const backdrops = document.querySelectorAll('.modal-backdrop');
    backdrops.forEach(backdrop => backdrop.remove());
    document.body.classList.remove('modal-open');
    document.body.style.overflow = '';
    document.body.style.paddingRight = '';

  } catch (err) {
    console.error('Error al activar:', err);
    error.value = 'No se pudo activar la actualización.';
  }
};

onMounted(() => {
  obtenerActualizaciones(1);
});

defineExpose({
  obtenerActualizaciones
});
</script>

<style scoped>
/* Variables del sistema */
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

/* Estados de carga y error */
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

/* Contenedor de la tabla con borde y sombra */
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

/* Tabla */
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

/* EFECTO HOVER: Oscurecer y mover ligeramente */
.base-table tbody tr:hover {
  background-color: rgba(0, 0, 0, 0.04);
  transform: translateX(5px) scale(1.01);
  box-shadow: -4px 0 0 var(--primary);
  cursor: pointer;
}

/* Efecto adicional para las celdas */
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

.base-table tbody tr:hover .btn-icon {
  transform: scale(1.1);
}

.base-table td {
  padding: 12px 20px;
  font-size: 0.95rem;
  color: #555c68;
  vertical-align: middle;
  transition: var(--transition-smooth);
}

/* Columnas */
.col-titulo {
  width: 35%;
}

.col-version {
  width: 15%;
}

.col-fecha {
  width: 20%;
}

.col-estado {
  width: 15%;
}

.col-acciones {
  width: 15%;
  text-align: right;
}

/* Celda título */
.titulo-texto {
  font-weight: 600;
  color: #1a202c;
  transition: var(--transition-smooth);
}

/* Badge de versión */
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

/* Fecha */
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

/* Badges de estado - Estilos originales */
.badge-estado {
  display: inline-block;
  padding: 4px 10px;
  border-radius: 20px;
  font-size: 0.8rem;
  font-weight: 600;
  text-transform: capitalize;
  transition: var(--transition-smooth);
}

.base-table tbody tr:hover .badge-estado {
  transform: scale(1.05);
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

/* Iconos de acción - Estilo original */
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

/* Footer de la tabla */
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

/* Paginación moderna */
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

.modal-close:hover {
  color: var(--primary);
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

.btn-cancelar {
  padding: 10px 20px;
  background: white;
  border: 1px solid #e1e7f0;
  border-radius: 10px;
  cursor: pointer;
  transition: var(--transition);
  font-weight: 500;
}

.btn-cancelar:hover {
  background: #f8fafc;
  border-color: var(--primary);
}

.btn-eliminar-modal:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow-sm);
}

/* Responsive */
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

  /* Efecto hover en móvil */
  .base-table tbody tr:hover {
    transform: translateX(4px) scale(1.02);
  }

  .base-table td {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 16px;
    border-bottom: 1px solid #f0f2f5;
  }

  .base-table td::before {
    content: attr(data-label);
    font-weight: 600;
    color: var(--primary);
    font-size: 0.8rem;
  }

  .table-footer {
    flex-direction: column;
    text-align: center;
  }

  .icon-group {
    justify-content: center;
  }
}

.modal-header {
  border-bottom: none;
  border-top: 3px solid var(--warning);
}
</style>