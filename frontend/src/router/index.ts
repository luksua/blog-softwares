import { createRouter, createWebHistory } from 'vue-router'
import api, { BACKEND_URL, INTRANET_ENTRY_URL, ensureCsrfCookie } from '../api/api.ts'

import MainLayout from '../layouts/MainLayout.vue'
import HomePage from '../features/employee/HomePage.vue'
import HomePageAdmin from '../features/admin/HomePage.vue'
import SupervisionPage from '../features/admin/SupervisionPage.vue'
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

let authVerificado: any = null
let intentoMeRealizado = false

function normalizarPermisos(valor: any): string[] {
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

async function cargarAuth() {
  if (authVerificado) {
    return authVerificado
  }

  if (intentoMeRealizado) {
    return null
  }

  intentoMeRealizado = true

  try {
    await ensureCsrfCookie()

    const response = await api.get('/me')

    const data = response.data
    const usuario = data.usuario || data.user || data
    const permisos = normalizarPermisos(data.permisos || usuario?.permisos || [])

    const grupo = String(usuario.usuario_grupo || '').toUpperCase()
    const esAdmin = data.es_admin ?? grupo === 'ADMIN'
    const puedeSupervisarArea =
      data.puede_supervisar_area ||
      data.tipo_usuario === 'admin' ||
      usuario.tipo_usuario === 'admin' ||
      permisos.includes('blog.supervisar_area')

    authVerificado = {
      usuario,
      permisos,
      es_admin: esAdmin,
      puede_supervisar_area: puedeSupervisarArea,
    }

    localStorage.setItem('user_data', JSON.stringify(usuario))

    return authVerificado
  } catch (error) {
    console.error(error)

    authVerificado = null
    localStorage.removeItem('user_data')
    localStorage.removeItem('auth_token')

    return null
  }
}

function enviarAIntranet() {
  localStorage.removeItem('user_data')
  localStorage.removeItem('auth_token')

  // qxr: login temporal local para trabajar sin la BD de intranet actualizada.
  if (import.meta.env.DEV) {
    window.location.href = `${BACKEND_URL}/qxr-dev-login`
    return
  }

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

router.beforeEach(async (to) => {
  const requiresAuth = to.matched.some(record => record.meta.requiresAuth)
  const requiresSupervisor = to.matched.some(record => record.meta.requiresSupervisor)

  if (!requiresAuth) {
    return true
  }

  const auth = await cargarAuth()

  if (!auth?.usuario) {
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

export default router
