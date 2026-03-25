// src/router/index.ts
import { createRouter, createWebHistory } from 'vue-router';

// Importamos el Layout y la Lista que acabamos de crear
import MainLayout from '../layouts/MainLayout.vue'; // Tu layout con el sidebar
import HomePage from '../features/employee/HomePage.vue';
import HomePageAdmin from '../features/admin/HomePage.vue';

const router = createRouter({
  history: createWebHistory(),
  routes: [
    {
      path: '/',
      component: MainLayout, 
      children: [
        {
          path: '', // La ruta por defecto cuando entras a /
          name: 'inicio',
          component: HomePageAdmin // Se mostrará dentro del <router-view>
        }
      ]
    }
  ]
});

export default router;