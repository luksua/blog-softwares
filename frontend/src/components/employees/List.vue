<template>
  <div class="contenedor-lista">

    <VerActualizacion v-if="idSeleccionado" :id="idSeleccionado" @cerrar="idSeleccionado = null" />

    <div v-else>
      <div v-if="cargando" class="estado-mensaje">
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

      <div v-else-if="actualizaciones.length === 0" class="estado-mensaje vacio">
        <div class="empty-icon">📭</div>
        <h3>No hay actualizaciones para mostrar</h3>
        <p>Aún no se ha registrado ninguna publicación en el sistema.</p>
      </div>

      <div v-else>
        <div class="lista-feed">
          <div v-for="item in actualizaciones" :key="item.id" class="tarjeta-changelog">

            <div class="tarjeta-header">
              <div class="header-left">
                <span :class="['badge-estado', item.actualizacion_estado || 'publicado']">
                  {{ item.actualizacion_estado || 'Publicado' }}
                </span>
                <span class="version-number">
                  v{{ item.actualizacion_version || '0.0' }}
                </span>
              </div>
              <span class="fecha">{{ formatearFecha(item.actualizacion_fecha_publicacion) }}</span>
            </div>

            <div class="tarjeta-cuerpo">
              <h2 class="titulo-version">
                {{ item.actualizacion_titulo }}
              </h2>
              <p class="resumen">{{ item.actualizacion_resumen }}</p>

              <div v-if="item.actualizacion_imagen_destacada" class="imagen-container">
                <img :src="item.actualizacion_imagen_destacada" alt="Imagen destacada" class="imagen-destacada" />
              </div>
              <div v-else class="sin-imagen">
                <span>🖼️</span>
                <p>Sin imagen destacada</p>
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

        <div class="paginacion-container">
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
import VerActualizacion from './ListVersion.vue';

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
  max-width: 1200px;
  margin: 0 auto;
  padding: 20px;
  font-family: system-ui, -apple-system, sans-serif;
}

.estado-mensaje {
  text-align: center;
  padding: 60px 40px;
  background: white;
  border-radius: 20px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  color: #6b7280;
}

.estado-mensaje.error {
  border-top: 3px solid #ef4444;
}

.estado-mensaje.vacio {
  border-top: 3px solid #FCBB1C;
}

.error-icon,
.empty-icon {
  font-size: 48px;
  margin-bottom: 16px;
}

.btn-retry {
  margin-top: 20px;
  padding: 10px 24px;
  background: linear-gradient(135deg, #077E9D 0%, #025B7D 100%);
  color: white;
  border: none;
  border-radius: 12px;
  cursor: pointer;
  font-weight: 500;
  transition: all 0.3s ease;
}

.btn-retry:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

/* Lista feed */
.lista-feed {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

/* Tarjeta */
.tarjeta-changelog {
  border: 1px solid #eaeaea;
  border-radius: 16px;
  background-color: white;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  overflow: hidden;
}

.tarjeta-changelog:hover {
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
  transform: translateY(-2px);
}

/* Header mejorado */
.tarjeta-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 20px 24px;
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
  border-bottom: 1px solid #eaeaea;
}

.header-left {
  display: flex;
  align-items: center;
  gap: 12px;
}

.badge-estado {
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
  padding: 4px 10px;
  border-radius: 20px;
  letter-spacing: 0.5px;
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


/* Estados */
.badge-estado.publicado {
  background-color: #e6f7e9;
  color: #2e7d32;
}

.badge-estado.revision {
  background-color: #fff3e0;
  color: #ef6c00;
}

.badge-estado.borrador {
  background-color: #e3f2fd;
  color: #1565c0;
}

.badge-estado.inactivo {
  background-color: #e5e7eb;
  color: #374151;
}

/* Número de versión */
.version-number {
  display: inline-block;
  padding: 4px 10px;
  background: white;
  color: #077E9D;
  border-radius: 20px;
  font-size: 0.75rem;
  font-weight: 600;
  font-family: 'Courier New', monospace;
  border: 1px solid #e1e7f0;
}

.fecha {
  font-size: 0.85rem;
  color: #888;
}

/* Cuerpo */
.tarjeta-cuerpo {
  padding: 24px;
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

/* Imagen destacada */
.imagen-container {
  margin-top: 20px;
  border-radius: 12px;
  overflow: hidden;
  transition: all 0.3s ease;
}

.imagen-container:hover {
  transform: scale(1.02);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.imagen-destacada {
  width: 100%;
  height: auto;
  max-height: 400px;
  object-fit: cover;
  display: block;
}

.sin-imagen {
  margin-top: 20px;
  padding: 40px;
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
  border-radius: 12px;
  text-align: center;
  color: #9ca3af;
  border: 2px dashed #e1e7f0;
}

.sin-imagen span {
  font-size: 48px;
  display: block;
  margin-bottom: 12px;
}

.sin-imagen p {
  margin: 0;
  font-size: 0.9rem;
}

/* Pie mejorado */
.tarjeta-pie {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px 24px;
  background: #fafbfc;
  border-top: 1px solid #eaeaea;
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
  padding: 6px 12px;
  border-radius: 20px;
  transition: all 0.3s ease;
}

.tag-gris:hover {
  background-color: #077E9D;
  color: white;
  transform: translateY(-1px);
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
  gap: 8px;
  padding: 8px 16px;
  border-radius: 8px;
  transition: all 0.3s ease;
}

.btn-enlace:hover {
  color: #077E9D;
  background: rgba(7, 126, 157, 0.1);
  transform: translateX(4px);
}

/* Paginación */
.paginacion-container {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px 0;
  margin-top: 20px;
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
  border-radius: 8px;
  transition: all 0.3s ease;
}

.custom-pagination .page-item.active .page-link {
  background: linear-gradient(135deg, #077E9D 0%, #025B7D 100%);
  color: white;
}

.custom-pagination .page-link:hover:not(.active) {
  background-color: #f2f4f7;
  transform: translateY(-1px);
}

/* Responsive */
@media (max-width: 768px) {
  .contenedor-lista {
    padding: 16px;
  }

  .tarjeta-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 12px;
  }

  .header-left {
    width: 100%;
    justify-content: space-between;
  }

  .tarjeta-pie {
    flex-direction: column;
    gap: 16px;
    align-items: stretch;
  }

  .tags-container {
    justify-content: center;
  }

  .btn-enlace {
    justify-content: center;
  }

  .paginacion-container {
    flex-direction: column;
    gap: 16px;
    text-align: center;
  }

  .titulo-version {
    font-size: 1.2rem;
  }
}

@media (max-width: 480px) {
  .tarjeta-cuerpo {
    padding: 16px;
  }

  .badge-estado {
    font-size: 0.7rem;
  }

  .version-number {
    font-size: 0.7rem;
  }
}
</style>