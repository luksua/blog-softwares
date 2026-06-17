<template>
  <!-- <pre style="font-size: 11px;">
isLoggedIn: {{ isLoggedIn }}
isAdmin: {{ isAdmin }}
route: {{ route.name }}
</pre> -->
  <div class="layout">
    <!-- Overlay móvil para sidebar admin -->
    <div v-if="isMobile && isExpanded && isLoggedIn" class="sidebar-overlay" @click="cerrarSidebarMovil"></div>

    <!-- SIDEBAR: solo admin -->
    <aside v-if="isLoggedIn && isMobile" :class="[
      'sidebar',
      {
        expanded: isExpanded,
        collapsed: !isExpanded,
        mobile: isMobile
      }
    ]">
      <div class="sidebar-header">
        <div v-if="isExpanded" class="logo-full">
          Asotrauma
        </div>

        <button class="toggle-btn" type="button" @click="toggleSidebar">
          ☰
        </button>
      </div>

      <ul class="nav-menu">
        <li>
          <router-link :to="{ name: 'inicio' }" class="nav-item" active-class="active" @click="cerrarSidebarMovil">
            <i class="bi bi-house-door-fill nav-icon"></i>
            <span v-show="isExpanded" class="nav-text">Blog</span>
            <div v-if="!isExpanded" class="tooltip">Blog</div>
          </router-link>
        </li>

        <li>
          <router-link :to="{ name: 'mis-registros' }" class="nav-item" active-class="active"
            @click="cerrarSidebarMovil">
            <i class="bi bi-journal-text nav-icon"></i>
            <span v-show="isExpanded" class="nav-text">Mis registros</span>
            <div v-if="!isExpanded" class="tooltip">Mis registros</div>
          </router-link>
        </li>

        <li v-if="mostrarSupervision">
          <router-link :to="{ name: 'supervision' }" class="nav-item" active-class="active" @click="cerrarSidebarMovil">
            <i class="bi bi-people-fill nav-icon"></i>
            <span v-show="isExpanded" class="nav-text">Dashboard</span>
            <div v-if="!isExpanded" class="tooltip">Dashboard</div>
          </router-link>
        </li>

        <li v-if="mostrarSupervision">
          <router-link :to="{ name: 'supervision' }" class="nav-item" active-class="active" @click="cerrarSidebarMovil">
            <i class="bi bi-people-fill nav-icon"></i>
            <span v-show="isExpanded" class="nav-text">Supervisión</span>
            <div v-if="!isExpanded" class="tooltip">Supervisión</div>
          </router-link>
        </li>

        <li v-if="mostrarGuardados">
          <router-link v-if="mostrarGuardados" :to="{ name: 'employee-guardados' }" class="nav-item"
            active-class="active" @click="cerrarSidebarMovil">
            <i class="bi bi-bookmark-fill nav-icon"></i>
            <span v-show="isExpanded" class="nav-text">Guardados</span>
          </router-link>
        </li>
      </ul>
    </aside>

    <div class="main">
      <!-- NAVBAR GLOBAL -->
      <header class="navbar">
        <div class="navbar-left">
          <button v-if="isLoggedIn && isMobile" class="mobile-toggle" type="button" @click="abrirSidebarMovil">
            ☰
          </button>

          <router-link :to="{ name: 'inicio' }" class="logo-link">
            <img src="../assets/Asotrauma3.png" alt="Asotrauma" class="logo" />
          </router-link>
        </div>

        <div class="navbar-right">
          <template v-if="!authReady && !isLoggedIn">
            <span class="navbar-loading">Cargando...</span>
          </template>

          <template v-else-if="isLoggedIn">
            <nav class="header-nav">
              <router-link :to="{ name: 'inicio' }" class="nav-link-header">
                Blog
              </router-link>

              <router-link :to="{ name: 'mis-registros' }" class="nav-link-header">
                Mis registros
              </router-link>

              <router-link v-if="mostrarSupervision" :to="{ name: 'supervision' }" class="nav-link-header">
                Supervisión
              </router-link>

              <router-link v-if="mostrarSupervision" :to="{ name: 'dashboard' }" class="nav-link-header">
                Dashboard
              </router-link>
              
              <router-link v-if="mostrarGuardados" :to="{ name: 'employee-guardados' }"
                class="nav-link-header nav-icon-link" title="Guardados">
                <i class="bi bi-bookmark-fill fs-5"></i>
              </router-link>

            </nav>

            <div class="notification-wrapper" @click.stop>
              <button class="notification-button" type="button" title="Notificaciones"
                @click="togglePanelNotificaciones">
                <i class="bi bi-bell"></i>
                <span v-if="notificacionesNoLeidas > 0" class="notification-badge">
                  {{ etiquetaNotificacionesNoLeidas }}
                </span>
              </button>

              <section v-if="mostrarPanelNotificaciones" class="notification-panel">
                <header class="notification-panel-header">
                  <div>
                    <strong>Notificaciones</strong>
                    <small>{{ notificacionesNoLeidas }} sin leer</small>
                  </div>

                  <button v-if="notificacionesNoLeidas > 0" class="notification-link-button" type="button"
                    @click="marcarTodasComoLeidas">
                    Marcar leídas
                  </button>
                </header>

                <div v-if="cargandoNotificaciones" class="notification-empty">
                  Cargando...
                </div>

                <div v-else-if="notificaciones.length === 0" class="notification-empty">
                  No tienes notificaciones.
                </div>

                <template v-else>
                  <button v-for="notificacion in notificaciones" :key="notificacion.id"
                    :class="['notification-item', { unread: !notificacion.leida_en }]" type="button"
                    @click="abrirNotificacion(notificacion)">
                    <span class="notification-title">{{ notificacion.titulo }}</span>
                    <span class="notification-message">{{ notificacion.mensaje }}</span>
                    <small>{{ formatearFechaNotificacion(notificacion.created_at) }}</small>
                  </button>
                </template>
              </section>
            </div>

            <!-- Botón de Ayuda -->

            <HelpButton />

            <button class="btn-logout" type="button" @click="logout">
              <i class="bi bi-door-open"></i><i class="bi bi-arrow-right"></i>
            </button>
          </template>
        </div>
      </header>

      <main class="content">
        <div :class="[
          'content-card',
          'content-card-2',
          { 'no-padding': route.meta.sinPadding }
        ]">
          <router-view />
        </div>
      </main>

      <!-- <footer class="footer">
        <div class="footer-content">
          <span>© 2026 - Sistema de Versiones</span>
        </div>
      </footer> -->
    </div>

    <!-- Botón flotante móvil -->
    <!-- <button v-if="mostrarBotonNuevoRegistro" class="fab-new-record" type="button" aria-label="Crear nuevo registro"
      @click="irANuevoRegistro">
      +
    </button> -->
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import api, { INTRANET_ENTRY_URL } from '../api/api'
import HelpButton from '../components/HelpButton.vue'
import {
  listarNotificaciones,
  marcarNotificacionLeida,
  marcarTodasNotificacionesLeidas,
  type BlogNotification,
} from '../api/notificaciones'

