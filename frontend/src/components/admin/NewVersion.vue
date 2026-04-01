<template>
  <div>
    <form @submit.prevent="guardarRegistro">

      <div class="mb-3">
        <label for="titulo" class="form-label fw-bold">Título:</label>
        <input type="text" id="titulo" class="form-control" v-model="registro.titulo" required />
      </div>

      <div class="mb-3">
        <label for="version" class="form-label fw-bold">Número de Version:</label>
        <input type="text" id="version" class="form-control" v-model="registro.version" required />
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
        <input type="url" id="miniatura" class="form-control" v-model="registro.imagen_destacada"
          placeholder="https://..." />
      </div>

      <div class="row">
        <div class="col-md-6 mb-3">
          <label for="area_servicio" class="form-label fw-bold">Área:</label>
          <select id="area_servicio" class="form-select" v-model="registro.area_servicio_id" required>
            <option value="" disabled>Selecciona un área...</option>
            <option v-for="area in listaAreas" :key="area.area_servicio_id" :value="area.area_servicio_id">
              {{ area.area_servicio_nombre }}
            </option>
          </select>
        </div>

        <div class="col-md-6 mb-3">
          <label for="usuario_id_autor" class="form-label fw-bold">ID Autor (Automático):</label>
          <input type="number" id="usuario_id_autor" class="form-control" v-model="registro.usuario_id_autor" disabled
            required title="Se asignará tu ID de usuario automáticamente" />
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
          <input type="date" id="fecha_publicacion" class="form-control" v-model="registro.fecha_publicacion" required
            disabled />
        </div>
      </div>

      <div class="mb-3">
        <label for="imagenes" class="form-label fw-bold">Imágenes de la Galería:</label>
        <input type="file" id="imagenes" class="form-control" multiple accept="image/png, image/jpeg, image/webp"
          @change="manejarSeleccionImagenes" />
      </div>

      <div v-if="previsualizaciones.length > 0" class="mb-3">
        <p class="fw-bold mb-2">Vista previa:</p>
        <div class="d-flex flex-wrap gap-2">
          <div v-for="(url, index) in previsualizaciones" :key="index" class="position-relative">
            <img :src="url" alt="Previa" class="img-thumbnail" style="width: 80px; height: 80px; object-fit: cover;" />
            <button type="button" @click="quitarImagen(index)"
              class="btn btn-danger btn-sm position-absolute top-0 start-100 translate-middle rounded-circle p-1"
              style="width: 24px; height: 24px; line-height: 0.5;">X</button>
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
import { reactive, ref, onMounted } from 'vue';
import api from '../../api/api';
// Si tu type NewVersion requiere que area sea null al inicio, asegúrate de que el select lo soporte
import type { NewVersion } from '../../types/newVersion.ts';
import type { Area } from '../../types/areas.ts';

// --- MOCK DE AUTENTICACIÓN ---
// Cuando tengas Auth (ej. con Pinia o leyendo de localStorage), cambias este valor.
const idUsuarioLogueado = 1;

const registro = reactive<NewVersion>({
  titulo: '',
  version: '',
  contenido: '',
  resumen: '',
  imagen_destacada: '',
  area_servicio_id: '' as any, // Inicializado vacío para el select
  usuario_id_autor: idUsuarioLogueado, // Asignación automática del autor
  estado: 'borrador',
  fecha_creacion: new Date().toISOString().split('T')[0],
  fecha_publicacion: new Date().toISOString().split('T')[0]
});

const listaAreas = ref<Area[]>([]);

const obtenerAreas = async () => {
  try {
    const respuesta = await api.get('/admin/area-servicio'); 
    listaAreas.value = respuesta.data.data;
  } catch (error) {
    console.error('Error al cargar las áreas:', error);
    alert('No se pudieron cargar las áreas disponibles.');
  }
};

// Estados para manejar las imágenes y el botón
const archivosImagenes = ref<File[]>([]);
const previsualizaciones = ref<string[]>([]);
const enviando = ref(false);

const emit = defineEmits(['cerrar', 'recargar-lista']);

// Se ejecuta apenas carga el componente
onMounted(() => {
  obtenerAreas();
});

// Procesa los archivos cuando el usuario los selecciona
const manejarSeleccionImagenes = (event: Event) => {
  const target = event.target as HTMLInputElement;

  if (target.files) {
    const nuevosArchivos = Array.from(target.files);
    archivosImagenes.value.push(...nuevosArchivos);
    nuevosArchivos.forEach(archivo => {
      previsualizaciones.value.push(URL.createObjectURL(archivo));
    });
  }
  target.value = '';
};

const quitarImagen = (index: number) => {
  URL.revokeObjectURL(previsualizaciones.value[index]);
  previsualizaciones.value.splice(index, 1);
  archivosImagenes.value.splice(index, 1);
};

// Envía los datos al backend
const guardarRegistro = async () => {
  enviando.value = true;

  try {
    const formData = new FormData();

    // Textos y números
    formData.append('actualizacion_titulo', registro.titulo);
    formData.append('actualizacion_version', registro.version);
    formData.append('actualizacion_contenido', registro.contenido);
    formData.append('actualizacion_resumen', registro.resumen);
    formData.append('actualizacion_imagen_destacada', registro.imagen_destacada || '');
    formData.append('actualizacion_area_servicio_id', String(registro.area_servicio_id));
    formData.append('actualizacion_usuario_id_autor', String(registro.usuario_id_autor));
    formData.append('actualizacion_estado', registro.estado);
    formData.append('actualizacion_fecha_publicacion', registro.fecha_publicacion || '');

    // Imágenes físicas
    archivosImagenes.value.forEach((archivo) => {
      formData.append('imagenes[]', archivo);
    });

    await api.post('/admin/actualizaciones', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    });

    alert('¡Registro guardado con éxito!');
    limpiarFormulario();

    // Los emits van AQUÍ, después de que el guardado fue exitoso
    emit('recargar-lista');
    emit('cerrar');

  } catch (error) {
    console.error('Error al guardar:', error);
    alert('Hubo un problema al guardar los datos.');
  } finally {
    enviando.value = false;
  }
};

const limpiarFormulario = () => {
  // CORRECCIÓN: Se deben limpiar las propiedades usando las llaves de tu interface `NewVersion`
  Object.assign(registro, {
    titulo: '',
    version: '',
    contenido: '',
    resumen: '',
    imagen_destacada: '',
    area_servicio_id: '' as any,
    usuario_id_autor: idUsuarioLogueado, // Mantenemos el ID del autor
    estado: 'borrador',
    fecha_publicacion: new Date().toISOString().split('T')[0]
  });

  previsualizaciones.value.forEach(url => URL.revokeObjectURL(url));
  previsualizaciones.value = [];
  archivosImagenes.value = [];
};
</script>

<style scoped>
/* Tus estilos se mantienen exactamente igual */
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

.form-actions {
  margin-top: 20px;
  text-align: right;
}
</style>