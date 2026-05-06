<template>
  <main class="pagina-principal">
    <h1>Panel de Administración del Blog</h1>
    
    <Lista />
    
  </main>
</template>

<script setup lang="ts">
// 1. Importas el componente asegurándote de usar la ruta correcta
import Lista from '../components/employees/List.vue';

// (Si tienes otros imports, van aquí)
</script>

<style scoped>
.pagina-principal {
  padding: 20px;
}
</style>