const puedeSupervisarArea = ref(false)

const router = useRouter()
const route = useRoute()

const isExpanded = ref(true)
const isMobile = ref(false)

const isAdmin = ref(false)
const isEmpleado = ref(false)
const isLoggedIn = ref(false)
const authReady = ref(false)

const permisos = ref<string[]>([])
const notificaciones = ref<BlogNotification[]>([])
const notificacionesNoLeidas = ref(0)
const cargandoNotificaciones = ref(false)
const mostrarPanelNotificaciones = ref(false)
let intervaloNotificaciones: ReturnType<typeof window.setInterval> | null = null

const existeRuta = (name: string) => {
  return router.hasRoute(name)
}

const mostrarGuardados = computed(() => {
  return isLoggedIn.value && existeRuta('employee-guardados')
})

const etiquetaNotificacionesNoLeidas = computed(() => {
  return notificacionesNoLeidas.value > 99 ? '99+' : String(notificacionesNoLeidas.value)
})

// const puedeSupervisarArea = computed(() => {
//   return isAdmin.value || permisos.value.includes('blog.supervisar_area')
// })

const mostrarSupervision = computed(() => {
  return isLoggedIn.value && puedeSupervisarArea.value && router.hasRoute('supervision')
})

// const estaEnFormularioRegistro = computed(() => {
//   return [
//     'actualizaciones-crear',
//     'actualizaciones-create',
//     'actualizaciones-editar',
//     'actualizaciones-edit',
//     'crear-actualizacion',
//     'editar-actualizacion',
//     'admin-actualizaciones-edit',
//   ].includes(String(route.name))
// })

