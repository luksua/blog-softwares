import { createRouter, createWebHistory } from 'vue-router'
import api, { INTRANET_ENTRY_URL, ensureCsrfCookie } from '../api/api.ts'

import MainLayout from '../layouts/MainLayout.vue'
import HomePage from '../features/employee/HomePage.vue'
import HomePageAdmin from '../features/admin/HomePage.vue'
import ListaActualizaciones from '../components/employees/List.vue'
import VerActualizacion from '../components/employees/ListVersion.vue'
import VerActualizacionAdmin from '../components/admin/ListVersion.vue'
import EditarActualizacionAdmin from '../components/admin/EditVersion.vue'
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
            requiresEmployee: true,
          },
        },
        {
          path: 'employee/actualizaciones',
          name: 'employee-actualizaciones',
          component: ListaActualizaciones,
          meta: {
            requiresEmployee: true,
          },
        },
        {
          path: 'employee/actualizaciones/:id',
          name: 'employee-actualizaciones-show',
          component: VerActualizacion,
          props: true,
          meta: {
            sinPadding: true,
            requiresEmployee: true,
          },
        },
        {
          path: 'employee/guardados',
          name: 'employee-guardados',
          component: VerGuardados,
          meta: {
            requiresEmployee: true,
          },
        },
        // {
        //   path: 'admin',
        //   name: 'inicioAdmin',
        //   component: HomePageAdmin,
        //   meta: {
        //     requiresEmployee: true,
        //   },
        // },
        {
          path: 'mis-registros',
          name: 'mis-registros',
          component: HomePageAdmin,
          props: true,
          meta: {
            sinPadding: true,
            requiresEmployee: true,
          },
        },
        {
          path: 'mis-registros/:id',
          name: 'mis-registros-show',
          component: VerActualizacionAdmin,
          props: true,
          meta: {
            sinPadding: true,
            requiresEmployee: true,
          },
        },
        {
          path: 'admin/actualizaciones/:id/editar',
          name: 'admin-actualizaciones-edit',
          component: EditarActualizacionAdmin,
          props: true,
          meta: {
            requiresAdmin: true,
          },
        },
      ],
    },
    {
      path: '/:pathMatch(.*)*',
      redirect: '/',
    },
  ],
})

let usuarioVerificado: any = null
let intentoMeRealizado = false

async function cargarUsuario() {
  if (usuarioVerificado) {
    return usuarioVerificado
  }

  if (intentoMeRealizado) {
    return null
  }

  intentoMeRealizado = true

  try {
    await ensureCsrfCookie()

    const response = await api.get('/me')

    usuarioVerificado = response.data.usuario

    localStorage.setItem('user_data', JSON.stringify(usuarioVerificado))

    return usuarioVerificado
  } catch {
    usuarioVerificado = null
    localStorage.removeItem('user_data')
    localStorage.removeItem('auth_token')

    return null
  }
}

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

router.beforeEach(async (to) => {
  const requiresAuth = to.matched.some(record => record.meta.requiresAuth)
  const requiresAdmin = to.matched.some(record => record.meta.requiresAdmin)
  const requiresEmployee = to.matched.some(record => record.meta.requiresEmployee)

  if (!requiresAuth) {
    return true
  }

  const usuario = await cargarUsuario()

  if (!usuario) {
    enviarAIntranet()
    return false
  }

  const grupo = String(usuario.usuario_grupo || '').toUpperCase()
  const esAdmin = grupo === 'ADMIN'

  if (requiresAdmin && !esAdmin) {
    return { name: 'inicio' }
  }

  if (requiresEmployee && esAdmin) {
    return { name: 'inicioAdmin' }
  }

  return true
})

export default router
