<template>
  <div class="layout">

    <!-- Overlay móvil (solo se muestra si el sidebar está abierto en móvil y es admin) -->
    <div class="sidebar-overlay" v-if="isMobile && isExpanded && isAdmin" @click="toggleSidebar"></div>

    <!-- SIDEBAR: Solo visible para Administradores -->
    <aside v-if="isAdmin" :class="['sidebar', { expanded: isExpanded, collapsed: !isExpanded }]">
      <div class="sidebar-header">
        <div class="logo-full" v-if="isExpanded">Asotrauma</div>
        <button class="toggle-btn" @click="toggleSidebar">
          <i :class="isExpanded ? 'bi-chevron-left' : 'bi-chevron-right'">☰</i>
        </button>
      </div>

      <ul class="nav-menu">
        <!-- Link a Inicio Admin -->
        <router-link to="/admin" custom v-slot="{ navigate, isActive }">
          <li @click="navigate" :class="['nav-item', { active: isActive }]">
            <i class="nav-icon">a</i>
            <span class="nav-text" v-show="isExpanded">Dashboard</span>
            <div class="tooltip" v-if="!isExpanded">Dashboard</div>
          </li>
        </router-link>

        <router-link to="/admin" custom v-slot="{ navigate, isActive }">
          <li @click="navigate" :class="['nav-item', { active: isActive }]">
            <i class="nav-icon">b</i>
            <span class="nav-text" v-show="isExpanded">Panel administrativo</span>
            <div class="tooltip" v-if="!isExpanded">Panel administrativo</div>
          </li>
        </router-link>
      </ul>
    </aside>

    <div class="main">
      <header class="navbar">
        <div class="navbar-left">
          <!-- Botón hamburguesa móvil: Solo visible para admins -->
          <button v-if="isAdmin" class="mobile-toggle" @click="toggleSidebar">
            ☰
          </button>

          <a href="/" class="logo-link">
            <!-- NOTA: Verifica que la ruta de tu imagen sea correcta -->
            <img src="../assets/Asotrauma3.png" alt="logo" class="logo" />
          </a>
        </div>

        <div class="navbar-right">
          <!-- Muestra un link directo a guardados si el usuario está logueado -->
          <router-link v-if="isLoggedIn && !isAdmin" to="/employee/guardados" class="nav-link-header d-none d-md-flex">
            <i class="bi bi-bookmark-fill fs-4"></i>
          </router-link>

          <!-- Botón de Cerrar Sesión (Para todos los logueados) -->
          <button v-if="isLoggedIn" class="btn-logout ms-3" @click="cerrarSesion">
            Cerrar Sesión
          </button>

          <!-- Botón de Iniciar Sesión (Si NO está logueado) -->
          <button v-else class="btn-primary" @click="irALogin">
            <span class="icon">→</span>
            Iniciar Sesión
          </button>
        </div>
      </header>

      <main class="content">
        <div class="content-card-2" :class="['content-card', { 'no-padding': $route.meta.sinPadding }]">
          <router-view />
        </div>
      </main>

      <footer class="footer">
        <div class="footer-content">
          <span>© 2026 - Sistema de Versiones</span>
        </div>
      </footer>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted, watch } from "vue";
import { useRouter, useRoute } from "vue-router";

const router = useRouter();
const route = useRoute();

const isExpanded = ref(true);
const isMobile = ref(false);

const isAdmin = ref(false);
const isEmpleado = ref(false); // Nueva bandera
const isLoggedIn = ref(false);

const toggleSidebar = () => {
  isExpanded.value = !isExpanded.value;
};

const checkMobile = () => {
  isMobile.value = window.innerWidth <= 768;
  isExpanded.value = !isMobile.value;
};

// Validar estado de sesión y rol
const checkAuth = () => {
  const token = localStorage.getItem('auth_token');
  const userDataStr = localStorage.getItem('user_data');

  if (token && userDataStr) {
    isLoggedIn.value = true;
    try {
      const user = JSON.parse(userDataStr);
      isAdmin.value = user.rol === 'admin';
      isEmpleado.value = user.rol === 'empleado';
    } catch (e) {
      isAdmin.value = false;
      isEmpleado.value = false;
    }
  } else {
    isLoggedIn.value = false;
    isAdmin.value = false;
    isEmpleado.value = false;
  }
};

const irALogin = () => {
  router.push({ name: 'login' });
};

const cerrarSesion = () => {
  localStorage.removeItem('auth_token');
  localStorage.removeItem('user_data');
  isLoggedIn.value = false;
  isAdmin.value = false;
  isEmpleado.value = false;
  router.push({ name: 'inicio' }); // Lo devuelve a la zona pública
};

watch(() => route.path, () => {
  checkAuth();
});

onMounted(() => {
  checkMobile();
  checkAuth();
  window.addEventListener("resize", checkMobile);
});

onUnmounted(() => {
  window.removeEventListener("resize", checkMobile);
});
</script>