const normalizarPermisos = (valor: unknown): string[] => {
  if (!Array.isArray(valor)) {
    return []
  }

  return valor
    .map((item: any) => {
      if (typeof item === 'string') return item
      if (item?.permiso_nombre) return item.permiso_nombre
      if (item?.nombre) return item.nombre
      return ''
    })
    .filter(Boolean)
}

const aplicarUsuario = (data: any) => {
  const user = data?.usuario || data?.user || data

  if (!user) {
    limpiarSesionLocal()
    return
  }

  const permisosNormalizados = normalizarPermisos(data?.permisos || user?.permisos || [])

  isLoggedIn.value = true

  isAdmin.value = data?.es_admin === true
  isEmpleado.value = !isAdmin.value

  puedeSupervisarArea.value =
    data?.puede_supervisar_area === true ||
    data?.es_admin_area === true ||
    data?.tipo_usuario === 'admin' ||
    permisosNormalizados.includes('blog.supervisar_area')

  permisos.value = permisosNormalizados

  localStorage.setItem('user_data', JSON.stringify(user))
}

const cargarUsuarioDesdeLocalStorage = () => {
  const userDataStr = localStorage.getItem('user_data')

  if (!userDataStr) {
    return
  }

  try {
    const user = JSON.parse(userDataStr)
    aplicarUsuario({ usuario: user })
  } catch (error) {
    console.error(error)
    limpiarSesionLocal()
  }
}

const checkAuth = async () => {
  try {
    const response = await api.get('/me')
    aplicarUsuario(response.data)
    await cargarNotificaciones()
    iniciarPollingNotificaciones()
  } catch (error) {
    console.error(error)
    limpiarSesionLocal()
  } finally {
    authReady.value = true
  }
}

const cargarNotificaciones = async () => {
  if (!isLoggedIn.value) return

  cargandoNotificaciones.value = true

  try {
    const response = await listarNotificaciones(10)
    notificaciones.value = response?.data || []
    notificacionesNoLeidas.value = Number(response?.meta?.no_leidas ?? 0)
  } catch (error) {
    console.error('Error al cargar notificaciones:', error)
  } finally {
    cargandoNotificaciones.value = false
  }
}

const iniciarPollingNotificaciones = () => {
  if (intervaloNotificaciones) return

  intervaloNotificaciones = window.setInterval(() => {
    cargarNotificaciones()
  }, 60000)
}

const detenerPollingNotificaciones = () => {
  if (!intervaloNotificaciones) return

  window.clearInterval(intervaloNotificaciones)
  intervaloNotificaciones = null
}

const togglePanelNotificaciones = async () => {

  mostrarPanelNotificaciones.value = !mostrarPanelNotificaciones.value

  if (mostrarPanelNotificaciones.value) {
    await cargarNotificaciones()
  }
}

const cerrarPanelNotificaciones = () => {
  mostrarPanelNotificaciones.value = false
}

