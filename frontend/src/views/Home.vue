<template>
  <main class="pagina-principal">
    <h1>Panel de Administración del Blog</h1>
    
    <Lista />
    
  </main>
</template>

<script setup lang="ts">
import Lista from '../components/home/List.vue';

</script>

<style scoped>
.pagina-principal {
  padding: 20px;
}
</style>