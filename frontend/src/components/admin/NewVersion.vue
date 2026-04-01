<template>
  <div>
    <form @submit.prevent="guardarRegistro">

      <div class="mb-3">
        <label for="titulo" class="form-label fw-bold">Título:</label>
        <input type="text" id="titulo" class="form-control" v-model="registro.titulo" required />
      </div>

      <div class="mb-3">
        <label for="titulo" class="form-label fw-bold">Número de Version:</label>
        <input type="text" id="titulo" class="form-control" v-model="registro.version" required />
      </div>

      <div class="mb-3">
        <label for="resumen" class="form-label fw-bold">Resumen:</label>
        <textarea id="resumen" class="form-control" v-model="registro.resumen" rows="2" required></textarea>
      </div>

      <div class="mb-3">
        <label for="contenido" class="form-label fw-bold">Contenido:</label>
        <textarea id="contenido" class="form-control" v-model="registro.contenido" rows="4" required></textarea>
      </div>

      <div class="mb-3">
        <label for="miniatura" class="form-label fw-bold">URL de la Miniatura:</label>
        <input type="url" id="miniatura" class="form-control" v-model="registro.imagen_destacada" placeholder="https://..." />
      </div>

      <div class="row">
        <div class="col-md-6 mb-3">
          <label for="area_servicio" class="form-label fw-bold">ID Área:</label>
          <input type="number" id="area_servicio" class="form-control" v-model="registro.area_servicio_id" required />
        </div>
        <div class="col-md-6 mb-3">
          <label for="usuario_id_autor" class="form-label fw-bold">ID Autor:</label>
          <input type="number" id="usuario_id_autor" class="form-control" v-model="registro.usuario_id_autor" required />
        </div>
      </div>

      <div class="row">
        <div class="col-md-6 mb-3">
          <label for="estado" class="form-label fw-bold">Estado:</label>
          <select id="estado" class="form-select" v-model="registro.estado" required>
            <option value="borrador">Borrador</option>
            <option value="revision">En Revisión</option>
            <option value="publicado">Publicado</option>
          </select>
        </div>
        <div class="col-md-6 mb-3">
          <label for="fecha_publicacion" class="form-label fw-bold">Fecha:</label>
          <input type="date" id="fecha_publicacion" class="form-control" v-model="registro.fecha_publicacion" required disabled/>
        </div>
      </div>

      <div class="mb-3">
        <label for="imagenes" class="form-label fw-bold">Imágenes de la Galería:</label>
        <input type="file" id="imagenes" class="form-control" multiple accept="image/png, image/jpeg, image/webp" @change="manejarSeleccionImagenes" />
      </div>

      <div v-if="previsualizaciones.length > 0" class="mb-3">
        <p class="fw-bold mb-2">Vista previa:</p>
        <div class="d-flex flex-wrap gap-2">
          <div v-for="(url, index) in previsualizaciones" :key="index" class="position-relative">
            <img :src="url" alt="Previa" class="img-thumbnail" style="width: 80px; height: 80px; object-fit: cover;" />
            <button type="button" @click="quitarImagen(index)" class="btn btn-danger btn-sm position-absolute top-0 start-100 translate-middle rounded-circle p-1" style="width: 24px; height: 24px; line-height: 0.5;">X</button>
          </div>
        </div>
      </div>

      <div class="d-flex justify-content-end gap-2 mt-4">
        <button type="button" class="btn btn-secondary" @click="$emit('cerrar')">Cancelar</button>
        <button type="submit" class="btn-primary" :disabled="enviando">
          {{ enviando ? 'Guardando...' : 'Guardar Registro' }}
        </button>
      </div>

    </form>
  </div>
</template>

<script setup lang="ts">
import { reactive, ref } from 'vue';
import api from '../../api/api.ts';
import type { NewVersion } from '../../types/newVersion.ts';

// Estado del formulario
const registro = reactive<NewVersion>({
  titulo: '',
  version: '',
  contenido: '',
  resumen: '',
  imagen_destacada: '',
  area_servicio_id: null,
  usuario_id_autor: null,
  estado: 'borrador', // Coincide con el value del <select>
  fecha_creacion: new Date().toISOString().split('T')[0],
  fecha_publicacion: new Date().toISOString().split('T')[0]
});

