<template>
  <div class="contenedor-lista-tabla container-fluid py-4">
    <!-- Estados de carga y error con diseño mejorado -->
    <div v-if="cargando" class="text-center py-5">
      <div class="spinner-border text-primary mb-3" role="status">
        <span class="visually-hidden">Cargando...</span>
      </div>
      <p class="text-muted">Cargando actualizaciones...</p>
    </div>

    <div v-else-if="error" class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
      <i class="bi bi-exclamation-triangle-fill me-2"></i>
      {{ error }}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      <div class="mt-2">
        <button @click="obtenerActualizaciones" class="btn btn-outline-danger btn-sm">
          <i class="bi bi-arrow-repeat me-1"></i>Reintentar
        </button>
      </div>
    </div>

    <div v-else-if="actualizaciones.length === 0" class="text-center py-5">
      <div class="mb-4">
        <i class="bi bi-inbox display-1 text-muted"></i>
      </div>
      <h3 class="h4 text-muted">No hay actualizaciones para mostrar</h3>
      <p class="text-muted">Aún no se ha registrado ninguna actualización en el sistema.</p>
      <button class="btn btn-primary btn-sm mt-3">
        <i class="bi bi-plus-circle me-1"></i>Crear primera actualización
      </button>
    </div>

    <!-- Tabla moderna con Bootstrap -->
    <div v-else class="card shadow-sm border-0">
      <div class="card-header bg-white border-0 pt-4 pb-0">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h5 class="mb-0 fw-bold">
            <i class="bi bi-clock-history me-2 text-primary"></i>
            Historial de Actualizaciones
          </h5>
          <div class="d-flex gap-2">
            <div class="input-group input-group-sm" style="width: 250px;">
              <span class="input-group-text bg-white border-end-0">
                <i class="bi bi-search"></i>
              </span>
              <input 
                type="text" 
                class="form-control border-start-0 ps-0" 
                placeholder="Buscar actualización..."
                v-model="filtroBusqueda"
              />
            </div>
            <button class="btn btn-primary btn-sm">
              <i class="bi bi-plus-circle me-1"></i>Nueva
            </button>
          </div>
        </div>
      </div>

      <div class="card-body pt-0">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-dark">
              <tr>
                <th scope="col" class="col-titulo py-3">
                  <i class="bi bi-file-text me-1"></i>TÍTULO
                </th>
                <th scope="col" class="col-version">
                  <i class="bi bi-tag me-1"></i>VERSIÓN
                </th>
                <th scope="col" class="col-fecha">
                  <i class="bi bi-calendar3 me-1"></i>FECHA
                </th>
                <th scope="col" class="col-estado">
                  <i class="bi bi-circle me-1"></i>ESTADO
                </th>
                <th scope="col" class="col-acciones text-end">
                  <i class="bi bi-three-dots-vertical"></i>
                </th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in actualizacionesFiltradas" :key="item.id">
                <td class="fw-semibold">
                  <div class="d-flex align-items-center">
                    <div class="me-2">
                      <i class="bi bi-file-earmark-text text-primary"></i>
                    </div>
                    {{ item.actualizacion_titulo }}
                  </div>
                </td>
                <td>
                  <span class="badge bg-secondary bg-opacity-10 text-dark">
                    <i class="bi bi-tag me-1"></i>v{{ item.actualizacion_version }}
                  </span>
                </td>
                <td>
                  <span v-if="item.actualizacion_estado === 'publicado'">
                    <i class="bi bi-calendar-check text-success me-1"></i>
                    {{ formatearFecha(item.actualizacion_fecha_publicacion) }}
                  </span>
                  <span v-else class="text-muted">
                    <i class="bi bi-calendar-plus me-1"></i>
                    {{ formatearFecha(item.actualizacion_fecha_creacion) }}
                  </span>
                </td>
                <td>
                  <span :class="['badge', mapearClaseEstado(item.actualizacion_estado)]">
                    <i :class="obtenerIconoEstado(item.actualizacion_estado)" class="me-1"></i>
                    {{ item.actualizacion_estado }}
                  </span>
                </td>
                <td class="text-end">
                  <div class="btn-group btn-group-sm" role="group">
                    <button title="Ver detalles" class="btn btn-outline-secondary" @click="verDetalles(item)">
                      <i class="bi bi-eye"></i>
                    </button>
                    <button title="Editar" class="btn btn-outline-primary" @click="editarActualizacion(item)">
                      <i class="bi bi-pencil"></i>
                    </button>
                    <button title="Eliminar" class="btn btn-outline-danger" @click="confirmarEliminar(item)">
                      <i class="bi bi-trash"></i>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Paginación -->
        <div class="d-flex justify-content-between align-items-center mt-3 pt-2">
          <div class="text-muted small">
            Mostrando {{ actualizacionesFiltradas.length }} de {{ actualizaciones.length }} actualizaciones
          </div>
          <nav aria-label="Navegación de página">
            <ul class="pagination pagination-sm mb-0">
              <li class="page-item" :class="{ disabled: paginaActual === 1 }">
                <a class="page-link" href="#" @click.prevent="cambiarPagina(paginaActual - 1)">
                  <i class="bi bi-chevron-left"></i>
                </a>
              </li>
              <li v-for="pag in totalPaginas" :key="pag" class="page-item" :class="{ active: paginaActual === pag }">
                <a class="page-link" href="#" @click.prevent="cambiarPagina(pag)">{{ pag }}</a>
              </li>
              <li class="page-item" :class="{ disabled: paginaActual === totalPaginas }">
                <a class="page-link" href="#" @click.prevent="cambiarPagina(paginaActual + 1)">
                  <i class="bi bi-chevron-right"></i>
                </a>
              </li>
            </ul>
          </nav>
        </div>
      </div>
    </div>

    <!-- Modal de confirmación para eliminar -->
    <div class="modal fade" id="modalEliminar" tabindex="-1" ref="modalEliminar">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Confirmar eliminación</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <p>¿Estás seguro de que deseas eliminar la actualización <strong>{{ itemAEliminar?.actualizacion_titulo }}</strong>?</p>
            <p class="text-danger small">Esta acción no se puede deshacer.</p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="button" class="btn btn-danger" @click="eliminarActualizacion">Eliminar</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import api from '../../api/api';
