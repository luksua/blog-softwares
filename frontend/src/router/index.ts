// src/router/index.ts
import { createRouter, createWebHistory } from 'vue-router';

import MainLayout from '../layouts/MainLayout.vue';
import HomePage from '../features/employee/HomePage.vue';
import HomePageAdmin from '../features/admin/HomePage.vue';
import ListaActualizaciones from '../components/employees/List.vue';
import VerActualizacion from '../components/employees/ListVersion.vue';
import VerActualizacionAdmin from '../components/admin/ListVersion.vue';
import EditarActualizacionAdmin from '../components/admin/EditVersion.vue'

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
          component: HomePageAdmin,
        },
        {
          path: '/admin',
          name: 'inicioAdmin',
          component: HomePageAdmin,
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
          meta: {
            sinPadding: true
          }
        },
        {
          path: 'admin/actualizaciones/:id',
          name: 'admin-actualizaciones-show',
          component: VerActualizacionAdmin,
          props: true,
          meta: {
            sinPadding: true
          }
        },
        {
          path: 'admin/actualizaciones/:id',
          name: 'admin-actualizaciones-edit',
          component: EditarActualizacionAdmin,
          props: true,
        },
      ],
    },
  ],
});

export default router;