// Estados para manejar las imágenes y el botón
const archivosImagenes = ref<File[]>([]);
const previsualizaciones = ref<string[]>([]);
const enviando = ref(false);

// Procesa los archivos cuando el usuario los selecciona
const manejarSeleccionImagenes = (event: Event) => {
  const target = event.target as HTMLInputElement;

  if (target.files) {
    const nuevosArchivos = Array.from(target.files);

    // Guardamos los archivos físicos
    archivosImagenes.value.push(...nuevosArchivos);

    // Creamos URLs temporales para la vista previa
    nuevosArchivos.forEach(archivo => {
      previsualizaciones.value.push(URL.createObjectURL(archivo));
    });
  }

  target.value = ''; // Resetea el input
};

// Elimina una imagen de la lista antes de enviar
const quitarImagen = (index: number) => {
  URL.revokeObjectURL(previsualizaciones.value[index]); // Libera memoria
  previsualizaciones.value.splice(index, 1);
  archivosImagenes.value.splice(index, 1);
};

const emit = defineEmits(['cerrar', 'recargar-lista']);

// Envía los datos al backend
const guardarRegistro = async () => {
  enviando.value = true;

  try {
    const formData = new FormData();
    emit('recargar-lista');
    emit('cerrar');

    // Textos y números
    formData.append('actualizacion_titulo', registro.titulo);
    formData.append('actualizacion_version', registro.version);
    formData.append('actualizacion_contenido', registro.contenido);
    formData.append('actualizacion_resumen', registro.resumen);
    // Si imagen_destacada está vacío, enviamos un string vacío para evitar enviar "null" como texto
    formData.append('actualizacion_imagen_destacada', registro.imagen_destacada || '');
    formData.append('actualizacion_area_servicio_id', String(registro.area_servicio_id));
    formData.append('actualizacion_usuario_id_autor', String(registro.usuario_id_autor));
    formData.append('actualizacion_estado', registro.estado);
    formData.append('actualizacion_fecha_publicacion', registro.fecha_publicacion || '');

    // Imágenes físicas
    archivosImagenes.value.forEach((archivo) => {
      formData.append('imagenes[]', archivo);
    });

    // Cambia el puerto/dominio por el de tu proyecto de Laravel
    await api.post('/admin/actualizaciones', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    });

    alert('¡Registro guardado con éxito!');
    limpiarFormulario();

  } catch (error) {
    console.error('Error al guardar:', error);
    alert('Hubo un problema al guardar los datos.');
  } finally {
    enviando.value = false;
  }
};

// Resetea el formulario después de guardar
const limpiarFormulario = () => {
  Object.assign(registro, {
    actualizacion_titulo: '',
    actualizacion_contenido: '',
    actualizacion_resumen: '',
    actualizacion_area_servicio_id: null,
    actualizacion_usuario_id_autor: null,
    actualizacion_estado: 'borrador',
    actualizacion_fecha_publicacion: new Date().toISOString().split('T')[0]
  });

  previsualizaciones.value.forEach(url => URL.revokeObjectURL(url));
  previsualizaciones.value = [];
  archivosImagenes.value = [];
};
</script>

<style scoped>
/* Estilos básicos para que el formulario se vea ordenado */
.form-container {
  max-width: 600px;
  margin: 0 auto;
  padding: 20px;
  background-color: #f9f9f9;
  border-radius: 8px;
  font-family: sans-serif;
}

.form-group {
  margin-bottom: 15px;
  display: flex;
  flex-direction: column;
}

.form-group label {
  margin-bottom: 5px;
  font-weight: bold;
  color: #333;
}

.form-group input,
.form-group textarea,
.form-group select {
  padding: 10px;
  border: 1px solid #ccc;
  border-radius: 4px;
  font-size: 1rem;
}

.form-group input:focus,
.form-group textarea:focus,
.form-group select:focus {
  outline: none;
  border-color: #4CAF50;
}

.form-actions {
  margin-top: 20px;
  text-align: right;
}

</style>