import type { Version } from '../../types/version';
import { Modal } from 'bootstrap';

// Estados reactivos
const actualizaciones = ref<Version[]>([]);
const cargando = ref(true);
const error = ref('');
const filtroBusqueda = ref('');
const paginaActual = ref(1);
const itemsPorPagina = ref(10);
const itemAEliminar = ref<Version | null>(null);
const modalEliminar = ref<HTMLElement | null>(null);
let modalInstance: Modal | null = null;

// Computed para filtrar por búsqueda
const actualizacionesFiltradas = computed(() => {
  let filtradas = actualizaciones.value;
  
  if (filtroBusqueda.value) {
    const busqueda = filtroBusqueda.value.toLowerCase();
    filtradas = filtradas.filter(item => 
      item.actualizacion_titulo.toLowerCase().includes(busqueda) ||
      item.actualizacion_version.toLowerCase().includes(busqueda)
    );
  }
  
  // Paginación
  const inicio = (paginaActual.value - 1) * itemsPorPagina.value;
  const fin = inicio + itemsPorPagina.value;
  return filtradas.slice(inicio, fin);
});

const totalPaginas = computed(() => {
  const filtradas = actualizaciones.value.filter(item => {
    if (!filtroBusqueda.value) return true;
    const busqueda = filtroBusqueda.value.toLowerCase();
    return item.actualizacion_titulo.toLowerCase().includes(busqueda) ||
           item.actualizacion_version.toLowerCase().includes(busqueda);
  });
  return Math.ceil(filtradas.length / itemsPorPagina.value);
});

