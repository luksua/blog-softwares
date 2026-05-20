<template>
  <div class="contenedor-guardados">
    <div class="supervision-hero">
      <div>
        <span class="eyebrow">Bookmarks</span>
        <h2>Registros guardados</h2>
        <p>Consulta las actualizaciones que marcaste para revisar después.</p>
      </div>

      <!-- <div class="hero-badge">
        <i class="bi bi-shield-check"></i>
        <span>Supervisor</span>
      </div> -->
    </div>

    <div v-if="cargando" class="estado-mensaje">
      <div class="spinner-border text-primary mb-3" role="status">
        <span class="visually-hidden">Cargando...</span>
      </div>
      <p>Cargando guardados...</p>
    </div>

    <div v-else-if="error" class="estado-mensaje error">
      <div class="estado-icono">⚠️</div>
      <h3>No se pudieron cargar tus guardados</h3>
      <p>{{ error }}</p>
      <button class="btn-retry" type="button" @click="obtenerGuardados">
        Reintentar
      </button>
    </div>

    <div v-else-if="idsGuardados.length === 0" class="estado-mensaje vacio">
      <div class="estado-icono">🔖</div>
      <h3>No tienes actualizaciones guardadas</h3>
      <p>
        Cuando encuentres una publicación importante, presiona el ícono de guardar
        para verla luego desde este apartado.
      </p>

      <button class="btn-primary-custom" type="button" @click="volverAlBlog">
        Ir al blog
      </button>
    </div>

    <div v-else-if="actualizaciones.length === 0" class="estado-mensaje vacio">
      <div class="estado-icono">📭</div>
      <h3>Tus guardados ya no están disponibles</h3>
      <p>
        Es posible que esas actualizaciones hayan sido eliminadas, inactivadas o ya
        no estén publicadas.
      </p>

      <button class="btn-primary-custom" type="button" @click="confirmarLimpiarGuardados">
        Limpiar guardados
      </button>
    </div>

    <div v-else>
      <div class="resumen-guardados">
        <span>
          <strong>{{ actualizaciones.length }}</strong>
          {{ actualizaciones.length === 1 ? 'actualización guardada' : 'actualizaciones guardadas' }}
        </span>

        <button class="btn-limpiar" type="button" @click="confirmarLimpiarGuardados">
          Limpiar todo
        </button>
      </div>

      <div class="grid-guardados">
        <article v-for="item in actualizaciones" :key="item.id" class="tarjeta-guardado">
          <div class="tarjeta-imagen" @click="verDetalle(item.id)">
            <img v-if="item.actualizacion_imagen_destacada" :src="obtenerUrlImagen(item.actualizacion_imagen_destacada)"
              :alt="item.actualizacion_titulo" loading="lazy" />

            <div v-else class="sin-imagen">
              <span>🖼️</span>
              <p>Sin imagen destacada</p>
            </div>
          </div>

          <div class="tarjeta-cuerpo" @click="verDetalle(item.id)">
            <div class="metadatos">
              <span>{{ formatearFecha(item.actualizacion_fecha_publicacion) }}</span>
              <span>v{{ item.actualizacion_version || '0.0' }}</span>
            </div>

            <h3>{{ item.actualizacion_titulo }}</h3>

            <p>
              {{ item.actualizacion_resumen || 'Sin resumen disponible.' }}
            </p>
          </div>

          <div class="tarjeta-footer">
            <div class="tags">
              <span>
                {{ item.area_servicio?.area_servicio_nombre || 'Sin área' }}
              </span>

              <span>
                {{ item.categoria?.categoria_actualizacion_nombre || 'Sin categoría' }}
              </span>
            </div>

            <div class="acciones">
              <button class="btn-quitar" type="button" title="Quitar de guardados"
                @click.stop="quitarGuardado(item.id)">
                <i class="bi bi-bookmark-x-fill fs-5"></i>
              </button>

              <button class="btn-ver" type="button" @click.stop="verDetalle(item.id)">
                Ver más
                <i class="bi bi-arrow-right"></i>
              </button>
            </div>
          </div>
        </article>
      </div>

      <div v-if="guardadosNoEncontrados > 0" class="alerta-info">
        {{ guardadosNoEncontrados }}
        {{ guardadosNoEncontrados === 1 ? 'guardado no pudo cargarse' : 'guardados no pudieron cargarse' }}.
        Puede que ya no estén disponibles.
      </div>
    </div>

    <div class="modal fade" id="modalLimpiarGuardados" tabindex="-1" aria-labelledby="modalLimpiarGuardadosLabel"
      aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="modalLimpiarGuardadosLabel">
              ¿Deseas limpiar todos tus guardados?
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>

          <div class="modal-body">
            <p>
              Al aceptar, se eliminarán todas las actualizaciones guardadas de tu cuenta.
            </p>
            <strong class="modal-item-title">
              Esta acción no eliminará las publicaciones originales.
            </strong>
          </div>

          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
              Cancelar
            </button>

            <button type="button" class="btn btn-danger" :disabled="limpiandoGuardados" @click="limpiarGuardados">
              {{ limpiandoGuardados ? 'Limpiando...' : 'Aceptar' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, nextTick } from 'vue'
import { useRouter } from 'vue-router'
import { obtenerBookmarks, quitarBookmark, limpiarBookmarks } from '../../../api/bookmarks'
import type { Version } from '../../../types/version'
import { Modal } from 'bootstrap'
import { toast } from 'vue-sonner'

const router = useRouter()

const actualizaciones = ref<Version[]>([])
const idsGuardados = ref<number[]>([])
const cargando = ref(false)
const error = ref('')
const guardadosNoEncontrados = ref(0)
const limpiandoGuardados = ref(false)

const sincronizarIdsDesdeActualizaciones = () => {
  idsGuardados.value = actualizaciones.value
    .map((item) => Number(item.id))
    .filter((id) => Number.isFinite(id))
}

const obtenerGuardados = async () => {
  cargando.value = true
  error.value = ''
  guardadosNoEncontrados.value = 0

  try {
    actualizaciones.value = await obtenerBookmarks()
    sincronizarIdsDesdeActualizaciones()
  } catch (err) {
    console.error(err)
    error.value = 'Error al conectar con el servidor.'
  } finally {
    cargando.value = false
  }
}

const quitarGuardado = async (id: number) => {
  const idNormalizado = Number(id)

  if (!Number.isFinite(idNormalizado)) {
    return
  }

  try {
    await quitarBookmark(idNormalizado)

    actualizaciones.value = actualizaciones.value.filter(
      (item) => Number(item.id) !== idNormalizado
    )

    sincronizarIdsDesdeActualizaciones()
    window.dispatchEvent(new Event('bookmarks-updated'))
    toast.success('¡Se quitó de tus guardados!')
  } catch (err) {
    console.error('Error al quitar guardado:', err)
    error.value = 'No se pudo quitar el guardado.'
  }
}

const limpiarFondoModal = () => {
  const backdrops = document.querySelectorAll('.modal-backdrop')
  backdrops.forEach((backdrop) => backdrop.remove())
  document.body.classList.remove('modal-open')
  document.body.style.overflow = ''
  document.body.style.paddingRight = ''
}

const obtenerModalLimpiarGuardados = () => {
  const modalElement = document.getElementById('modalLimpiarGuardados')

  if (!modalElement) {
    return null
  }

  return Modal.getInstance(modalElement) || new Modal(modalElement)
}

const limpiarGuardados = async () => {
  const modalInstance = obtenerModalLimpiarGuardados()
  limpiandoGuardados.value = true

  try {
    await limpiarBookmarks()

    actualizaciones.value = []
    idsGuardados.value = []
    guardadosNoEncontrados.value = 0
    window.dispatchEvent(new Event('bookmarks-updated'))

    if (modalInstance) modalInstance.hide()
    limpiarFondoModal()

    toast.info('¡Todos tus guardados fueron eliminados!')
  } catch (err) {
    console.error('Error al limpiar guardados:', err)
    error.value = 'No se pudieron limpiar tus guardados.'

    if (modalInstance) modalInstance.hide()
    limpiarFondoModal()
  } finally {
    limpiandoGuardados.value = false
  }
}

const confirmarLimpiarGuardados = async () => {
  await nextTick()

  const modalInstance = obtenerModalLimpiarGuardados()

  if (modalInstance) {
    modalInstance.show()
  }
}

const volverAlBlog = () => {
  if (router.hasRoute('inicio')) {
    router.push({ name: 'inicio' })
    return
  }

  router.push('/')
}

const verDetalle = (id: number) => {
  if (router.hasRoute('actualizaciones-show')) {
    router.push({
      name: 'actualizaciones-show',
      params: { id },
    })
    return
  }

  if (router.hasRoute('employee-actualizaciones-show')) {
    router.push({
      name: 'employee-actualizaciones-show',
      params: { id },
    })
    return
  }

  router.push(`/employee/actualizaciones/${id}`)
}

const obtenerUrlImagen = (ruta: string) => {
  if (!ruta) {
    return ''
  }

  if (ruta.startsWith('http')) {
    return ruta
  }

  const backendUrl = import.meta.env.VITE_BACKEND_URL || 'http://localhost:8000'

  if (ruta.startsWith('/storage/')) {
    return `${backendUrl}${ruta}`
  }

  if (ruta.startsWith('storage/')) {
    return `${backendUrl}/${ruta}`
  }

  return `${backendUrl}/storage/${ruta}`
}

const formatearFecha = (fechaString: string) => {
  if (!fechaString) {
    return 'Sin fecha'
  }

  const fecha = new Date(fechaString)

  if (Number.isNaN(fecha.getTime())) {
    return 'Sin fecha'
  }

  const str = fecha.toLocaleDateString('es-ES', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  })

  return str.charAt(0).toUpperCase() + str.slice(1)
}

