import { createRouter, createWebHistory } from 'vue-router'
import { cargarAuth, USUARIO_DEMO } from '../api/auth'

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
        {
          path: '/notificaciones',
          name: 'notificaciones',
          component: () =>
            import('../components/home/Notifications.vue'),
        },
        {
          path: '/blog/mi-historial',
          name: 'mi-historial-lecturas',
          component: () =>
            import('../features/employee/Historial.vue'),
           meta: {
            sinPadding: true,
           }
        },
      ],
    },

    {
      path: '/:pathMatch(.*)*',
      redirect: '/',
    },
  ],
})

router.beforeEach(async (to) => {
  const requiresAuth = to.matched.some(record => record.meta.requiresAuth)
  const requiresSupervisor = to.matched.some(record => record.meta.requiresSupervisor)

  if (!requiresAuth) {
    return true
  }

  let auth = await cargarAuth()

  if (!auth?.usuario) {
    auth = USUARIO_DEMO
  }

  if (requiresSupervisor && !auth.es_admin && !auth.puede_supervisar_area) {
    return {
      name: 'inicio',
    }
  }

  return true
})

export default router