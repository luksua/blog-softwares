<template>
    <div class="container-fluid">
        <div class="cabecera mb-4"> <h2>Actualizaciones</h2>
        </div>
        
        <div class="row">
            <div class="col-12 col-lg-9">
                <List /> 
            </div>
            
            <div class="col-12 col-lg-3">
                <div class="p-3 bg-light rounded">
                </div>
            </div>
        </div>
    </div>
</template>
<script setup lang="ts">
// Cambié el nombre de la importación a 'List' para que sea más intuitivo, 
// pero llama exactamente al componente que acabas de crear.
import List from '../../components/employees/List.vue';

// Borramos la importación de MainLayout de aquí
</script>