onMounted(() => {
  obtenerGuardados()
})
</script>

<style scoped>
:root {
  --primary: #077e9d;
  --secondary: #025b7d;
  --warning: #fcbb1c;
  --shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.08);
  --shadow-md: 0 4px 16px rgba(0, 0, 0, 0.12);
  --transition: all 0.3s ease;
}

.contenedor-guardados {
  max-width: 1400px;
  margin: 0 auto;
  padding: 0 16px;
}

.cabecera {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 16px;
  margin-bottom: 24px;
}

.cabecera h2 {
  margin: 0 0 6px 0;
  font-size: 1.75rem;
  color: #1f2937;
}

.cabecera p {
  margin: 0;
  color: #6b7280;
  font-size: 0.95rem;
}

.estado-mensaje {
  text-align: center;
  padding: 56px 24px;
  background: white;
  border-radius: 20px;
  box-shadow: var(--shadow-sm);
  color: #6b7280;
  /* border-top: 3px solid var(--warning); */
}

.estado-mensaje.error {
  border-top-color: #ef4444;
}

.estado-icono {
  font-size: 48px;
  margin-bottom: 16px;
}

.estado-mensaje h3 {
  margin: 0 0 8px 0;
  color: #1f2937;
  font-size: 1.3rem;
}

.estado-mensaje p {
  max-width: 560px;
  margin: 0 auto 20px auto;
  line-height: 1.6;
}