const abrirNotificacion = async (notificacion: BlogNotification) => {
  try {
    const mobile = window.innerWidth <= 768
    isMobile.value = mobile
    isExpanded.value = !mobile

    if (!mobile) {
      if (!notificacion.leida_en) {
        await marcarNotificacionLeida(notificacion.id)
        notificacion.leida_en = new Date().toISOString()
        notificacionesNoLeidas.value = Math.max(0, notificacionesNoLeidas.value - 1)
      }

      cerrarPanelNotificaciones()

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
    } else {
      router.push({
        name: 'mis-registros-',
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
    notificaciones.value = notificaciones.value.map((notificacion) => ({
      ...notificacion,
      leida_en: notificacion.leida_en || new Date().toISOString(),
    }))
    notificacionesNoLeidas.value = 0
  } catch (error) {
    console.error('Error al marcar notificaciones como leídas:', error)
  }
}

const formatearFechaNotificacion = (fecha?: string | null) => {
  if (!fecha) return ''

  return new Intl.DateTimeFormat('es-CO', {
    day: '2-digit',
    month: 'short',
    hour: '2-digit',
    minute: '2-digit',
  }).format(new Date(fecha))
}

const manejarNotificacionesActualizadas = () => {
  void cargarNotificaciones()
}

// const irANuevoRegistro = () => {
//   if (router.hasRoute('actualizaciones-crear')) {
//     router.push({ name: 'actualizaciones-crear' })
//     return
//   }

//   router.push({ name: 'mis-registros' })
// }

const toggleSidebar = () => {
  isExpanded.value = !isExpanded.value
}

const abrirSidebarMovil = () => {
  isExpanded.value = true
}

const cerrarSidebarMovil = () => {
  if (isMobile.value) {
    isExpanded.value = false
  }
}

const checkMobile = () => {
  const mobile = window.innerWidth <= 768
  isMobile.value = mobile
  isExpanded.value = !mobile
}

const limpiarSesionLocal = () => {
  localStorage.removeItem('user_data')
  localStorage.removeItem('auth_token')

  detenerPollingNotificaciones()
  notificaciones.value = []
  notificacionesNoLeidas.value = 0
  mostrarPanelNotificaciones.value = false

  isLoggedIn.value = false
  isAdmin.value = false
  isEmpleado.value = false
  puedeSupervisarArea.value = false
  permisos.value = []
}

const logout = async () => {
  try {
    await api.post('/logout')
  } catch (error) {
    console.error(error)
  } finally {
    limpiarSesionLocal()
    window.location.href = INTRANET_ENTRY_URL || '/'
  }
}

onMounted(() => {
  checkMobile()
  cargarUsuarioDesdeLocalStorage()
  checkAuth()

  window.addEventListener('resize', checkMobile)
  window.addEventListener('click', cerrarPanelNotificaciones)
  window.addEventListener('notificaciones-updated', manejarNotificacionesActualizadas)
})

onUnmounted(() => {
  detenerPollingNotificaciones()
  window.removeEventListener('resize', checkMobile)
  window.removeEventListener('click', cerrarPanelNotificaciones)
  window.removeEventListener('notificaciones-updated', manejarNotificacionesActualizadas)
})
</script>

<style scoped>
:root {
  --primary: #077e9d;
  --secondary: #025b7d;
  --warning: #fcbb1c;
  --shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.08);
  --shadow-md: 0 4px 16px rgba(0, 0, 0, 0.12);
  --shadow-lg: 0 8px 24px rgba(0, 0, 0, 0.15);
}

.nav-icon-link {
  width: 40px;
  height: 40px;
  padding: 0;
  justify-content: center;
  border: 1px solid #dbe3ee;
  border-radius: 999px;
  background: #ffffff;
}

.nav-icon-link:hover {
  background: rgba(7, 126, 157, 0.08);
  border-color: rgba(7, 126, 157, 0.25);
}

.nav-icon-link .bi-bookmark-fill {
  color: #025b7d;
  font-size: 1.1rem;
}

.layout {
  display: flex;
  height: 100vh;
  background: #f0f2f5;
  overflow: hidden;
  position: relative;
}

.sidebar-overlay {
  position: fixed;
  inset: 0;
  background-color: rgba(0, 0, 0, 0.4);
  backdrop-filter: blur(2px);
  z-index: 90;
  cursor: pointer;
}

.sidebar {
  width: 80px;
  background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
  color: white;
  display: flex;
  flex-direction: column;
  position: relative;
  z-index: 100;
  box-shadow: var(--shadow-lg);
  overflow: hidden;
  transition: width 0.3s ease;
}

.sidebar.expanded {
  width: 260px;
}

.sidebar-header {
  display: flex;
  justify-content: flex-end;
  align-items: center;
  padding: 20px 16px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.15);
  margin-bottom: 20px;
}

.sidebar.expanded .sidebar-header {
  justify-content: space-between;
}

.logo-full {
  font-size: 20px;
  font-weight: 700;
  letter-spacing: 0.5px;
  white-space: nowrap;
}

.toggle-btn {
  background: rgba(255, 255, 255, 0.15);
  border: none;
  color: white;
  cursor: pointer;
  padding: 8px 10px;
  border-radius: 8px;
  transition: background 0.2s ease;
}

.toggle-btn:hover {
  background: rgba(255, 255, 255, 0.25);
}

.nav-menu {
  list-style: none;
  padding: 0;
  margin: 0;
  flex: 1;
}

.nav-menu li {
  list-style: none;
}

.nav-item {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 22px 20px;
  margin: 4px 12px;
  cursor: pointer;
  border-radius: 12px;
  color: rgba(255, 255, 255, 0.88);
  text-decoration: none;
  position: relative;
  transition: background 0.2s ease, transform 0.2s ease;
}

.nav-item:hover {
  background: rgba(255, 255, 255, 0.15);
  color: white;
  transform: translateX(4px);
}

.nav-item.active,
.nav-item.router-link-active {
  background: rgba(255, 255, 255, 0.25);
  color: white;
  border-left: 3px solid var(--warning);
}

.nav-icon {
  font-size: 20px;
  min-width: 28px;
  text-align: center;
}

.nav-text {
  font-size: 15px;
  font-weight: 600;
  white-space: nowrap;
}

.tooltip {
  position: absolute;
  left: 70px;
  background: var(--secondary);
  color: white;
  padding: 6px 12px;
  border-radius: 8px;
  font-size: 13px;
  white-space: nowrap;
  opacity: 0;
  pointer-events: none;
  transform: translateX(-10px);
  transition: opacity 0.2s ease, transform 0.2s ease;
  box-shadow: var(--shadow-sm);
}

.nav-item:hover .tooltip {
  opacity: 1;
  transform: translateX(0);
}

.main {
  flex: 1;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  background: #f5f7fa;
}

.navbar {
  background: white;
  padding: 0 24px;
  height: 70px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  box-shadow: var(--shadow-sm);
  position: relative;
  z-index: 10;
  gap: 16px;
}

.navbar-left {
  display: flex;
  align-items: center;
  gap: 16px;
  min-width: fit-content;
}

.mobile-toggle {
  display: none;
  background: none;
  border: none;
  font-size: 24px;
  cursor: pointer;
  color: var(--primary);
}

.logo-link {
  display: inline-flex;
  align-items: center;
}

.logo {
  height: 45px;
  display: block;
}

.navbar-right {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 0.75rem;
  flex: 1;
  min-width: 0;
}

.header-nav {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  min-width: 0;
}

.nav-link-header {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  color: #374151;
  text-decoration: none;
  font-weight: 600;
  font-size: 0.95rem;
  padding: 8px 12px;
  border-radius: 8px;
  white-space: nowrap;
  transition: background 0.2s ease, color 0.2s ease;
}

.nav-link-header:hover {
  background: rgba(7, 126, 157, 0.1);
  color: var(--primary);
}

.nav-link-header.router-link-active {
  color: var(--primary);
  font-weight: 700;
}

.nav-icon-link {
  font-size: 1.1rem;
}

.btn-new-record {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border: none;
  border-radius: 999px;
  padding: 0.65rem 1rem;
  background: var(--primary);
  color: white;
  font-weight: 700;
  font-size: 0.95rem;
  cursor: pointer;
  white-space: nowrap;
  box-shadow: 0 8px 18px rgba(7, 126, 157, 0.25);
  transition: transform 0.15s ease, box-shadow 0.15s ease, background 0.15s ease;
}

.btn-new-record:hover {
  background: var(--secondary);
  transform: translateY(-1px);
  box-shadow: 0 12px 24px rgba(7, 126, 157, 0.32);
}

.btn-logout {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background-color: #dc3545;
  color: white;
  border: none;
  border-radius: 8px;
  padding: 10px 18px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  white-space: nowrap;
  transition: background 0.2s ease, transform 0.2s ease, box-shadow 0.2s ease;
  box-shadow: var(--shadow-sm);
}

.btn-logout:hover {
  background-color: #c82333;
  transform: translateY(-1px);
  box-shadow: var(--shadow-md);
}

.content {
  flex: 1;
  overflow-y: auto;
  padding: 24px;
}

.content-card {
  background: white;
  box-shadow: var(--shadow-sm);
  padding: 24px;
  min-height: calc(100vh - 190px);
  border-top: 3px solid var(--warning);
}

.no-padding {
  padding: 0 !important;
}

.footer {
  background: linear-gradient(135deg, var(--secondary) 0%, var(--primary) 100%);
  color: white;
  padding: 16px 24px;
}

.footer-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 16px;
  font-size: 13px;
}