<style scoped>
/* VARIABLES */
:root {
  --primary: #077E9D;
  --secondary: #025B7D;
  --warning: #FCBB1C;
  --shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.08);
  --shadow-md: 0 4px 16px rgba(0, 0, 0, 0.12);
  --shadow-lg: 0 8px 24px rgba(0, 0, 0, 0.15);
  --transition-slow: all 15s cubic-bezier(0.4, 0, 0.2, 1);
  --transition-slower: all 5s cubic-bezier(0.34, 1.2, 0.64, 1);
}

.layout {
  display: flex;
  height: 100vh;
  background: #f0f2f5;
  overflow: hidden;
  position: relative;
  /* Agregado para contener el overlay correctamente */
}

/* ========== OVERLAY MÓVIL ========== */
.sidebar-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  background-color: rgba(0, 0, 0, 0.4);
  backdrop-filter: blur(2px);
  z-index: 90;
  cursor: pointer;
  animation: fadeIn 0.3s ease-out forwards;
}

@keyframes fadeIn {
  from {
    opacity: 0;
  }

  to {
    opacity: 1;
  }
}

/* ========== SIDEBAR MODERNO ========== */
.sidebar {
  width: 80px;
  background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
  color: white;
  transition: var(--transition-slower);
  display: flex;
  flex-direction: column;
  position: relative;
  box-shadow: var(--shadow-lg);
  z-index: 100;
  backdrop-filter: blur(10px);
  overflow: hidden;
}

.sidebar.expanded {
  width: 260px;
}

/* Animación suave para el contenido interno */
.sidebar-header,
.nav-menu,
.sidebar-footer {
  transition: var(--transition-slow);
}

/* Animación específica para el logo */
.logo-full {
  font-size: 20px;
  font-weight: 600;
  letter-spacing: 1px;
  animation: slideIn 0.5s ease-out;
  white-space: nowrap;
}

@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateX(-20px);
  }

  to {
    opacity: 1;
    transform: translateX(0);
  }
}

/* Animación para los items del menú */
.nav-text {
  display: inline-block;
  transition: opacity 0.4s ease, transform 0.4s ease;
}

.sidebar.expanded .nav-text {
  opacity: 1;
  transform: translateX(0);
}

.sidebar:not(.expanded) .nav-text {
  opacity: 0;
  transform: translateX(-10px);
  width: 0;
  overflow: hidden;
}

.sidebar-header {
  display: flex;
  justify-content: flex-end;
  align-items: center;
  padding: 20px 16px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
  margin-bottom: 20px;
  transition: all 0.5s ease;
}

.sidebar.expanded .sidebar-header {
  justify-content: space-between;
}

.toggle-btn {
  background: rgba(255, 255, 255, 0.15);
  border: none;
  color: white;
  cursor: pointer;
  padding: 8px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.3s ease;
  transform: rotate(0deg);
}

.sidebar.expanded .toggle-btn {
  transform: rotate(180deg);
}

.toggle-btn:hover {
  background: rgba(255, 255, 255, 0.25);
  transform: scale(1.05);
}

.sidebar.expanded .toggle-btn:hover {
  transform: rotate(180deg) scale(1.05);
}

.nav-menu {
  list-style: none;
  padding: 0;
  margin: 0;
  flex: 1;
  transition: all 0.5s ease;
}

.nav-item {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 12px 20px;
  margin: 4px 12px;
  cursor: pointer;
  border-radius: 12px;
  transition: all 0.3s ease;
  position: relative;
  color: rgba(255, 255, 255, 0.85);
  overflow: hidden;
}

.nav-item:hover {
  background: rgba(255, 255, 255, 0.15);
  color: white;
  transform: translateX(4px);
}

.nav-item.active {
  background: rgba(255, 255, 255, 0.25);
  color: white;
  box-shadow: var(--shadow-sm);
  border-left: 3px solid var(--warning);
}

.nav-icon {
  font-size: 22px;
  min-width: 28px;
  transition: transform 0.3s ease;
}

.nav-item:hover .nav-icon {
  transform: scale(1.1);
}

.nav-text {
  font-size: 15px;
  font-weight: 500;
  white-space: nowrap;
}

/* Tooltip para sidebar colapsado */
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
  transition: opacity 0.3s ease, transform 0.3s ease;
  transform: translateX(-10px);
  box-shadow: var(--shadow-sm);
}

.nav-item:hover .tooltip {
  opacity: 1;
  transform: translateX(0);
}

.sidebar-footer {
  padding: 20px;
  border-top: 1px solid rgba(255, 255, 255, 0.1);
  margin-top: auto;
  transition: all 0.5s ease;
}

.version-info {
  text-align: center;
  opacity: 0.7;
  font-size: 12px;
}

/* ========== MAIN CONTENT ========== */
.main {
  flex: 1;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  background: #f5f7fa;
  transition: margin-left 0.6s cubic-bezier(0.34, 1.2, 0.64, 1);
}

/* ========== NAVBAR MODERNO ========== */
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
}

.navbar-left {
  display: flex;
  align-items: center;
  gap: 16px;
  flex: 1;
}