.resumen-guardados {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 12px;
  margin-bottom: 18px;
  padding: 14px 18px;
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 16px;
  box-shadow: var(--shadow-sm);
  color: #4b5563;
}

.grid-guardados {
  display: grid;
  grid-template-columns: repeat(1, minmax(0, 1fr));
  gap: 18px;
}

.tarjeta-guardado {
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 18px;
  overflow: hidden;
  box-shadow: var(--shadow-sm);
  display: flex;
  flex-direction: column;
  transition: var(--transition);
}

.tarjeta-guardado:hover {
  transform: translateY(-4px);
  box-shadow: var(--shadow-md);
}

.tarjeta-imagen {
  width: 100%;
  background: #f3f4f6;
  cursor: pointer;
}

.tarjeta-imagen img {
  width: 100%;
  aspect-ratio: 22 / 9;
  object-fit: cover;
  object-position: center;
  display: block;
}

.sin-imagen {
  width: 100%;
  aspect-ratio: 22 / 9;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  color: #9ca3af;
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
}

.sin-imagen span {
  font-size: 32px;
  margin-bottom: 8px;
}

.sin-imagen p {
  margin: 0;
  font-size: 0.85rem;
}

.tarjeta-cuerpo {
  padding: 16px;
  flex: 1;
  cursor: pointer;
}

.metadatos {
  display: flex;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 10px;
  font-size: 0.82rem;
  color: #6b7280;
}

.metadatos span:last-child {
  color: var(--primary);
  border: 1px solid #e1e7f0;
  border-radius: 999px;
  padding: 2px 9px;
  font-weight: 700;
  font-family: 'Courier New', monospace;
}