.content::-webkit-scrollbar {
  width: 8px;
}

.content::-webkit-scrollbar-track {
  background: #f1f1f1;
}

.content::-webkit-scrollbar-thumb {
  background: var(--primary);
  border-radius: 4px;
}

.content::-webkit-scrollbar-thumb:hover {
  background: var(--warning);
}


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
  transition: background 0.2s ease, transform 0.2s ease, border-color 0.2s ease;
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

.notification-panel {
  position: absolute;
  top: calc(100% + 12px);
  right: 0;
  z-index: 120;
  width: min(360px, calc(100vw - 32px));
  max-height: 440px;
  overflow-y: auto;
  border: 1px solid #e2e8f0;
  border-radius: 18px;
  background: #ffffff;
  box-shadow: 0 24px 60px rgba(15, 23, 42, 0.18);
}

.notification-panel-header {
  position: sticky;
  top: 0;
  z-index: 1;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 14px 16px;
  border-bottom: 1px solid #eef2f7;
  background: #ffffff;
}

.notification-panel-header strong,
.notification-panel-header small {
  display: block;
}

.notification-panel-header strong {
  color: #0f172a;
  font-size: 0.98rem;
}

.notification-panel-header small {
  margin-top: 2px;
  color: #64748b;
  font-size: 0.78rem;
}

