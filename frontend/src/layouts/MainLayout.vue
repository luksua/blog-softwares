<template>
  <!-- <pre style="font-size: 11px;">
isLoggedIn: {{ isLoggedIn }}
isAdmin: {{ isAdmin }}
route: {{ route.name }}
</pre> -->
  <div class="layout">
    <!-- Overlay móvil para sidebar admin -->
    <div v-if="isMobile && isExpanded && isAdmin" class="sidebar-overlay" @click="toggleSidebar"></div>

    <!-- SIDEBAR: solo admin -->
    <aside v-if="isAdmin" :class="['sidebar', { expanded: isExpanded, collapsed: !isExpanded }]">
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
          <router-link :to="{ name: 'inicio' }" class="nav-item" active-class="active">
            <i class="nav-icon">🏠</i>
            <span v-show="isExpanded" class="nav-text">Blog</span>
            <div v-if="!isExpanded" class="tooltip">Blog</div>
          </router-link>
        </li>

        <li>
          <router-link :to="{ name: 'mis-registros' }" class="nav-item" active-class="active">
            <i class="nav-icon">📝</i>
            <span v-show="isExpanded" class="nav-text">Mis registros</span>
            <div v-if="!isExpanded" class="tooltip">Mis registros</div>
          </router-link>
        </li>

        <li v-if="mostrarSupervision">
          <router-link :to="{ name: 'supervision' }" class="nav-item" active-class="active">
            <i class="nav-icon">👥</i>
            <span v-show="isExpanded" class="nav-text">Supervisión</span>
            <div v-if="!isExpanded" class="tooltip">Supervisión</div>
          </router-link>
        </li>
      </ul>
    </aside>

    <div class="main">
      <!-- NAVBAR GLOBAL -->
      <header class="navbar">
        <div class="navbar-left">
          <button v-if="isAdmin" class="mobile-toggle" type="button" @click="toggleSidebar">
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

              <router-link v-if="mostrarGuardados" :to="{ name: 'employee-guardados' }"
                class="nav-link-header nav-icon-link" title="Guardados">
                <i class="bi bi-bookmark-fill fs-5"></i>
              </router-link>
            </nav>

            <!-- <button v-if="mostrarBotonNuevoRegistro" class="btn-new-record" type="button" @click="irANuevoRegistro">
              + Nuevo registro
            </button> -->

            <button class="btn-logout" type="button" @click="logout">
              Cerrar sesión
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

      <footer class="footer">
        <div class="footer-content">
          <span>© 2026 - Sistema de Versiones</span>
        </div>
      </footer>
    </div>

    <!-- Botón flotante móvil -->
    <button v-if="mostrarBotonNuevoRegistro" class="fab-new-record" type="button" aria-label="Crear nuevo registro"
      @click="irANuevoRegistro">
      +
    </button>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import api, { INTRANET_ENTRY_URL } from '../api/api'

const router = useRouter()
const route = useRoute()

const isExpanded = ref(true)
const isMobile = ref(false)

const isAdmin = ref(false)
const isEmpleado = ref(false)
const isLoggedIn = ref(false)
const authReady = ref(false)

const permisos = ref<string[]>([])

const existeRuta = (name: string) => {
  return router.hasRoute(name)
}

const mostrarGuardados = computed(() => {
  return isLoggedIn.value && existeRuta('employee-guardados')
})

const puedeSupervisarArea = computed(() => {
  return isAdmin.value || permisos.value.includes('blog.supervisar_area')
})

const mostrarSupervision = computed(() => {
  return isLoggedIn.value && puedeSupervisarArea.value && existeRuta('supervision')
})

const estaEnFormularioRegistro = computed(() => {
  return [
    'actualizaciones-crear',
    'actualizaciones-create',
    'actualizaciones-editar',
    'actualizaciones-edit',
    'crear-actualizacion',
    'editar-actualizacion',
    'admin-actualizaciones-edit',
  ].includes(String(route.name))
})

const mostrarBotonNuevoRegistro = computed(() => {
  return isLoggedIn.value && !estaEnFormularioRegistro.value
})

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

  const grupo = String(user.usuario_grupo || '').toUpperCase()

  isLoggedIn.value = true
  isAdmin.value = grupo === 'ADMIN'
  isEmpleado.value = grupo !== 'ADMIN'

  permisos.value = normalizarPermisos(data?.permisos || user?.permisos || [])

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
  } catch (error) {
    console.error(error)
    limpiarSesionLocal()
  } finally {
    authReady.value = true
  }
}

const irANuevoRegistro = () => {
  if (router.hasRoute('actualizaciones-crear')) {
    router.push({ name: 'actualizaciones-crear' })
    return
  }

  router.push({ name: 'mis-registros' })
}
const toggleSidebar = () => {
  isExpanded.value = !isExpanded.value
}

const checkMobile = () => {
  isMobile.value = window.innerWidth <= 768
  isExpanded.value = !isMobile.value
}

const limpiarSesionLocal = () => {
  localStorage.removeItem('user_data')
  localStorage.removeItem('auth_token')

  isLoggedIn.value = false
  isAdmin.value = false
  isEmpleado.value = false
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
})

onUnmounted(() => {
  window.removeEventListener('resize', checkMobile)
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

.bi-bookmark-fill {
  color: #025b7d;
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
  padding: 12px 20px;
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

.fab-new-record {
  display: none;
  position: fixed;
  right: 1.25rem;
  bottom: 1.25rem;
  width: 58px;
  height: 58px;
  border-radius: 999px;
  border: none;
  background: var(--secondary);
  color: white;
  font-size: 2rem;
  font-weight: 700;
  line-height: 1;
  z-index: 1000;
  box-shadow: 0 12px 28px rgba(7, 126, 157, 0.35);
  cursor: pointer;
}

.fab-new-record:active {
  transform: scale(0.96);
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

@media (max-width: 768px) {
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

  .header-nav {
    display: none;
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

  .fab-new-record {
    display: flex;
    align-items: center;
    justify-content: center;
  }
}
</style>