import { createRouter, createWebHistory } from 'vue-router'
import { BACKEND_URL, INTRANET_ENTRY_URL } from '../api/api.ts'
import { cargarAuth } from '../api/auth'

import MainLayout from '../layouts/MainLayout.vue'
import HomePage from '../features/employee/HomePage.vue'
import HomePageAdmin from '../features/admin/HomePage.vue'
import SupervisionPage from '../features/admin/SupervisionPage.vue'
import DashboardPage from '../features/admin/DashboardArea.vue'
import ListaActualizaciones from '../components/home/List.vue'
import VerActualizacion from '../components/home/ListVersion.vue'
import VerActualizacionAdmin from '../components/register/ListVersion.vue'
import EditarActualizacionAdmin from '../components/register/EditVersion.vue'
import VerGuardados from '../features/employee/Bookmarks.vue'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    {
      path: '/',
      component: MainLayout,
      meta: {
        requiresAuth: true,
      },
      children: [
        {
          path: '',
          name: 'inicio',
          component: HomePage,
          meta: {
            requiresAuth: true,
          },
        },

        {
          path: 'admin',
          redirect: {
            name: 'mis-registros',
          },
        },

        {
          path: 'actualizaciones',
          name: 'actualizaciones',
          component: ListaActualizaciones,
          meta: {
            requiresAuth: true,
          },
        },

        {
          path: 'actualizaciones/:id',
          name: 'actualizaciones-show',
          component: VerActualizacion,
          props: true,
          meta: {
            sinPadding: true,
            requiresAuth: true,
          },
        },

        {
          path: 'guardados',
          name: 'employee-guardados',
          component: VerGuardados,
          meta: {
            requiresAuth: true,
          },
        },

        {
          path: 'employee/guardados',
          redirect: {
            name: 'employee-guardados',
          },
        },

        {
          path: 'mis-registros',
          name: 'mis-registros',
          component: HomePageAdmin,
          props: true,
          meta: {
            sinPadding: true,
            requiresAuth: true,
          },
        },

        {
          path: 'mis-registros/:id',
          name: 'mis-registros-show',
          component: VerActualizacionAdmin,
          props: true,
          meta: {
            sinPadding: true,
            requiresAuth: true,
          },
        },

        {
          path: 'actualizaciones/nueva',
          name: 'actualizaciones-crear',
          component: EditarActualizacionAdmin,
          props: {
            modo: 'crear',
          },
          meta: {
            requiresAuth: true,
          },
        },

        {
          path: 'actualizaciones/:id/editar',
          name: 'actualizaciones-editar',
          component: EditarActualizacionAdmin,
          props: route => ({
            id: route.params.id,
            modo: 'editar',
          }),
          meta: {
            requiresAuth: true,
          },
        },

        {
          path: 'dashboard',
          name: 'dashboard',
          component: DashboardPage,
          meta: {
            sinPadding: true,
            requiresAuth: true,
            requiresSupervisor: true,
          },
        },

        {
          path: 'supervision',
          name: 'supervision',
          component: SupervisionPage,
          meta: {
            sinPadding: true,
            requiresAuth: true,
            requiresSupervisor: true,
          },
        },

        {
          path: 'supervision/:id',
          name: 'supervision-show',
          component: VerActualizacionAdmin,
          props: true,
          meta: {
            sinPadding: true,
            requiresAuth: true,
            requiresSupervisor: true,
          },
        },

        {
          path: 'employee/actualizaciones',
          redirect: {
            name: 'actualizaciones',
          },
        },

        {
          path: 'employee/actualizaciones/:id',
          redirect: to => ({
            name: 'actualizaciones-show',
            params: {
              id: to.params.id,
            },
          }),
        },

        {
          path: 'admin/actualizaciones/:id/editar',
          redirect: to => ({
            name: 'actualizaciones-editar',
            params: {
              id: to.params.id,
            },
          }),
        },
      ],
    },

    {
      path: '/:pathMatch(.*)*',
      redirect: '/',
    },
  ],
})

const LOCAL_DEMO_LOGIN_ENABLED = import.meta.env.DEV || import.meta.env.VITE_LOCAL_DEMO_LOGIN === 'true'
const LOCAL_DEMO_USER = import.meta.env.VITE_LOCAL_DEMO_USER || 'ADMIN'

function enviarAIntranet() {
  localStorage.removeItem('user_data')
  localStorage.removeItem('auth_token')

  if (INTRANET_ENTRY_URL) {
    window.location.href = INTRANET_ENTRY_URL
    return
  }

  document.body.innerHTML = `
    <main style="font-family: Arial, sans-serif; padding: 32px;">
      <h1>Sesión no iniciada</h1>
      <p>Este sistema no tiene inicio de sesión propio. Debes ingresar desde el menú de la intranet.</p>
    </main>
  `
}

function enviarALoginLocal() {
  localStorage.removeItem('user_data')
  localStorage.removeItem('auth_token')

  window.location.href = new URL(`/dev-login/${encodeURIComponent(LOCAL_DEMO_USER)}`, BACKEND_URL).toString()
}

router.beforeEach(async (to) => {
  const requiresAuth = to.matched.some(record => record.meta.requiresAuth)
  const requiresSupervisor = to.matched.some(record => record.meta.requiresSupervisor)

  if (!requiresAuth) {
    return true
  }

  const auth = await cargarAuth()

  if (!auth?.usuario) {
    if (LOCAL_DEMO_LOGIN_ENABLED) {
      enviarALoginLocal()
      return false
    }

    enviarAIntranet()
    return false
  }

  if (requiresSupervisor && !auth.es_admin && !auth.puede_supervisar_area) {
    return {
      name: 'inicio',
    }
  }

  return true
})

export { cargarAuth }
export default router