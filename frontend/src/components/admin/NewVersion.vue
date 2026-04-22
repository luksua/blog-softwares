<template>
  <div>
    <form @submit.prevent="guardarRegistro" class="form-container-x">
      <div class="mb-3">
        <label for="titulo" class="form-label fw-bold">Título *</label>
        <input type="text" id="titulo" class="form-control" v-model="registro.titulo" required />
      </div>
      <div class="row">
        <div class="col-md-6 mb-3">
          <label for="version" class="form-label fw-bold">Número de Versión</label>
          <input type="text" id="version" class="form-control" v-model="registro.version" required />
        </div>
        <div class="col-md-6 mb-3">
          <label for="miniatura" class="form-label fw-bold">Portada</label>
          <input type="file" id="miniatura" class="form-control" accept="image/*" @change="manejarArchivoMiniatura" />

          <div v-if="previewMiniatura" class="mt-2 text-center">
            <img :src="previewMiniatura" alt="Vista previa" class="img-thumbnail"
              style="max-height: 120px; border-radius: 8px;" />
          </div>
        </div>
      </div>

      <div class="mb-3">
        <label for="resumen" class="form-label fw-bold">Resumen</label>
        <textarea id="resumen" class="form-control" v-model="registro.resumen" rows="2" required></textarea>
      </div>

      <div class="mb-4">
        <label class="form-label fw-bold">Contenido *</label>
        <div id="editorjs" class="editor-container border p-3"></div>
      </div>

      <div class="row">
        <div class="col-md-6 mb-3">
          <label for="area_servicio" class="form-label fw-bold">Área *</label>
          <select id="area_servicio" class="form-select" v-model="registro.area_servicio_id" required>
            <option value="" disabled>Selecciona un área...</option>
            <option v-for="area in listaAreas" :key="area.area_servicio_id" :value="area.area_servicio_id">
              {{ area.area_servicio_nombre }}
            </option>
          </select>
        </div>

        <div class="col-md-6 mb-3">
          <label for="usuario_id_autor" class="form-label fw-bold">ID Autor:</label>
          <input type="number" id="usuario_id_autor" class="form-control" v-model="registro.usuario_id_autor" disabled
            required />
        </div>
      </div>

      <div class="row">
        <div class="col-md-6 mb-3">
          <label for="estado" class="form-label fw-bold">Estado</label>
          <select id="estado" class="form-select" v-model="registro.estado" required>
            <option v-for="estado in listaEstados" :key="estado.id" :value="estado.id">
              {{ estado.nombre }}
            </option>
          </select>
        </div>
        <div class="col-md-6 mb-3">
          <label for="fecha_publicacion" class="form-label fw-bold">Fecha:</label>
          <input type="date" id="fecha_publicacion" class="form-control" v-model="registro.fecha_publicacion" required
            disabled />
        </div>
      </div>

      <div class="d-flex justify-content-end gap-2 mt-4">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn-primary" :disabled="enviando">
          {{ enviando ? 'Guardando...' : 'Publicar Registro' }}
        </button>
      </div>

    </form>
  </div>
</template>

<script setup lang="ts">
import { reactive, ref, onMounted, onBeforeUnmount } from 'vue';
import api from '../../api/api';
import type { NewVersion } from '../../types/newVersion';
import type { Area } from '../../types/areas';

// Importaciones de Editor.js
import EditorJS from '@editorjs/editorjs';
import Header from '@editorjs/header';
import ImageTool from '@editorjs/image';
import List from '@editorjs/list';

const archivoMiniatura = ref<File | null>(null);
const previewMiniatura = ref<string | null>(null);

const manejarArchivoMiniatura = (event: Event) => {
  const target = event.target as HTMLInputElement;
  if (target.files && target.files.length > 0) {
    const file = target.files[0];
    archivoMiniatura.value = file;
    previewMiniatura.value = URL.createObjectURL(file);
  } else {
    archivoMiniatura.value = null;
    previewMiniatura.value = null;
  }
};

const idUsuarioLogueado = 1;

const registro = reactive<NewVersion>({
  titulo: '',
  version: '',
  contenido: '',
  resumen: '',
  imagen_destacada: '',
  area_servicio_id: '' as any,
  usuario_id_autor: idUsuarioLogueado,
  estado: 'borrador',
  fecha_creacion: new Date().toISOString().split('T')[0],
  fecha_publicacion: new Date().toISOString().split('T')[0],
  imagenes_quill: [] // Puedes renombrar esto o eliminarlo si ya no llevas el tracking manual
});

