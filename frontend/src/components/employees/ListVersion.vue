<template>
  <div class="vista-detalle-container">
    
    <div v-if="cargando" class="text-center py-5">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Cargando...</span>
      </div>
      <p class="mt-2">Cargando actualización...</p>
    </div>

    <div v-else-if="actualizacion" class="card shadow-sm">
      
      <div class="card-header bg-white border-bottom-0 pt-4 pb-0 px-4">
        

      <div class="pb-5 d-flex justify-content-start">
        <button class="btn btn-secondary" @click="$emit('cerrar')">
          Volver
        </button>
      </div>
        <div class="d-flex justify-content-between align-items-center mb-2">
          <span class="badge bg-primary">Versión {{ actualizacion.actualizacion_version }}</span>
          <!-- <span class="badge bg-secondary text-capitalize">{{ actualizacion.actualizacion_estado }}</span> -->
        </div>
        <h1 class="card-title h2 fw-bold mb-1">{{ actualizacion.actualizacion_titulo }}</h1>
        <p class="text-muted small mb-0">
          Publicado el: {{ formatearFecha(actualizacion.actualizacion_fecha_publicacion) }}
        </p>
      </div>

      <div class="card-body px-4 py-3">
        <div v-if="actualizacion.actualizacion_imagen_destacada" class="mb-4 text-center">
          <img :src="actualizacion.actualizacion_imagen_destacada" alt="Portada" class="img-fluid rounded max-altura-portada">
        </div>

        <div class="alert alert-custom mb-4">
           {{ actualizacion.actualizacion_resumen }}
        </div>

        <div class="ql-container ql-snow border-0">
          <div class="ql-editor px-0" v-html="actualizacion.actualizacion_contenido"></div>
        </div>
      </div>

    </div>

    <div v-else class="alert alert-danger">
      No se pudo cargar la información de esta actualización.
    </div>

  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import api from '../../api/api'; // Ajusta la ruta a tu archivo api

// Importamos el CSS de Quill para que las listas, sangrías y estilos se vean bien
import '@vueup/vue-quill/dist/vue-quill.snow.css';

const props = defineProps<{
  id: number;
}>();

const emit = defineEmits(['cerrar']);

const actualizacion = ref<any>(null);
const cargando = ref(true);

const obtenerDetalle = async () => {
  cargando.value = true;
  try {
    // Llamamos a la API usando el ID que nos pasan por prop
    const respuesta = await api.get(`/admin/actualizaciones/${props.id}`);
    actualizacion.value = respuesta.data.data; // Ajusta según cómo devuelva los datos tu API
  } catch (error) {
    console.error('Error al cargar la actualización:', error);
  } finally {
    cargando.value = false;
  }
};

onMounted(() => {
  obtenerDetalle();
});

// Función simple para formatear la fecha
const formatearFecha = (fechaString: string) => {
  if (!fechaString) return 'Sin fecha';
  const opciones: Intl.DateTimeFormatOptions = { year: 'numeric', month: 'long', day: 'numeric' };
  return new Date(fechaString).toLocaleDateString('es-ES', opciones);
};
</script>

<style scoped>
.vista-detalle-container {
  max-width: 900px;
  margin: 0 auto;
}

.max-altura-portada {
  max-height: 350px;
  object-fit: cover;
  width: 100%;
}

.alert-custom {
    background-color: #dbdbdb;
}

/* Ajustes finos para que el editor de lectura se vea como un blog limpio */
:deep(.ql-editor) {
  font-family: system-ui, -apple-system, sans-serif;
  font-size: 1.1rem;
  line-height: 1.6;
  color: #333;
}

:deep(.ql-editor img) {
  border-radius: 8px;
  box-shadow: 0 4px 6px rgba(0,0,0,0.1);
  margin-top: 15px;
  margin-bottom: 15px;
}
</style>