// Función para obtener los datos
const obtenerActualizaciones = async () => {
  cargando.value = true;
  error.value = '';

  try {
    const respuesta = await api.get('/admin/actualizaciones');
    actualizaciones.value = respuesta.data.data;
  } catch (err) {
    console.error('Error al cargar la lista:', err);
    error.value = 'No se pudo conectar con el servidor para obtener los datos.';
  } finally {
    cargando.value = false;
  }
};

// Utilidad para formatear fecha
const formatearFecha = (fechaString: string) => {
  if (!fechaString) return 'Sin fecha';
  const fecha = new Date(fechaString);
  return fecha.toLocaleDateString('es-ES', { 
    year: 'numeric', 
    month: 'short', 
    day: 'numeric' 
  });
};

// Mapeo de clases de estado con Bootstrap
const mapearClaseEstado = (estado: string) => {
  const estadoMin = estado.toLowerCase();
  if (estadoMin === 'publicado') return 'bg-success';
  if (estadoMin === 'borrador') return 'bg-warning text-dark';
  if (estadoMin === 'revision') return 'bg-danger';
  return 'bg-secondary';
};

// Obtener ícono según estado
const obtenerIconoEstado = (estado: string) => {
  const estadoMin = estado.toLowerCase();
  if (estadoMin === 'publicado') return 'bi bi-check-circle';
  if (estadoMin === 'borrador') return 'bi bi-pencil-square';
  if (estadoMin === 'revision') return 'bi bi-hourglass-split';
  return 'bi bi-question-circle';
};

// Cambiar página
const cambiarPagina = (pagina: number) => {
  if (pagina >= 1 && pagina <= totalPaginas.value) {
    paginaActual.value = pagina;
  }
};

// Acciones de la tabla
const verDetalles = (item: Version) => {
  console.log('Ver detalles:', item);
  // Implementar lógica de ver detalles
};

const editarActualizacion = (item: Version) => {
  console.log('Editar:', item);
  // Implementar lógica de edición
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
    await obtenerActualizaciones();
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

// Lifecycle
onMounted(() => {
  obtenerActualizaciones();
  
  // Inicializar modal de Bootstrap
  if (modalEliminar.value) {
    modalInstance = new Modal(modalEliminar.value);
  }
});

defineExpose({
  obtenerActualizaciones
});
</script>

<style scoped>
/* Estilos personalizados para complementar Bootstrap */
.table-dark {
  background-color: #0a1c3a !important;
}

.table > :not(caption) > * > * {
  padding: 1rem 0.75rem;
}

.badge {
  font-weight: 500;
  padding: 0.35rem 0.75rem;
  border-radius: 0.375rem;
}

.btn-group-sm > .btn {
  padding: 0.25rem 0.5rem;
  font-size: 0.75rem;
}

.card {
  border-radius: 0.5rem;
}

.card-header {
  border-bottom: none;
}

.table-responsive {
  border-radius: 0.5rem;
}

/* Animación de hover para las filas */
.table-hover tbody tr {
  transition: all 0.2s ease;
}

.table-hover tbody tr:hover {
  background-color: rgba(13, 110, 253, 0.05);
  transform: translateX(2px);
}

/* Estilos para la paginación */
.pagination .page-link {
  color: #0a1c3a;
  border: none;
  margin: 0 2px;
  border-radius: 0.375rem;
}

.pagination .page-item.active .page-link {
  background-color: #0a1c3a;
  border-color: #0a1c3a;
}

.pagination .page-link:hover {
  background-color: #e9ecef;
  transform: translateY(-1px);
}

/* Input de búsqueda personalizado */
.input-group .form-control:focus {
  border-color: #dee2e6;
  box-shadow: none;
}

.input-group .input-group-text {
  background-color: white;
  color: #6c757d;
}

/* Transiciones suaves */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>