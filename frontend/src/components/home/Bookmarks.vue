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

        <!-- reemplaza el <article v-for="item in actualizaciones"...> completo -->
        <div v-for="item in actualizaciones" :key="item.id">
          <div class="tarjeta-changelog h-100">

            <!-- CABECERA -->
            <div class="tarjeta-header">
              <div v-if="item.actualizacion_imagen_destacada" class="imagen-container">
                <img :src="obtenerUrlImagen(item.actualizacion_imagen_destacada)" alt="Imagen destacada"
                  class="imagen-destacada" loading="lazy" />
                <div class="imagen-overlay">
                  <span class="area-label">{{ item.area_servicio?.area_servicio_nombre || 'Sin área' }}</span>
                </div>
              </div>
              <div v-else class="sin-imagen">
                <span class="sin-imagen-icono">🖼️</span>
                <p>Sin imagen destacada</p>
                <div class="imagen-overlay">
                  <span class="area-label">{{ item.area_servicio?.area_servicio_nombre || 'Sin área' }}</span>
                </div>
              </div>
            </div>

            <!-- CUERPO -->
            <div class="tarjeta-cuerpo" @click="verDetalle(item.id)">
              <div class="metadatos-top">
                <span class="fecha">{{ formatearFecha(item.actualizacion_fecha_publicacion) }}</span>
                <span class="separador">|</span>
                <span class="version-number">v{{ item.actualizacion_version || '0.0' }}</span>
              </div>
              <h2 class="titulo-version">{{ item.actualizacion_titulo }}</h2>
              <p class="resumen">{{ item.actualizacion_resumen }}</p>
              <div class="categorias-iconos">
                <div v-for="cat in obtenerCategorias(item)" :key="cat.id" class="icono-categoria">
                  <i class="ico-icon bi" :class="obtenerIconoCategoria(cat.nombre)"></i>
                  <span class="ico-label">{{ cat.nombre }}</span>
                </div>
              </div>
            </div>

            <!-- FOOTER -->
            <div class="tarjeta-pie">
              <button class="btn-quitar" type="button" title="Quitar de guardados"
                @click.stop="quitarGuardado(item.id)">
                <i class="bi bi-bookmark-x-fill fs-5"></i>
              </button>

              <button class="btn-enlace" @click.stop="verDetalle(item.id)">
                Ver más <i class="bi bi-arrow-right"></i>
              </button>
            </div>

          </div>
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
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, nextTick } from 'vue'
import { useRouter } from 'vue-router'
import { obtenerBookmarks, quitarBookmark, limpiarBookmarks } from '../../api/bookmarks'
import type { Version } from '../../types/version'
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