.tarjeta-cuerpo h3 {
  margin: 0 0 8px 0;
  color: #111827;
  font-size: 1.15rem;
  font-weight: 800;
  line-height: 1.35;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.tarjeta-cuerpo p {
  margin: 0;
  color: #4b5563;
  font-size: 0.92rem;
  line-height: 1.55;
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.tarjeta-footer {
  border-top: 1px solid #f0f2f5;
  padding: 12px 16px;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.tags {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.tags span {
  background-color: #f4f5f7;
  color: #4a5568;
  font-size: 0.72rem;
  font-weight: 700;
  padding: 5px 11px;
  border-radius: 999px;
}

.acciones {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
}

.btn-primary-custom,
.btn-secundario,
.btn-retry,
.btn-limpiar,
.btn-quitar,
.btn-ver {
  border: none;
  border-radius: 12px;
  font-weight: 700;
  cursor: pointer;
  transition: var(--transition);
}

.btn-primary-custom,
.btn-retry {
  padding: 10px 22px;
  background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
  color: white;
}

.btn-secundario {
  padding: 10px 18px;
  background: #f3f4f6;
  color: #374151;
  border: 1px solid #e5e7eb;
  white-space: nowrap;
}

.btn-limpiar {
  padding: 8px 14px;
  background: #fef2f2;
  color: #b91c1c;
  border: 1px solid #ffb4b4;
}

.btn-quitar {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 8px 12px;
  background: #fff7ed;
  color: #a70000;
  border: 1px solid #ff9898;
}

.btn-ver {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 8px 12px;
  background: rgba(7, 126, 157, 0.08);
  color: var(--primary);
}

.btn-primary-custom:hover,
.btn-retry:hover,
.btn-secundario:hover,
.btn-limpiar:hover,
.btn-quitar:hover,
.btn-ver:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow-sm);
}

.alerta-info {
  margin-top: 20px;
  padding: 12px 16px;
  background: #eff6ff;
  color: #1d4ed8;
  border: 1px solid #bfdbfe;
  border-radius: 14px;
  font-size: 0.9rem;
}

@media (min-width: 640px) {
  .grid-guardados {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (min-width: 1024px) {
  .grid-guardados {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }
}

@media (min-width: 1280px) {
  .grid-guardados {
    grid-template-columns: repeat(4, minmax(0, 1fr));
  }
}

@media (max-width: 640px) {
  .contenedor-guardados {
    padding: 0;
  }

  .cabecera {
    flex-direction: column;
    align-items: stretch;
  }

  .resumen-guardados {
    flex-direction: column;
    align-items: stretch;
  }

  .btn-limpiar,
  .btn-secundario {
    width: 100%;
  }

  .acciones {
    flex-direction: column;
    align-items: stretch;
  }

  .btn-quitar,
  .btn-ver {
    width: 100%;
    justify-content: center;
  }
}

.supervision-hero {
  display: flex;
  justify-content: space-between;
  gap: 24px;
  align-items: center;
  max-width: 1500px;
  margin: 0 auto 20px;
  padding: 28px;
  border-radius: 24px;
  background:
    radial-gradient(circle at top right, rgba(252, 187, 28, 0.24), transparent 32%),
    linear-gradient(135deg, #073b4c 0%, var(--secondary) 100%);
  color: white;
  box-shadow: 0 14px 32px rgba(2, 91, 125, 0.22);
}

.supervision-hero h2 {
  margin: 6px 0 10px;
  font-size: clamp(1.7rem, 3vw, 2.6rem);
  font-weight: 800;
}

.supervision-hero p {
  max-width: 760px;
  margin: 0;
  color: rgba(255, 255, 255, 0.86);
  font-size: 1rem;
}

.eyebrow {
  display: inline-flex;
  padding: 6px 12px;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.14);
  color: #fff7d6;
  font-size: 0.8rem;
  font-weight: 800;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

.hero-badge {
  display: inline-flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-width: 130px;
  height: 120px;
  border-radius: 24px;
  background: rgba(255, 255, 255, 0.14);
  border: 1px solid rgba(255, 255, 255, 0.18);
  backdrop-filter: blur(8px);
  font-weight: 800;
}

.hero-badge i {
  font-size: 2rem;
  color: var(--warning);
  margin-bottom: 8px;
}

@media (max-width: 900px) {
  .supervision-hero {
    align-items: flex-start;
    flex-direction: column;
  }

  .hero-badge {
    width: 100%;
    height: auto;
    flex-direction: row;
    gap: 10px;
    padding: 14px;
  }

  .hero-badge i {
    margin-bottom: 0;
  }

  .supervision-resumen {
    grid-template-columns: 1fr;
  }
}
</style>