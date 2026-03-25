<template>
  <div class="contenedor-lista-tabla container-fluid">
    <div v-if="cargando" class="estado-mensaje">
      <p>Cargando actualizaciones...</p>
    </div>

    <div v-else-if="error" class="estado-mensaje error">
      <p>{{ error }}</p>
      <button @click="obtenerActualizaciones">Reintentar</button>
    </div>

    <div v-else-if="actualizaciones.length === 0" class="estado-mensaje vacio">
      <div class="icono-vacio">📄</div>
      <h3>No hay actualizaciones para mostrar</h3>
      <p>Aún no se ha registrado ninguna actualización en el sistema.</p>
    </div>

    <div v-else class="table-container shadow-sm rounded">
      <table class="base-table">
        <thead>
          <tr>
            <th class="col-titulo">TÍTULO</th>
            <th class="col-titulo">VERSION</th>
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
              {{ item.actualizacion_version }}
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
                <button title="Editar" class="btn-icon">✎</button>
                <button title="Ver" class="btn-icon">👁</button>
                <button title="Eliminar" class="btn-icon">🗑</button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import api from '../../api/api';
// Asegúrate de que el tipo 'Version' tenga titulo, resumen, estado, fecha_publicacion
import type { Version } from '../../types/version'

// 2. Estados reactivos (se mantienen)
const actualizaciones = ref<Version[]>([]);
const cargando = ref(true);
const error = ref('');

// 3. Función para obtener los datos (se mantiene)
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

// 4. Utilidad para formatear fecha (se mantiene)
const formatearFecha = (fechaString: string) => {
  if (!fechaString) return 'Sin fecha';
  // Formateador para Oct 12, 2023
  const opciones: Intl.DateTimeFormatOptions = { year: 'numeric', month: 'short', day: '2-digit' };
  const fechaStr = new Date(fechaString).toLocaleDateString('en-US', opciones);
  // Pequeño hack para capitalizar el mes (ej: oct -> Oct)
  return fechaStr.charAt(0).toUpperCase() + fechaStr.slice(1);
};

// NUEVA UTILIDAD: Mapea tus estados a las clases coloreadas de la imagen
const mapearClaseEstado = (estado: string) => {
  const estadoMin = estado.toLowerCase();
  if (estadoMin === 'publicado') return 'estado-green'; // Como Stable
  if (estadoMin === 'borrador') return 'estado-yellow'; // Como Beta
  if (estadoMin === 'revision') return 'estado-red';    // Como Development
  return 'estado-gray'; // Como Deprecated u otros
};

// 5. Ejecutar la función automáticamente al cargar el componente (se mantiene)
onMounted(() => {
  obtenerActualizaciones();
});

defineExpose({
  obtenerActualizaciones
});
</script>

<style scoped>
/* Contenedor General */
.contenedor-lista-tabla {
  width: 100%;
  max-width: 1100px;
  margin: 0 auto;
  padding: 20px;
  font-family: 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
}

/* Estados de carga/vacío */
.estado-mensaje {
  text-align: center;
  padding: 40px;
  background-color: #f9fafb;
  border-radius: 8px;
  color: #6b7280;
  border: 1px dashed #e5e7eb;
}

/* Estilos de la Tabla Principal (Contenedor con sombra y bordes redondeados) */
.table-container {
  background-color: white;
  overflow: hidden;
  /* Importante para los bordes redondeados */
  border: 1px solid #e1e7f0;
}

.base-table {
  width: 100%;
  border-collapse: collapse;
}

/* Encabezado de Tabla Oscuro (Estilo image_2.png) */
.base-table thead tr {
  background-color: #0d2141;
  /* Color azul muy oscuro de la imagen */
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

/* Filas de la Tabla */
.base-table tbody tr {
  border-bottom: 1px solid #e1e7f0;
  /* Borde suave entre filas */
  transition: background-color 0.2s;
}

/* Las filas se vuelven blancas y sin sombreado al hacer hover, manteniendo la limpieza */
.base-table tbody tr:hover {
  background-color: #f9fafb;
}

.base-table td {
  padding: 12px 20px;
  font-size: 0.95rem;
  color: #555c68;
  vertical-align: middle;
}

/* Definición de anchos de columna para mejor diseño */
.col-titulo {
  width: 30%;
}

.col-resumen {
  width: 40%;
}

.col-fecha {
  width: 12%;
}

.col-estado {
  width: 12%;
}

.col-acciones {
  width: 6%;
  text-align: right;
}

/* Estilos de Celda Específicos */
.cell-titulo {
  font-weight: 600;
  /* Texto en negrita para el título */
  color: #1a202c;
}

.cell-resumen {
  color: #6b7280;
  white-space: nowrap;
  /* Evita que el texto salte de línea */
  overflow: hidden;
  text-overflow: ellipsis;
  /* Añade ... si es muy largo */
}

/* Badges de Estado Coloreados (Estilo image_2.png) */
.badge-estado {
  display: inline-block;
  padding: 4px 10px;
  border-radius: 20px;
  /* Bordes muy redondeados */
  font-size: 0.8rem;
  font-weight: 600;
  text-transform: capitalize;
  /* Capitaliza la primera letra */
}

/* Mapeo de Colores del Estado de la imagen */
.estado-green {
  background-color: #e6f7e9;
  color: #2e7d32;
}

/* Como Stable */
.estado-yellow {
  background-color: #fef8e3;
  color: #f9a825;
}

/* Como Beta */
.estado-red {
  background-color: #fdeaea;
  color: #d32f2f;
}

/* Como Development */
.estado-gray {
  background-color: #f2f4f7;
  color: #5f6671;
}

/* Como Deprecated u otros */

/* Columna de Acciones (Íconos) */
.cell-acciones {
  text-align: right;
}

.icon-group {
  display: flex;
  gap: 15px;
  /* Espacio entre íconos */
  justify-content: flex-end;
}

.btn-icon {
  background: none;
  border: none;
  padding: 0;
  color: #9ca3af;
  /* Color gris suave */
  font-size: 1.1rem;
  cursor: pointer;
  transition: color 0.2s;
}

.btn-icon:hover {
  color: #4b5563;
  /* Gris más oscuro en hover */
}

/* Sombra y bordes redondeados globales (utilidades sencillas si no usas Tailwind/Bootstrap) */
.shadow-sm {
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
}

.rounded {
  border-radius: 6px;
}
</style>