<template>
  <router-view />
  <Toaster 
    position="bottom-right" 
    richColors 
    expand
    :duration="4000"
    :toastOptions="{
      style: { background: 'white', border: '1px solid #e2e8f0' },
      className: 'mi-alerta-personalizada'
    }"
  />
</template>

<script setup lang="ts">
import { Toaster } from 'vue-sonner';
import 'vue-sonner/style.css';
</script>