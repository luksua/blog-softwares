<template>
  <div class="form-container">
    <h2>Nuevo Registro</h2>

    <form @submit.prevent="guardarRegistro" class="formulario">

      <div class="form-group">
        <label for="titulo">Título:</label>
        <input type="text" id="titulo" v-model="registro.titulo" required />
      </div>

      <div class="form-group">
        <label for="resumen">Resumen:</label>
        <textarea id="resumen" v-model="registro.resumen" rows="3" required></textarea>
      </div>

      <div class="form-group">
        <label for="contenido">Contenido:</label>
        <textarea id="contenido" v-model="registro.contenido" rows="6" required></textarea>
      </div>

      <div class="form-group">
        <label for="miniatura">URL de la Miniatura:</label>
        <input type="url" id="miniatura" v-model="registro.imagen_destacada" placeholder="https://..." />
      </div>

      <div class="form-group">
        <label for="area_servicio">ID Área de Servicio:</label>
        <input type="number" id="area_servicio" v-model="registro.area_servicio_id" required />
      </div>

      <div class="form-group">
        <label for="usuario_id_autor">ID del Autor:</label>
        <input type="number" id="usuario_id_autor" v-model="registro.usuario_id_autor" required />
      </div>

      <div class="form-group">
        <label for="estado">Estado:</label>
        <select id="estado" v-model="registro.estado" required>
          <option value="borrador">Borrador</option>
          <option value="revision">En Revisión</option>
          <option value="publicado">Publicado</option>
        </select>
      </div>

      <div class="form-group">
        <label for="fecha_publicacion">Fecha de Publicación:</label>
        <input type="date" id="fecha_publicacion" v-model="registro.fecha_publicacion" required />
      </div>

      <div class="form-group">
        <label for="imagenes">Imágenes de la Galería:</label>
        <input type="file" id="imagenes" multiple accept="image/png, image/jpeg, image/webp"
          @change="manejarSeleccionImagenes" />
      </div>

      <div v-if="previsualizaciones.length > 0" class="preview-container">
        <p>Vista previa:</p>
        <div class="imagenes-grid">
          <div v-for="(url, index) in previsualizaciones" :key="index" class="imagen-preview">
            <img :src="url" alt="Previsualización" />
            <button type="button" @click="quitarImagen(index)" class="btn-quitar">X</button>
          </div>
        </div>
      </div>

      <div class="form-actions">
        <button type="submit" :disabled="enviando">
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
  contenido: '',
  resumen: '',
  imagen_destacada: '',
  area_servicio_id: null,
  usuario_id_autor: null,
  estado: 'borrador', // Coincide con el value del <select>
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

// Envía los datos al backend
const guardarRegistro = async () => {
  enviando.value = true;

  try {
    const formData = new FormData();

    // Textos y números
    formData.append('titulo', registro.titulo);
    formData.append('contenido', registro.contenido);
    formData.append('resumen', registro.resumen);
    // Si imagen_destacada está vacío, enviamos un string vacío para evitar enviar "null" como texto
    formData.append('imagen_destacada', registro.imagen_destacada || '');
    formData.append('area_servicio_id', String(registro.area_servicio_id));
    formData.append('usuario_id_autor', String(registro.usuario_id_autor));
    formData.append('estado', registro.estado);
    formData.append('fecha_publicacion', registro.fecha_publicacion);

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
    titulo: '',
    contenido: '',
    resumen: '',
    imagen_destacada: '',
    area_servicio_id: null,
    usuario_id_autor: null,
    estado: 'borrador',
    fecha_publicacion: new Date().toISOString().split('T')[0]
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

button {
  padding: 10px 20px;
  background-color: #4CAF50;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-size: 1rem;
}

button:disabled {
  background-color: #9e9e9e;
  cursor: not-allowed;
}

button:hover:not(:disabled) {
  background-color: #45a049;
}
</style>