.notification-link-button {
  border: none;
  background: transparent;
  color: #077e9d;
  font-size: 0.78rem;
  font-weight: 800;
  cursor: pointer;
  white-space: nowrap;
}

.notification-empty {
  padding: 22px 16px;
  color: #64748b;
  text-align: center;
  font-size: 0.9rem;
}

.notification-item {
  display: block;
  width: 100%;
  padding: 14px 16px;
  border: none;
  border-bottom: 1px solid #f1f5f9;
  background: #ffffff;
  text-align: left;
  cursor: pointer;
  transition: background 0.2s ease;
}

.notification-item:hover {
  background: #f8fafc;
}

.notification-item.unread {
  background: #eef9fc;
}

.notification-item.unread:hover {
  background: #e3f4f8;
}

.notification-title {
  display: block;
  color: #0f172a;
  font-size: 0.9rem;
  font-weight: 800;
  line-height: 1.25;
}

.notification-message {
  display: block;
  margin-top: 4px;
  color: #475569;
  font-size: 0.82rem;
  line-height: 1.35;
}

.notification-item small {
  display: block;
  margin-top: 7px;
  color: #94a3b8;
  font-size: 0.72rem;
}

@media (max-width: 426px) {
  .header-nav {
    display: none;
  }
}

@media (max-width: 900px) {
  .header-nav {
    gap: 0.35rem;
  }

  .nav-link-header {
    padding: 8px 8px;
    font-size: 0.88rem;
  }

  .btn-new-record,
  .btn-logout {
    padding: 9px 12px;
    font-size: 0.85rem;
  }
}

@media (max-width: 425px) {
  .sidebar {
    position: fixed;
    left: -80px;
    top: 0;
    height: 100vh;
    width: 80px;
    transition: left 0.25s ease, width 0.25s ease;
  }

  .sidebar.expanded {
    left: 0;
    width: 260px;
  }

  .sidebar.collapsed {
    left: -80px;
  }

  .mobile-toggle {
    display: inline-flex;
  }

  .navbar {
    height: 64px;
    padding: 0 12px;
    gap: 8px;
  }

  .logo {
    height: 38px;
  }

  .navbar-right {
    gap: 0.5rem;
  }

  .btn-new-record {
    display: none;
  }

  .btn-logout {
    padding: 8px 10px;
    font-size: 0.8rem;
  }

  .content {
    padding: 16px;
  }

  .content-card {
    padding: 16px;
    min-height: calc(100vh - 160px);
  }

  .footer {
    padding: 12px 16px;
  }

  .footer-content {
    justify-content: center;
    text-align: center;
  }
}

@media (max-width: 768px) {
  .sidebar {
    position: fixed;
    left: -280px;
    top: 0;
    height: 100vh;
    width: 280px;
    z-index: 100;
    transition: left 0.25s ease;
  }

  .sidebar.expanded {
    left: 0;
    width: 280px;
  }

  .sidebar.collapsed {
    left: -280px;
  }

  .sidebar.mobile .sidebar-header {
    justify-content: space-between;
  }

  .sidebar.mobile .tooltip {
    display: none;
  }

  .navbar {
    height: 64px;
    padding: 0 12px;
    gap: 8px;
  }

  .logo {
    height: 38px;
  }

  .navbar-right {
    gap: 0.5rem;
  }

  .btn-new-record {
    display: none;
  }

  .btn-logout {
    padding: 8px 10px;
    font-size: 0.8rem;
  }

  .content {
    padding: 16px;
  }

  .content-card {
    padding: 16px;
    min-height: calc(100vh - 160px);
  }

  .footer {
    padding: 12px 16px;
  }

  .footer-content {
    justify-content: center;
    text-align: center;
  }
}
</style>