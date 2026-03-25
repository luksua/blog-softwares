<template>
    <div class="container-fluid">
        <div class="cabecera">
            <h2>Actualizaciones</h2>
        </div>
        <div class="col-lg-10">
            <List />
        </div>
        <div class="col-lg-2">
            <!-- resumen -->
        </div>
    </div>
</template>

<script setup lang="ts">
// Cambié el nombre de la importación a 'List' para que sea más intuitivo, 
// pero llama exactamente al componente que acabas de crear.
import List from '../../components/employees/List.vue';

// Borramos la importación de MainLayout de aquí
</script>
