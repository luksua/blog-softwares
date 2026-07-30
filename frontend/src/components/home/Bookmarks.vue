<template>
  <div class="contenedor-guardados">
    <PageHero eyebrow="Bookmarks" titulo="Registros guardados">
      Consulta las actualizaciones que marcaste para revisar después.
    </PageHero>

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

        <button class="btn-limpiar-bookmark" type="button" @click="confirmarLimpiarGuardados">
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
import PageHero from '../shared/PageHero.vue'
import '../../styles/bookmarks.css'

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