const listaAreas = ref<Area[]>([]);
const listaEstados = ref<{ id: string, nombre: string }[]>([]);
const enviando = ref(false);
const mensajeOk = ref('');
const emit = defineEmits(['cerrar', 'recargar-lista']);

// Referencia para guardar la instancia de Editor.js
const editorInstance = ref<EditorJS | null>(null);

onMounted(async () => {
  // Cargar áreas y estados
  try {
    const resAreas = await api.get('/admin/area-servicio');
    listaAreas.value = resAreas.data.data;
  } catch (error) {
    console.error('Error al cargar las áreas:', error);
  }

  try {
    const resEstados = await api.get('/admin/estados-actualizacion');
    listaEstados.value = resEstados.data.data;
  } catch (error) {
    console.error('Error al cargar los estados:', error);
  }

  // --- INICIALIZAR EDITOR.JS ---
  editorInstance.value = new EditorJS({
    holder: 'editorjs',
    placeholder: 'Escribe tu actualización aquí. Puedes arrastrar imágenes...',
    tools: {
      header: Header,
      list: List,
      image: {
        class: ImageTool,
        config: {
          // Usamos un uploader personalizado para aprovechar tu configuración de Axios
          uploader: {
            async uploadByFile(file: File) {
              try {
                const formData = new FormData();
                formData.append('imagen', file);

                // Enviamos a tu backend en Laravel
                const respuesta = await api.post('/admin/subir-imagen-blog', formData);

                // Editor.js exige retornar el éxito y la URL en este formato específico
                return {
                  success: 1,
                  file: {
                    url: respuesta.data.url
                  }
                };
              } catch (error) {
                console.error('Error subiendo imagen:', error);
                alert('Hubo un error al subir la imagen.');
                return { success: 0 };
              }
            }
          }
        }
      }
    }
  });
});

// Limpiar la instancia de Editor.js cuando se destruye el componente
onBeforeUnmount(() => {
  if (editorInstance.value) {
    editorInstance.value.destroy();
  }
});

const guardarRegistro = async () => {
  // 1. Extraer los datos del editor
  let outputData;
  mensajeOk.value = '';
  if (editorInstance.value) {
    outputData = await editorInstance.value.save();
  }

  // Validar si está vacío (Editor.js devuelve un array de bloques)
  if (!outputData || outputData.blocks.length === 0) {
    alert('El contenido del blog no puede estar vacío.');
    return;
  }

  enviando.value = true;

  try {
    const formData = new FormData();

    formData.append('actualizacion_titulo', registro.titulo);
    formData.append('actualizacion_version', registro.version);

    // Convertimos los bloques JSON a un string para guardarlo en la base de datos
    formData.append('actualizacion_contenido', JSON.stringify(outputData));

    formData.append('actualizacion_resumen', registro.resumen);

    if (archivoMiniatura.value) {
      formData.append('actualizacion_imagen_destacada', archivoMiniatura.value);
    }

    formData.append('actualizacion_area_servicio_id', String(registro.area_servicio_id));
    formData.append('actualizacion_usuario_id_autor', String(registro.usuario_id_autor));
    formData.append('actualizacion_estado', registro.estado);
    formData.append('actualizacion_fecha_publicacion', registro.fecha_publicacion || '');

    await api.post('/admin/actualizaciones', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    });

    mensajeOk.value = 'Registro guardado con éxito';
    limpiarFormulario();
    emit('recargar-lista');
    emit('cerrar');

  } catch (error) {
    console.error('Error al guardar:', error);
  } finally {
    enviando.value = false;
  }
};

const limpiarFormulario = () => {
  Object.assign(registro, {
    titulo: '',
    version: '',
    contenido: '',
    resumen: '',
    imagen_destacada: '',
    area_servicio_id: '' as any,
    usuario_id_autor: idUsuarioLogueado,
    estado: 'borrador',
    fecha_publicacion: new Date().toISOString().split('T')[0]
  });

  archivoMiniatura.value = null;
  previewMiniatura.value = null;

  const inputMiniatura = document.getElementById('miniatura') as HTMLInputElement;
  if (inputMiniatura) inputMiniatura.value = '';

  // Limpiar también el editor visualmente
  if (editorInstance.value) {
    editorInstance.value.clear();
  }
};
</script>

<style scoped>
.editor-container {
  background-color: white;
  border-radius: 4px;
  min-height: 300px;
}

/* Puedes borrar los estilos de :deep(.ql-editor) y relacionados a Quill */

.form-container {
  max-width: 800px;
  margin: 0 auto;
  padding: 20px;
  background-color: #f9f9f9;
  border-radius: 8px;
  font-family: sans-serif;
}
</style>