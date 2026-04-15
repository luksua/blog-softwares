<template>
  <div class="contenedor-lista">
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

            <div class="tarjeta-cuerpo pt-3" @click="verDetalle(item.id)">
              <div class="metadatos-top">
                <span class="fecha">{{ formatearFecha(item.actualizacion_fecha_publicacion) }}</span>
                <span class="separador">|</span>
                <span class="version-number">v{{ item.actualizacion_version || '0.0' }}</span>
                <!-- <span :class="['badge-estado', item.actualizacion_estado || 'publicado']">
                  {{ item.actualizacion_estado || 'Publicado' }}
                </span> -->
              </div>

              <h2 class="titulo-version">
                {{ item.actualizacion_titulo }}
              </h2>

              <p class="resumen">{{ item.actualizacion_resumen }}</p>
            </div>

            <div class="tarjeta-pie">
              <div class="tags-container">
                <span class="tag-gris">
                  {{ item.area_servicio.area_servicio_nombre }}
                </span>
              </div>

              <button class="btn-enlace" @click="verDetalle(item.id)">
                Ver más ➔
              </button>
            </div>
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
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '../../api/api'
import type { Version } from '../../types/version'

const router = useRouter()

const obtenerUrlImagen = (ruta: string) => {
  if (!ruta) return ''
  if (ruta.startsWith('http')) return ruta

  const baseUrl = import.meta.env.VITE_API_URL || 'http://localhost:8000'
  return `${baseUrl}/storage/${ruta}`
}

const actualizaciones = ref<Version[]>([])
const cargando = ref(true)
const error = ref('')

const paginaActual = ref(1)
const totalPaginas = ref(1)
const totalRegistros = ref(0)

const verDetalle = (id: number) => {
  router.push({
    name: 'employee-actualizaciones-show',
    params: { id },
  });
};

const obtenerActualizaciones = async (pagina: number = 1) => {
  cargando.value = true
  error.value = ''

  try {
    const respuesta = await api.get('/employee/actualizaciones', {
      params: { page: pagina }
    })

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

const cambiarPagina = (pagina: number) => {
  if (pagina >= 1 && pagina <= totalPaginas.value) {
    obtenerActualizaciones(pagina)
    window.scrollTo({ top: 0, behavior: 'smooth' })
  }
}

const formatearFecha = (fechaString: string) => {
  if (!fechaString) return 'Sin fecha'

  return new Date(fechaString).toLocaleDateString('es-ES', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

onMounted(() => {
  obtenerActualizaciones(1)
})
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

/* Tarjeta */
.tarjeta-changelog {
  border: 1px solid #eaeaea;
  border-radius: 16px;
  background-color: white;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

.tarjeta-changelog:hover {
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
  transform: translateY(-2px);
}

/* Header de la tarjeta modificado para la imagen arriba */
.tarjeta-header {
  padding: 0; /* Sin padding para que la imagen toque los bordes */
  display: block;
}

/* Contenedor de la imagen arreglado */
.imagen-container {
  margin-top: 0;
  border-radius: 0;
  overflow: hidden;
  width: 100%;
}

.imagen-destacada {
  width: 100%;
  aspect-ratio: 22 / 8;
  object-fit: cover;
  display: block;
  object-position: center;
  transition: all 0.3s ease;
}

.imagen-container:hover .imagen-destacada {
  transform: scale(1.02);
}

/* Estado cuando no hay imagen */
.sin-imagen {
  margin-top: 0;
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
  border-radius: 0;
  border-bottom: 1px solid #e1e7f0;
  color: #9ca3af;
  width: 100%;
  aspect-ratio: 22 / 8;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  text-align: center;
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

/* Cuerpo */
.tarjeta-cuerpo {
  padding: 10px 15px;
  flex-grow: 1;
  display: flex;
  flex-direction: column;
  cursor: pointer;
}

/* Metadatos como en la imagen (Fecha | Versión | Estado) */
.metadatos-top {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 16px;
  flex-wrap: wrap;
}

.separador {
  color: #a0aec0;
  font-weight: 300;
}

.fecha {
  font-size: 0.95rem;
  color: #888;
  font-weight: 500;
}

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

.badge-estado {
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
  padding: 4px 10px;
  border-radius: 4px;
  background-color: #e8f5e9;
  color: #2e7d32;
  letter-spacing: 0.5px;
}

.badge-estado.revision {
  background-color: #fff3e0;
  color: #ef6c00;
}

.badge-estado.borrador {
  background-color: #e3f2fd;
  color: #1565c0;
}

.badge-estado.publicado {
  background-color: #e6f7e9;
  color: #2e7d32;
}

.badge-estado.inactivo {
  background-color: #e5e7eb;
  color: #374151;
}

.titulo-version {
  font-size: 1.4rem;
  font-weight: 700;
  color: #1a1a1a;
  margin: 0 0 10px 0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.resumen {
  font-size: 1rem;
  color: #555;
  line-height: 1.6;
  margin: 0 0 20px 0;
  display: -webkit-box;
  -webkit-box-orient: vertical;
  line-clamp: 3;
  -webkit-line-clamp: 2;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* Pie mejorado */
.tarjeta-pie {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 8px 24px;
  margin-top: auto;
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