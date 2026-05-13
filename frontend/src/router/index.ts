import { createRouter, createWebHistory } from 'vue-router'
import api from '../api/api.ts'

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
      children: [
        {
          path: '',
          name: 'inicio',
          component: HomePage,
        },
        {
          path: 'employee/actualizaciones',
          name: 'employee-actualizaciones',
          component: ListaActualizaciones,
        },
        {
          path: 'employee/actualizaciones/:id',
          name: 'employee-actualizaciones-show',
          component: VerActualizacion,
          props: true,
          meta: { sinPadding: true },
        },
        {
          path: 'employee/guardados',
          name: 'employee-guardados',
          component: VerGuardados,
          meta: {
            requiresAuth: true,
            requiresEmployee: true
          },
        },
        {
          path: 'admin',
          name: 'inicioAdmin',
          component: HomePageAdmin,
          meta: {
            requiresAuth: true,
            requiresAdmin: true
          },
        },
        {
          path: 'admin/actualizaciones/:id',
          name: 'admin-actualizaciones-show',
          component: VerActualizacionAdmin,
          props: true,
          meta: {
            sinPadding: true,
            requiresAuth: true,
            requiresAdmin: true
          },
        },
        {
          path: 'admin/actualizaciones/:id/editar',
          name: 'admin-actualizaciones-edit',
          component: EditarActualizacionAdmin,
          props: true,
          meta: {
            requiresAuth: true,
            requiresAdmin: true
          },
        },
      ],
    },
  ],
})

let usuarioVerificado: any = null

router.beforeEach(async (to) => {

  const requiresAuth = to.matched.some(
    record => record.meta.requiresAuth
  )

  const requiresAdmin = to.matched.some(
    record => record.meta.requiresAdmin
  )

  const requiresEmployee = to.matched.some(
    record => record.meta.requiresEmployee
  )

  try {

    // SOLO consultar backend si aún no sabemos usuario
    if (!usuarioVerificado) {

      const response = await api.get('/me')

      usuarioVerificado = response.data.usuario

      localStorage.setItem(
        'user_data',
        JSON.stringify(usuarioVerificado)
      )
    }

    // SI intenta entrar al login teniendo sesión
    if (to.name === 'login') {

      if (
        usuarioVerificado.usuario_grupo === 'ADMIN'
      ) {

        return { name: 'inicioAdmin' }
      }

      return { name: 'inicio' }
    }

    // RUTA REQUIERE LOGIN
    if (requiresAuth && !usuarioVerificado) {

      return { name: 'login' }
    }

    // RUTA SOLO ADMIN
    if (
      requiresAdmin &&
      usuarioVerificado.usuario_grupo !== 'ADMIN'
    ) {

      return { name: 'inicio' }
    }

    // RUTA SOLO EMPLEADO
    if (
      requiresEmployee &&
      usuarioVerificado.usuario_grupo === 'ADMIN'
    ) {

      return { name: 'inicioAdmin' }
    }

    return true

  } catch {

    usuarioVerificado = null
    localStorage.removeItem('user_data')
    localStorage.removeItem('auth_token')
    window.location.href =
      'http://localhost/simulador_login.php'
    return false
  }
})

export default router