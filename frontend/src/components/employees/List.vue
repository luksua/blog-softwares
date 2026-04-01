<template>
  <div class="contenedor-lista">

    <VerActualizacion v-if="idSeleccionado" :id="idSeleccionado" @cerrar="idSeleccionado = null" />

    <div v-else>
      <div v-if="cargando" class="estado-mensaje">
        <p>Cargando actualizaciones...</p>
      </div>

      <div v-else-if="error" class="estado-mensaje error">
        <p>{{ error }}</p>
        <button @click="obtenerActualizaciones(1)">Reintentar</button>
      </div>

      <div v-else-if="actualizaciones.length === 0" class="estado-mensaje vacio">
        <div class="icono-vacio">📄</div>
        <h3>No hay actualizaciones para mostrar</h3>
        <p>Aún no se ha registrado ninguna publicación en el sistema.</p>
      </div>

      <div v-else>
        <div class="lista-feed">
          <div v-for="item in actualizaciones" :key="item.id" class="tarjeta-changelog">

            <div class="tarjeta-header">
              <span class="badge-estado" :class="item.actualizacion_estado">
                {{ item.actualizacion_estado || 'Publicado' }}
              </span>
              <span class="fecha">{{ formatearFecha(item.actualizacion_fecha_publicacion) }}</span>
            </div>

            <div class="imagen">
              <div class="tarjeta-cuerpo">
                <h2 class="titulo-version">
                  Versión {{ item.actualizacion_version || '0.0' }} — {{ item.actualizacion_titulo }}
                </h2>
                <p class="resumen">{{ item.actualizacion_resumen }}</p>
              
                <img v-if="item.actualizacion_imagen_destacada" :src="item.actualizacion_imagen_destacada"
                  alt="Miniatura" />
                <div v-else class="sin-imagen">Sin imagen</div>
              </div>
            </div>

            <div class="tarjeta-pie">
              <div class="tags-container">
                <span class="tag-gris">📸 {{ item.actualizacion_imagenes ? item.actualizacion_imagenes : 0 }}
                  imágenes</span>
              </div>

              <button class="btn-enlace" @click="verDetalle(item.id)">
                Ver más ➔
              </button>
            </div>

          </div>
        </div>

        <div class="paginacion-container mt-4">
          <div class="info-paginacion">
            Mostrando {{ actualizaciones.length }} de {{ totalRegistros }} registros
          </div>

          <nav aria-label="Navegación">
            <ul class="pagination custom-pagination pagination-sm mb-0">
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
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import api from '../../api/api';
import type { Version } from '../../types/version';
import VerActualizacion from './ListVersion.vue'; // Asegúrate que el nombre coincida

const idSeleccionado = ref<number | null>(null);

const verDetalle = (id: number) => {
  idSeleccionado.value = id;
};

const actualizaciones = ref<Version[]>([]);
const cargando = ref(true);
const error = ref('');

const paginaActual = ref(1);
const totalPaginas = ref(1);
const totalRegistros = ref(0);

const obtenerActualizaciones = async (pagina: number = 1) => {
  cargando.value = true;
  error.value = '';

  try {
    const respuesta = await api.get('/employee/actualizaciones', {
      params: { page: pagina }
    });

    actualizaciones.value = respuesta.data.data;
    paginaActual.value = respuesta.data.current_page;
    totalPaginas.value = respuesta.data.last_page;
    totalRegistros.value = respuesta.data.total;

  } catch (err) {
    console.error('Error al cargar la lista:', err);
    error.value = 'Error al conectar con el servidor.';
  } finally {
    cargando.value = false;
  }
};

const cambiarPagina = (pagina: number) => {
  if (pagina >= 1 && pagina <= totalPaginas.value) {
    obtenerActualizaciones(pagina);
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }
};

const formatearFecha = (fechaString: string) => {
  if (!fechaString) return 'Sin fecha';
  return new Date(fechaString).toLocaleDateString('es-ES', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  });
};

onMounted(() => {
  obtenerActualizaciones(1);
});
</script>

<style scoped>
.contenedor-lista {
  max-width: 2000px;
  /* Reducido para imitar la columna izquierda de la imagen */
  margin: 0 auto;
  padding: 20px;
  font-family: system-ui, -apple-system, sans-serif;
}

.estado-mensaje {
  text-align: center;
  padding: 40px;
  background-color: #f9f9f9;
  border-radius: 8px;
  color: #666;
}

.estado-mensaje.error {
  color: #d32f2f;
  background-color: #ffebee;
}

/* --- NUEVO ESTILO: RELEASE FEED --- */
.lista-feed {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.tarjeta-changelog {
  border: 1px solid #eaeaea;
  border-radius: 8px;
  padding: 24px;
  background-color: white;
  transition: box-shadow 0.2s;
}

.tarjeta-changelog:hover {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
}

.tarjeta-header {
  display: flex;
  align-items: center;
  gap: 15px;
  margin-bottom: 12px;
}

.badge-estado {
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
  padding: 4px 10px;
  border-radius: 4px;
  background-color: #e8f5e9;
  /* Estilo STABLE (Verde claro) */
  color: #2e7d32;
  letter-spacing: 0.5px;
}

/* Colores dinámicos por si tienes varios estados (opcional) */
.badge-estado.revision {
  background-color: #fff3e0;
  color: #ef6c00;
}

.badge-estado.borrador {
  background-color: #e3f2fd;
  color: #1565c0;
}

.fecha {
  font-size: 0.9rem;
  color: #888;
}

.titulo-version {
  font-size: 1.4rem;
  font-weight: 700;
  color: #1a1a1a;
  margin: 0 0 10px 0;
}

.resumen {
  font-size: 1rem;
  color: #555;
  line-height: 1.6;
  margin: 0 0 20px 0;
}

.tarjeta-pie {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.tags-container {
  display: flex;
  gap: 10px;
}

.tag-gris {
  background-color: #f4f5f7;
  color: #4a5568;
  font-size: 0.75rem;
  font-weight: 600;
  padding: 4px 10px;
  border-radius: 4px;
}

.btn-enlace {
  background: none;
  border: none;
  color: #1a1a1a;
  font-weight: 700;
  font-size: 0.95rem;
  cursor: pointer;
  display: flex;
  align-items: center;
  transition: color 0.2s;
}

.btn-enlace:hover {
  color: #209bb9;
  /* Azul al pasar el ratón */
}

/* --- ESTILOS DE PAGINACIÓN --- */
.paginacion-container {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px 0;
  border-top: 1px solid #eee;
}

.info-paginacion {
  color: #666;
  font-size: 0.9rem;
}

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
</style>
