<template>
  <div class="contenedor-lista-tabla container-fluid py-4">
    <div v-if="cargando" class="estado-mensaje">
      <div class="spinner-border text-primary mb-3" role="status">
        <span class="visually-hidden">Cargando...</span>
      </div>
      <p>Cargando actualizaciones...</p>
    </div>

    <div v-else-if="error" class="estado-mensaje error alert alert-danger alert-dismissible fade show" role="alert">
      {{ error }}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      <div class="mt-3">
        <button @click="obtenerActualizaciones()" class="btn btn-outline-danger btn-sm">
          Reintentar
        </button>
      </div>
    </div>

    <div v-else-if="actualizaciones.length === 0" class="estado-mensaje vacio">
      <div class="icono-vacio display-4 mb-3">📄</div>
      <h3>No hay actualizaciones para mostrar</h3>
      <p>Aún no se ha registrado ninguna actualización en el sistema.</p>
      <button class="btn btn-primary btn-sm mt-3">
        Crear primera actualización
      </button>
    </div>

    <div v-else class="table-container shadow-sm rounded">
      <table class="base-table">
        <thead>
          <tr>
            <th class="col-titulo">TÍTULO</th>
            <th class="col-version">VERSION</th>
            <th class="col-fecha">FECHA</th>
            <th class="col-estado">ESTADO</th>
            <th class="col-acciones">ACCIONES</th>
          </tr>
        </thead>

        <tbody>
          <tr v-for="item in actualizaciones" :key="item.id">
            <td class="cell-titulo">
              {{ item.actualizacion_titulo }}
            </td>

            <td class="cell-version">
              <span class="badge bg-secondary bg-opacity-10 text-dark border px-2 py-1">
                v{{ item.actualizacion_version }}
              </span>
            </td>

            <td class="cell-fecha">
              <span v-if="item.actualizacion_estado === 'publicado'">
                P: {{ formatearFecha(item.actualizacion_fecha_publicacion) }}
              </span>
              <span v-else class="text-muted">
                C: {{ formatearFecha(item.actualizacion_fecha_creacion) }}
              </span>
            </td>

            <td class="cell-estado">
              <span :class="['badge-estado', mapearClaseEstado(item.actualizacion_estado)]">
                {{ item.actualizacion_estado }}
              </span>
            </td>

            <td class="cell-acciones">
              <div class="icon-group">
                <button title="Editar" class="btn-icon" @click="editarActualizacion(item)">✎</button>
                <button title="Ver" class="btn-icon" @click="verDetalles(item)">👁</button>
                <button title="Eliminar" class="btn-icon" @click="confirmarEliminar(item)">🗑</button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>

      <div class="bg-white px-4 py-3 border-top d-flex justify-content-between align-items-center">
        <div class="text-muted small">
          Mostrando {{ actualizaciones.length }} registros en esta página (Total: {{ totalRegistros }})
        </div>
        <nav aria-label="Navegación de página">
          <ul class="pagination pagination-sm mb-0 custom-pagination">
            <li class="page-item" :class="{ disabled: paginaActual === 1 }">
              <a class="page-link" href="#" @click.prevent="cambiarPagina(paginaActual - 1)">«</a>
            </li>
            <li v-for="pag in totalPaginas" :key="pag" class="page-item" :class="{ active: paginaActual === pag }">
              <a class="page-link" href="#" @click.prevent="cambiarPagina(pag)">{{ pag }}</a>
            </li>
            <li class="page-item" :class="{ disabled: paginaActual === totalPaginas }">
              <a class="page-link" href="#" @click.prevent="cambiarPagina(paginaActual + 1)">»</a>
            </li>
          </ul>
        </nav>
      </div>
    </div>

    <div class="modal fade" id="modalEliminar" tabindex="-1" ref="modalEliminar">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
          <div class="modal-header">
            <h5 class="modal-title">Confirmar eliminación</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <p>¿Estás seguro de que deseas eliminar la actualización <strong>{{ itemAEliminar?.actualizacion_titulo }}</strong>?</p>
            <p class="text-danger small mb-0">Esta acción no se puede deshacer.</p>
          </div>
          <div class="modal-footer border-top-0">
            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
            <button type="button" class="btn btn-danger" @click="eliminarActualizacion">Eliminar</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
