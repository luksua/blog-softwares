<template>
  <div ref="wrapperRef" class="notification-wrapper">
    <button
      class="notification-button"
      type="button"
      title="Notificaciones"
      aria-label="Notificaciones"
      @click.stop="togglePanel"
    >
      <i class="bi bi-bell"></i>

      <span v-if="noLeidas > 0" class="notification-badge">
        {{ etiquetaNoLeidas }}
      </span>
    </button>

    <NotificationsList
      v-if="mostrarPanel"
      :notificaciones="notificaciones"
      :cargando="cargando"
      :no-leidas="noLeidas"
      @abrir="abrirNotificacion"
      @marcar-todas="marcarTodasComoLeidas"
    />
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import NotificationsList from './NotificationsList.vue'
import {
  listarNotificaciones,
  marcarNotificacionLeida,
  marcarTodasNotificacionesLeidas,
  type BlogNotification,
} from '../../api/notificaciones'

const router = useRouter()

const wrapperRef = ref<HTMLElement | null>(null)
const notificaciones = ref<BlogNotification[]>([])
const noLeidas = ref(0)
const cargando = ref(false)
const mostrarPanel = ref(false)

let intervaloNotificaciones: ReturnType<typeof window.setInterval> | null = null

const etiquetaNoLeidas = computed(() => {
  return noLeidas.value > 99 ? '99+' : String(noLeidas.value)
})

const cargarNotificaciones = async (silencioso = false) => {
  if (!silencioso) {
    cargando.value = true
  }

  try {
    const response = await listarNotificaciones(10)

    notificaciones.value = response?.data || []
    noLeidas.value = Number(response?.meta?.no_leidas ?? 0)
  } catch (error) {
    console.error('Error al cargar notificaciones:', error)
  } finally {
    if (!silencioso) {
      cargando.value = false
    }
  }
}

const iniciarPolling = () => {
  if (intervaloNotificaciones) return

  intervaloNotificaciones = window.setInterval(() => {
    cargarNotificaciones(true)
  }, 60000)
}

const detenerPolling = () => {
  if (!intervaloNotificaciones) return

  window.clearInterval(intervaloNotificaciones)
  intervaloNotificaciones = null
}

const togglePanel = async () => {
  mostrarPanel.value = !mostrarPanel.value

  if (mostrarPanel.value) {
    await cargarNotificaciones()
  }
}

const cerrarPanel = () => {
  mostrarPanel.value = false
}

const manejarClickGlobal = (event: MouseEvent) => {
  if (!wrapperRef.value) return

  const target = event.target as Node

  if (!wrapperRef.value.contains(target)) {
    cerrarPanel()
  }
}

const abrirNotificacion = async (notificacion: BlogNotification) => {
  try {
    if (!notificacion.leida_en) {
      await marcarNotificacionLeida(notificacion.id)

      notificacion.leida_en = new Date().toISOString()
      noLeidas.value = Math.max(0, noLeidas.value - 1)
    }

    cerrarPanel()

    const rutaSugerida = notificacion.data?.ruta_sugerida

    if (rutaSugerida?.name && router.hasRoute(rutaSugerida.name)) {
      router.push(rutaSugerida)
      return
    }

    if (notificacion.actualizacion_id) {
      router.push({
        name: 'mis-registros-show',
        params: {
          id: notificacion.actualizacion_id,
        },
      })
    }
  } catch (error) {
    console.error('Error al abrir notificación:', error)
  }
}

const marcarTodasComoLeidas = async () => {
  try {
    await marcarTodasNotificacionesLeidas()

    const fechaLectura = new Date().toISOString()

    notificaciones.value = notificaciones.value.map((notificacion) => ({
      ...notificacion,
      leida_en: notificacion.leida_en || fechaLectura,
    }))

    noLeidas.value = 0
  } catch (error) {
    console.error('Error al marcar notificaciones como leídas:', error)
  }
}

onMounted(() => {
  cargarNotificaciones(true)
  iniciarPolling()
  window.addEventListener('click', manejarClickGlobal)
})

onUnmounted(() => {
  detenerPolling()
  window.removeEventListener('click', manejarClickGlobal)
})
</script>

<style scoped>
.notification-wrapper {
  position: relative;
  display: inline-flex;
  align-items: center;
}

.notification-button {
  position: relative;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 40px;
  height: 40px;
  border: 1px solid #dbe3ee;
  border-radius: 999px;
  background: #ffffff;
  color: #025b7d;
  cursor: pointer;
  transition:
    background 0.2s ease,
    transform 0.2s ease,
    border-color 0.2s ease;
}

.notification-button:hover {
  background: rgba(7, 126, 157, 0.08);
  border-color: rgba(7, 126, 157, 0.25);
  transform: translateY(-1px);
}

.notification-button i {
  font-size: 1.15rem;
}

.notification-badge {
  position: absolute;
  top: -4px;
  right: -4px;
  min-width: 18px;
  height: 18px;
  padding: 0 5px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 999px;
  background: #dc2626;
  color: #ffffff;
  font-size: 0.68rem;
  font-weight: 800;
  line-height: 1;
  box-shadow: 0 4px 10px rgba(220, 38, 38, 0.25);
}
</style>