.mobile-toggle {
  display: none;
  background: none;
  border: none;
  font-size: 24px;
  cursor: pointer;
  color: var(--primary);
  transition: transform 0.3s ease;
}

.mobile-toggle:hover {
  transform: scale(1.1);
}

.navbar-center {
  flex: 1;
  display: flex;
  justify-content: center;
}

.logo-link {
  display: inline-block;
}

.logo {
  height: 45px;
  transition: all 0.3s ease;
}

.logo:hover {
  transform: scale(1.02);
}

.navbar-right {
  display: flex;
  align-items: center;
  gap: 16px;
  flex: 1;
  justify-content: flex-end;
}

/* Botón primario */
.btn-primary {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  background-color: var(--primary);
  color: white;
  border: none;
  border-radius: 8px;
  padding: 10px 18px;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.3s ease;
  box-shadow: var(--shadow-sm);
}

.btn-primary .icon {
  font-size: 16px;
}

.btn-primary:hover {
  background-color: var(--secondary);
  transform: translateY(-1px);
  box-shadow: var(--shadow-md);
}

.btn-primary:active {
  transform: scale(0.97);
}

/* ========== CONTENIDO ========== */
.content {
  flex: 1;
  overflow-y: auto;
  padding: 24px;
}

.content-card {
  background: white;
  /* border-radius: 20px; */
  box-shadow: var(--shadow-sm);
  padding: 24px;
  min-height: calc(100vh - 190px);
  transition: all 0.3s ease;
  border-top: 3px solid var(--warning);
}

.content-card:hover {
  box-shadow: var(--shadow-md);
}

.no-padding {
  padding: 0 !important;
}

/* ========== FOOTER MODERNO ========== */
.footer {
  background: linear-gradient(135deg, var(--secondary) 0%, var(--primary) 100%);
  color: white;
  padding: 16px 24px;
}

.footer-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 16px;
  font-size: 13px;
}

.footer-links {
  display: flex;
  gap: 24px;
}

.footer-links a {
  color: rgba(255, 255, 255, 0.8);
  text-decoration: none;
  transition: all 0.3s ease;
}

.footer-links a:hover {
  color: var(--warning);
  text-decoration: underline;
  transform: translateX(2px);
}

/* ========== RESPONSIVE ========== */
@media (max-width: 768px) {
  .sidebar {
    position: fixed;
    left: -80px;
    top: 0;
    height: 100vh;
    transition: left 0.6s cubic-bezier(0.34, 1.2, 0.64, 1);
    width: 80px;
  }

  .sidebar.expanded {
    left: 0;
    width: 260px;
  }

  .sidebar.collapsed {
    left: -80px;
  }

  .mobile-toggle {
    display: block;
  }

  .navbar {
    padding: 0 16px;
  }

  .navbar-left {
    flex: 0;
  }

  .navbar-center {
    flex: 2;
  }

  .navbar-right {
    flex: 0;
  }

  .btn-primary span:not(.icon) {
    display: none;
  }

  .btn-primary {
    padding: 10px;
  }

  .btn-primary .icon {
    margin: 0;
  }

  .content {
    padding: 16px;
  }

  .content-card-2 {
    padding: 16px;
    min-height: calc(100vh - 160px);
  }

  .footer-content {
    flex-direction: column;
    text-align: center;
  }

  .footer-links {
    justify-content: center;
  }
}

/* Tablet responsive */
@media (min-width: 769px) and (max-width: 1024px) {
  .sidebar.expanded {
    width: 220px;
  }

  .content {
    padding: 20px;
  }

  .content-card-2 {
    padding: 20px;
  }
}

/* Scrollbar personalizada */
.content::-webkit-scrollbar {
  width: 8px;
}

.content::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 4px;
}

.content::-webkit-scrollbar-thumb {
  background: var(--primary);
  border-radius: 4px;
}

.content::-webkit-scrollbar-thumb:hover {
  background: var(--warning);
}

/* Botón Logout (Rojo) */
.btn-logout {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  background-color: #dc3545;
  color: white;
  border: none;
  border-radius: 8px;
  padding: 10px 18px;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.3s ease;
  box-shadow: var(--shadow-sm);
}

.btn-logout:hover {
  background-color: #c82333;
  transform: translateY(-1px);
  box-shadow: var(--shadow-md);
}

.btn-logout:active {
  transform: scale(0.97);
}

@media (max-width: 768px) {
  .btn-logout span:not(.icon) {
    display: none;
  }

  .btn-logout {
    padding: 10px;
  }

  .btn-logout .icon {
    margin: 0;
  }
}

.nav-link-header {
  display: flex;
  align-items: center;
  gap: 5px;
  color: var(--secondary);
  text-decoration: none;
  font-weight: 600;
  padding: 8px 12px;
  border-radius: 8px;
  transition: all 0.3s ease;
}
.nav-link-header:hover {
  background: rgba(7, 126, 157, 0.1);
  color: var(--primary);
}
</style>