// Quitamos 'computed' de las importaciones porque ya no se usa
import { ref, onMounted } from 'vue';
import api from '../../api/api';
import type { Version } from '../../types/version';
import { Modal } from 'bootstrap';

const actualizaciones = ref<Version[]>([]);
const cargando = ref(true);
const error = ref('');

const paginaActual = ref(1);
const totalPaginas = ref(1);
const totalRegistros = ref(0);

const itemAEliminar = ref<Version | null>(null);
const modalEliminar = ref<HTMLElement | null>(null);
let modalInstance: Modal | null = null;

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
};

const cambiarPagina = (pagina: number) => {
  if (pagina >= 1 && pagina <= totalPaginas.value && pagina !== paginaActual.value) {
    obtenerActualizaciones(pagina);
  }
};

const verDetalles = (item: Version) => {
  console.log('Ver detalles:', item);
};

const editarActualizacion = (item: Version) => {
  console.log('Editar:', item);
};

const confirmarEliminar = (item: Version) => {
  itemAEliminar.value = item;
  if (modalInstance) {
    modalInstance.show();
  }
};

const eliminarActualizacion = async () => {
  if (!itemAEliminar.value) return;

  try {
    await api.delete(`/admin/actualizaciones/${itemAEliminar.value.id}`);
    await obtenerActualizaciones(paginaActual.value);
    if (modalInstance) {
      modalInstance.hide();
    }
  } catch (err) {
    console.error('Error al eliminar:', err);
    error.value = 'No se pudo eliminar la actualización.';
  } finally {
    itemAEliminar.value = null;
  }
};

onMounted(() => {
  obtenerActualizaciones(1);

  if (modalEliminar.value) {
    modalInstance = new Modal(modalEliminar.value);
  }
});

defineExpose({
  obtenerActualizaciones
});
</script>

<style scoped>
.contenedor-lista-tabla {
  width: 100%;
  max-width: 1100px;
  margin: 0 auto;
  font-family: 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
}

.estado-mensaje {
  text-align: center;
  padding: 40px;
  background-color: #f9fafb;
  border-radius: 8px;
  color: #6b7280;
  border: 1px dashed #e5e7eb;
}

.estado-dark-gray { 
  background-color: #e5e7eb; 
  color: #374151; 
  text-decoration: line-through; /* Opcional: un tachado sutil para que sea obvio que está inactivo */
}

.table-container {
  background-color: white;
  overflow: hidden;
  border: 1px solid #e1e7f0;
}

.base-table {
  width: 100%;
  border-collapse: collapse;
}

.base-table thead tr {
  background-color: #0d2141;
  color: white;
  text-align: left;
}

.base-table th {
  padding: 15px 20px;
  font-weight: bold;
  font-size: 0.85rem;
  letter-spacing: 0.05em;
  border: none;
}

.base-table tbody tr {
  border-bottom: 1px solid #e1e7f0;
  transition: background-color 0.2s;
}

.base-table tbody tr:hover {
  background-color: #f9fafb;
}

.base-table td {
  padding: 12px 20px;
  font-size: 0.95rem;
  color: #555c68;
  vertical-align: middle;
}

.col-titulo { width: 35%; }
.col-version { width: 15%; }
.col-fecha { width: 20%; }
.col-estado { width: 15%; }
.col-acciones { width: 15%; text-align: right; }

.cell-titulo {
  font-weight: 600;
  color: #1a202c;
}

.badge-estado {
  display: inline-block;
  padding: 4px 10px;
  border-radius: 20px;
  font-size: 0.8rem;
  font-weight: 600;
  text-transform: capitalize;
}

.estado-green { background-color: #e6f7e9; color: #2e7d32; }
.estado-yellow { background-color: #fef8e3; color: #f9a825; }
.estado-red { background-color: #fdeaea; color: #d32f2f; }
.estado-gray { background-color: #f2f4f7; color: #5f6671; }

.cell-acciones { text-align: right; }

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
  transition: color 0.2s;
}

.btn-icon:hover { color: #4b5563; }

.custom-pagination .page-link {
  color: #0d2141;
  border: none;
  margin: 0 2px;
  border-radius: 4px;
}

.custom-pagination .page-item.active .page-link {
  background-color: #0d2141;
  color: white;
}

.custom-pagination .page-link:hover:not(.active) {
  background-color: #f2f4f7;
}

.shadow-sm { box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08) !important; }
.rounded { border-radius: 6px !important; }
</style>