const obtenerNombreCategorias = (item: Version): string => {
  if (Array.isArray((item as any).categorias) && (item as any).categorias.length > 0) {
    return (item as any).categorias
      .map((categoria: any) => categoria.categoria_actualizacion_nombre)
      .filter(Boolean)
      .join(', ')
  }

  return item.categoria?.categoria_actualizacion_nombre || 'Sin categoría'
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


const normalizarTexto = (texto: string): string =>
  texto
    .toLowerCase()
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .trim()

const obtenerIconoCategoria = (nombre: string | undefined): string => {
  if (!nombre) return 'bi-tag-fill'

  const n = normalizarTexto(nombre)

  const mapa: Record<string, string> = {
    'manual de usuario': 'bi-person-lines-fill',
    'manual tecnico': 'bi-tools',
    'instalador': 'bi-box-arrow-down',
    'actualizacion del sistema': 'bi-arrow-repeat',
    'nueva funcionalidad': 'bi-stars',
    'mejora': 'bi-arrow-up-circle-fill',
    'correccion de errores': 'bi-bug-fill',
    'parche de seguridad': 'bi-shield-fill-check',
    'guia de instalacion': 'bi-journal-arrow-down',
    'guia rapida': 'bi-lightning-charge-fill',
    'documentacion': 'bi-file-earmark-text-fill',
    'notas de version': 'bi-card-list',
    'general': 'bi-info-circle-fill',
  }

  if (mapa[n]) return mapa[n]

  // Fallback por coincidencias parciales
  if (n.includes('manual')) return 'bi-journal-text'
  if (n.includes('instal')) return 'bi-box-arrow-down'
  if (n.includes('actualizacion')) return 'bi-arrow-repeat'
  if (n.includes('funcionalidad')) return 'bi-stars'
  if (n.includes('mejora')) return 'bi-arrow-up-circle-fill'
  if (n.includes('correccion') || n.includes('error')) return 'bi-bug-fill'
  if (n.includes('seguridad') || n.includes('parche')) return 'bi-shield-fill-check'
  if (n.includes('guia')) return 'bi-journal-bookmark-fill'
  if (n.includes('documentacion')) return 'bi-file-earmark-text-fill'
  if (n.includes('version')) return 'bi-card-list'
  if (n.includes('general')) return 'bi-info-circle-fill'

  return 'bi-tag-fill'
}

/**
 * Normaliza las categorías de un item, soportando:
 * - item.categorias → array (relación hasMany)
 * - item.categoria  → objeto único (relación belongsTo)
 * - ninguno         → fallback "Sin categoría"
 */
const obtenerCategorias = (item: Version): { id: string | number; nombre: string }[] => {
  if (Array.isArray((item as any).categorias) && (item as any).categorias.length > 0) {
    return (item as any).categorias.map((c: any) => ({
      id: c.categoria_actualizacion_id ?? c.id ?? Math.random(),
      nombre: c.categoria_actualizacion_nombre ?? c.nombre ?? 'Sin categoría',
    }))
  }
  if ((item as any).categoria) {
    const c = (item as any).categoria
    return [{
      id: c.categoria_actualizacion_id ?? c.id ?? 0,
      nombre: c.categoria_actualizacion_nombre ?? c.nombre ?? 'Sin categoría',
    }]
  }
  return [{ id: 0, nombre: 'Sin categoría' }]
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
  /* font-size: 32px; */
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
  background: #fef2f2;
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
  font-weight: 700;
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

/* ── Tarjetas ─────────────────────────────────────────── */
.lista-feed { margin-top: 4px; }

.tarjeta-changelog {
  border: 1px solid #eaeaea;
  border-radius: 16px;
  background-color: white;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  overflow: visible;
  display: flex;
  flex-direction: column;
  cursor: pointer;
}

.tarjeta-changelog:active { transform: scale(0.98); }

@media (min-width: 768px) {
  .tarjeta-changelog:hover {
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.12);
    transform: translateY(-4px);
  }
}

/* CABECERA */
.tarjeta-header {
  position: relative;
  border-radius: 16px 16px 0 0;
  overflow: hidden;
}

.imagen-container {
  position: relative;
  overflow: hidden;
  width: 100%;
  background: #f5f5f5;
}

.imagen-destacada {
  width: 100%;
  aspect-ratio: 22/9;
  object-fit: cover;
  display: block;
  object-position: center;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

@media (min-width: 768px) {
  .imagen-container:hover .imagen-destacada { transform: scale(1.05); }
}

.imagen-overlay {
  position: absolute;
  inset: 0;
  background: linear-gradient(
    to top left,
    rgba(0,0,0,0.78) 0%,
    rgba(0,0,0,0.15) 38%,
    transparent 62%
  );
  display: flex;
  align-items: flex-end;
  justify-content: flex-end;
  padding: 10px 12px;
  pointer-events: none;
}

.area-label {
  display: inline-block;
  background: rgba(255,255,255,0.15);
  backdrop-filter: blur(6px);
  -webkit-backdrop-filter: blur(6px);
  color: #ffffff;
  font-size: 0.72rem;
  font-weight: 700;
  padding: 4px 12px;
  border-radius: 20px;
  letter-spacing: 0.03em;
  text-shadow: 0 1px 3px rgba(0,0,0,0.3);
  border: 1px solid rgba(255,255,255,0.2);
}

.sin-imagen {
  position: relative;
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
  border-bottom: 1px solid #e1e7f0;
  color: #9ca3af;
  width: 100%;
  aspect-ratio: 22/9;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  text-align: center;
}

.sin-imagen-icono { font-size: 32px; margin-bottom: 8px; }
.sin-imagen p { margin: 0; font-size: 0.85rem; }

/* CUERPO */
.tarjeta-cuerpo {
  padding: 14px 16px;
  flex-grow: 1;
  display: flex;
  flex-direction: column;
}

.metadatos-top {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 10px;
  flex-wrap: wrap;
}

.separador { color: #a0aec0; font-weight: 300; }
.fecha { font-size: 0.85rem; color: #888; font-weight: 500; }

.version-number {
  display: inline-block;
  padding: 3px 10px;
  background: white;
  color: var(--primary);
  border-radius: 20px;
  font-size: 0.75rem;
  font-weight: 600;
  font-family: 'Courier New', monospace;
  border: 1px solid #e1e7f0;
}

.titulo-version {
  font-size: 1.1rem;
  font-weight: 700;
  color: #1a1a1a;
  margin: 0 0 8px 0;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

@media (min-width: 768px) {
  .titulo-version {
    font-size: 1.2rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    -webkit-line-clamp: 1;
  }
}

.resumen {
  font-size: 0.9rem;
  color: #555;
  line-height: 1.5;
  margin: 0 0 10px 0;
  display: -webkit-box;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 3;
  overflow: hidden;
  text-overflow: ellipsis;
}

@media (min-width: 768px) {
  .resumen { -webkit-line-clamp: 2; }
}

/* Iconos de categoría */
.categorias-iconos {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
  margin-top: auto;
  padding-top: 8px;
}

.icono-categoria {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  height: 30px;
  min-width: 30px;
  max-width: 30px;
  border-radius: 8px;
  background: #f4f5f7;
  border: 1px solid transparent;
  overflow: hidden;
  cursor: default;
  transition:
    max-width 0.32s cubic-bezier(0.4, 0, 0.2, 1),
    background 0.22s ease,
    border-color 0.22s ease,
    padding 0.32s cubic-bezier(0.4, 0, 0.2, 1);
  padding: 0 7px;
}

.icono-categoria:hover {
  max-width: 200px;
  background: rgba(7, 126, 157, 0.10);
  border-color: rgba(7, 126, 157, 0.25);
  padding: 0 10px;
}

.icono-categoria .ico-icon {
  font-size: 14px;
  color: var(--secondary);
  flex-shrink: 0;
  width: 14px;
  opacity: 1;
  transition: opacity 0.15s ease, width 0.22s ease, margin 0.22s ease;
}

.icono-categoria:hover .ico-icon { opacity: 0; width: 0; margin: 0; }

.icono-categoria .ico-label {
  font-size: 11px;
  font-weight: 700;
  color: var(--primary);
  white-space: nowrap;
  max-width: 0;
  opacity: 0;
  overflow: hidden;
  transition:
    max-width 0.32s cubic-bezier(0.4, 0, 0.2, 1),
    opacity 0.18s ease 0.08s;
}

.icono-categoria:hover .ico-label { max-width: 180px; opacity: 1; }

/* FOOTER */
.tarjeta-pie {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 8px;
  padding: 10px 16px;
  border-top: 1px solid #f0f2f5;
  margin-top: auto;
}

.btn-enlace {
  background: none;
  border: none;
  color: #1a1a1a;
  font-weight: 600;
  font-size: 0.85rem;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 6px 10px;
  border-radius: 8px;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.btn-enlace i { font-size: 0.9rem; transition: transform 0.3s ease; }
.btn-enlace:active { transform: scale(0.95); }

@media (min-width: 768px) {
  .btn-enlace:hover { color: var(--primary); background: rgba(7, 126, 157, 0.08); }
  .btn-enlace:hover i { transform: translateX(4px); }
}
</style>