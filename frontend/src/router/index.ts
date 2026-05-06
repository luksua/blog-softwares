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
      path: '/login',
      name: 'login',
      component: () => import('../features/Login.vue'),
      meta: { requiresAuth: false },
    },
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
        },
        {
          path: 'admin',
          name: 'inicioAdmin',
          component: HomePageAdmin,
          meta: { requiresAuth: true },
        },
        {
          path: 'admin/actualizaciones/:id',
          name: 'admin-actualizaciones-show',
          component: VerActualizacionAdmin,
          props: true,
          meta: { sinPadding: true, requiresAuth: true },
        },
        {
          path: 'admin/actualizaciones/:id/editar',
          name: 'admin-actualizaciones-edit',
          component: EditarActualizacionAdmin,
          props: true,
          meta: { requiresAuth: true },
        },
      ],
    },
  ],
})

let usuarioVerificado: any = null
let verificacionRealizada = false

router.beforeEach(async (to) => {
  const requiresAuth = to.matched.some(record => record.meta.requiresAuth)

  if (!requiresAuth && to.name !== 'login') {
    return true
  }

  try {
    if (!verificacionRealizada) {
      const response = await api.get('/me')

      usuarioVerificado = response.data.user
      verificacionRealizada = true

      localStorage.setItem('user_data', JSON.stringify(usuarioVerificado))
      localStorage.setItem('auth_token', 'laravel-session')
    }

    if (to.name === 'login' && usuarioVerificado) {
      return { name: 'inicioAdmin' }
    }

    return true

  } catch {
    usuarioVerificado = null
    verificacionRealizada = true

    localStorage.removeItem('auth_token')
    localStorage.removeItem('user_data')

    if (requiresAuth) {
      return { name: 'login' }
    }

    return true
  }
})

export default router

// v2

// import { createRouter, createWebHistory } from 'vue-router'
// import api from '../api/api.ts'

// import MainLayout from '../layouts/MainLayout.vue'
// import HomePage from '../features/employee/HomePage.vue'
// import HomePageAdmin from '../features/admin/HomePage.vue'
// import ListaActualizaciones from '../components/employees/List.vue'
// import VerActualizacion from '../components/employees/ListVersion.vue'
// import VerActualizacionAdmin from '../components/admin/ListVersion.vue'
// import EditarActualizacionAdmin from '../components/admin/EditVersion.vue'
// import VerGuardados from '../features/employee/Bookmarks.vue'

// const router = createRouter({
//   history: createWebHistory(),
//   routes: [
//     {
//       path: '/login',
//       name: 'login',
//       component: () => import('../features/Login.vue'),
//       meta: { requiresAuth: false },
//     },
//     {
//       path: '/',
//       component: MainLayout,
//       children: [
//         {
//           path: '',
//           name: 'inicio',
//           component: HomePage,
//         },
//         {
//           path: 'employee/actualizaciones',
//           name: 'employee-actualizaciones',
//           component: ListaActualizaciones,
//         },
//         {
//           path: 'employee/actualizaciones/:id',
//           name: 'employee-actualizaciones-show',
//           component: VerActualizacion,
//           props: true,
//           meta: { sinPadding: true },
//         },
//         {
//           path: 'employee/guardados',
//           name: 'employee-guardados',
//           component: VerGuardados,
//         },
//         {
//           path: 'admin',
//           name: 'inicioAdmin',
//           component: HomePageAdmin,
//           meta: { requiresAuth: true },
//         },
//         {
//           path: 'admin/actualizaciones/:id',
//           name: 'admin-actualizaciones-show',
//           component: VerActualizacionAdmin,
//           props: true,
//           meta: { sinPadding: true, requiresAuth: true },
//         },
//         {
//           path: 'admin/actualizaciones/:id/editar',
//           name: 'admin-actualizaciones-edit',
//           component: EditarActualizacionAdmin,
//           props: true,
//           meta: { requiresAuth: true },
//         },
//       ],
//     },
//   ],
// })

// let usuarioVerificado: any = null
// let verificacionRealizada = false

// router.beforeEach(async (to) => {
//   const requiresAuth = to.matched.some(record => record.meta.requiresAuth)

//   if (!requiresAuth && to.name !== 'login') {
//     return true
//   }

//   try {
//     if (!verificacionRealizada) {
//       const response = await api.get('/me')

//       usuarioVerificado = response.data.user
//       verificacionRealizada = true

//       localStorage.setItem('user_data', JSON.stringify(usuarioVerificado))
//       localStorage.setItem('auth_token', 'laravel-session')
//     }

//     if (to.name === 'login' && usuarioVerificado) {
//       return { name: 'inicioAdmin' }
//     }

//     return true

//   } catch {
//     usuarioVerificado = null
//     verificacionRealizada = true

//     localStorage.removeItem('auth_token')
//     localStorage.removeItem('user_data')

//     if (requiresAuth) {
//       return { name: 'login' }
//     }

//     return true
//   }
// })

// export default router