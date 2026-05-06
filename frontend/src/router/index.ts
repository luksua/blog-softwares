import { createRouter, createWebHistory } from 'vue-router';

import MainLayout from '../layouts/MainLayout.vue';
import HomePage from '../features/employee/HomePage.vue';
import HomePageAdmin from '../features/admin/HomePage.vue';
import ListaActualizaciones from '../components/employees/List.vue';
import VerActualizacion from '../components/employees/ListVersion.vue';
import VerActualizacionAdmin from '../components/admin/ListVersion.vue';
import EditarActualizacionAdmin from '../components/admin/EditVersion.vue';
import VerGuardados from '../features/employee/Bookmarks.vue';

const router = createRouter({
  history: createWebHistory(),
  routes: [
    // ── 1. RUTA PÚBLICA (SIN LAYOUT) ──
    {
      path: '/login',
      name: 'login',
      component: () => import('../features/Login.vue'),
      meta: { requiresAuth: false }
    },

    // ── 2. RUTAS CON EL LAYOUT PRINCIPAL ──
    {
      path: '/',
      component: MainLayout,
      children: [
        // Rutas públicas / Empleados normales (No requieren auth simulado en este ejemplo)
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
          meta: { sinPadding: true }
        },
        {
          path: 'employee/guardados',
          name: 'employee-guardados',
          component: VerGuardados,
        },

        // Rutas de Administración (SÍ requieren auth simulado)
        {
          path: 'admin',
          name: 'inicioAdmin',
          component: HomePageAdmin,
          meta: { requiresAuth: true } // Protegida
        },
        {
          path: 'admin/actualizaciones/:id',
          name: 'admin-actualizaciones-show',
          component: VerActualizacionAdmin,
          props: true,
          meta: { sinPadding: true, requiresAuth: true } // Protegida
        },
        // 👇 FIX: Modifiqué el path para que no sea idéntico al de "show"
        {
          path: 'admin/actualizaciones/:id/editar', 
          name: 'admin-actualizaciones-edit',
          component: EditarActualizacionAdmin,
          props: true,
          meta: { requiresAuth: true } // Protegida
        },
      ],
    },
  ],
});

// ── 3. GUARD DE NAVEGACIÓN (Protección de Rutas) ──
router.beforeEach((to, from, next) => {
  // Verificamos si la ruta a la que quiere ir exige autenticación
  const requiresAuth = to.matched.some(record => record.meta.requiresAuth);
  
  // Revisamos si existe el token en el almacenamiento del navegador
  const isAuthenticated = !!localStorage.getItem('auth_token');

  if (requiresAuth && !isAuthenticated) {
    // Si la ruta está protegida y NO hay token, pateamos al usuario al login
    next({ name: 'login' });
  } else if (to.name === 'login' && isAuthenticated) {
    // Si intenta ir a la pantalla de login pero YA está logueado, lo mandamos al inicio admin
    next({ name: 'inicioAdmin' });
  } else {
    // En cualquier otro caso, lo dejamos continuar normalmente
    next();
  }
});

export default router;