<template>
  <div>
    <form @submit.prevent="guardarRegistro">

      <div class="mb-3">
        <label for="titulo" class="form-label fw-bold">Título:</label>
        <input type="text" id="titulo" class="form-control" v-model="registro.titulo" required />
      </div>

      <div class="row">
        <div class="col-md-6 mb-3">
          <label for="version" class="form-label fw-bold">Número de Versión:</label>
          <input type="text" id="version" class="form-control" v-model="registro.version" required />
        </div>
        <div class="col-md-6 mb-3">
          <label for="miniatura" class="form-label fw-bold">Miniatura (Portada):</label>
          <input type="file" id="miniatura" class="form-control" accept="image/*" @change="manejarArchivoMiniatura" />

          <div v-if="previewMiniatura" class="mt-2 text-center">
            <img :src="previewMiniatura" alt="Vista previa" class="img-thumbnail"
              style="max-height: 120px; border-radius: 8px;" />
          </div>
        </div>
      </div>

      <div class="mb-3">
        <label for="resumen" class="form-label fw-bold">Resumen corto:</label>
        <textarea id="resumen" class="form-control" v-model="registro.resumen" rows="2" required></textarea>
      </div>

      <div class="mb-4">
        <label class="form-label fw-bold">Contenido:</label>
        <div class="editor-container">
          <QuillEditor theme="snow" v-model:content="registro.contenido" contentType="html" :options="opcionesEditor"
            @ready="manejarArrastreImagen" placeholder="Escribe tu actualización aquí. Puedes arrastrar imágenes..." />
        </div>
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
          <label for="usuario_id_autor" class="form-label fw-bold">ID Autor:</label>
          <input type="number" id="usuario_id_autor" class="form-control" v-model="registro.usuario_id_autor" disabled
            required />
        </div>
      </div>

      <div class="row">
        <div class="col-md-6 mb-3">
          <label for="estado" class="form-label fw-bold">Estado:</label>
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
        <button type="button" class="btn btn-secondary" @click="$emit('cerrar')">Cancelar</button>
        <button type="submit" class="btn-primary" :disabled="enviando">
          {{ enviando ? 'Guardando...' : 'Publicar Registro' }}
        </button>
      </div>

    </form>
  </div>
</template>

<script setup lang="ts">
import { reactive, ref, onMounted } from 'vue';
import api from '../../api/api';
import type { NewVersion } from '../../types/newVersion';
import type { Area } from '../../types/areas';
// Variables para manejar la portada
const archivoMiniatura = ref<File | null>(null);
const previewMiniatura = ref<string | null>(null);

// Función que captura el archivo y genera la vista previa
const manejarArchivoMiniatura = (event: Event) => {
  const target = event.target as HTMLInputElement;
  if (target.files && target.files.length > 0) {
    const file = target.files[0];
    archivoMiniatura.value = file;
    // Generar una URL temporal para mostrar la vista previa en el HTML
    previewMiniatura.value = URL.createObjectURL(file);
  } else {
    archivoMiniatura.value = null;
    previewMiniatura.value = null;
  }
};

// QUILL
import type Quill from 'quill';
import { QuillEditor } from '@vueup/vue-quill';
import '@vueup/vue-quill/dist/vue-quill.snow.css';

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
  imagenes_quill: []
});

const listaAreas = ref<Area[]>([]);
const listaEstados = ref<{ id: string, nombre: string }[]>([]);
const enviando = ref(false);
const emit = defineEmits(['cerrar', 'recargar-lista']);

// AÑADIDO: Unificamos los onMounted en uno solo para mejor orden
onMounted(async () => {
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
});

// --- INICIO: LÓGICA DE IMÁGENES PARA QUILL ---

// 1. Función compartida que sube la imagen a Laravel
const subirImagenAlServidor = async (file: File, quillInstance: Quill, index: number) => {
  try {
    const formData = new FormData();
    formData.append('imagen', file);

    // Asegúrate de tener esta ruta creada en tu Laravel
    const respuesta = await api.post('/admin/subir-imagen-blog', formData);

    // Inserta la URL que devuelve Laravel dentro del editor
    quillInstance.insertEmbed(index, 'image', respuesta.data.url);
  } catch (error) {
    console.error('Error subiendo imagen:', error);
    alert('Hubo un error al subir la imagen.');
  }
};

// 2. Evento para cuando el usuario ARRASTRA la imagen
const manejarArrastreImagen = (quillInstance: Quill) => {
  const root = quillInstance.root;

  root.addEventListener('drop', async (e: DragEvent) => {
    e.preventDefault();
    if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files.length) {
      const file = e.dataTransfer.files[0];
      if (/^image\//.test(file.type)) {
        const range = quillInstance.getSelection(true);
        await subirImagenAlServidor(file, quillInstance, range.index);
      }
    }
  }, false);
};

// 3. Configuración para el BOTÓN de imagen en la barra de herramientas
const opcionesEditor = {
  // Envolvemos todo en "modules" porque ahora usamos la propiedad :options
  modules: {
    toolbar: {
      container: [
        ['bold', 'italic', 'underline'],
        [{ 'header': 1 }, { 'header': 2 }],
        ['image', 'link']
      ],
      handlers: {
        // SOLUCIÓN AL ERROR DE TS: Le decimos explícitamente a TypeScript 
        // que "this" será inyectado dinámicamente usando (this: any)
        image: function (this: any) {
          const quill = this.quill as Quill;

          const input = document.createElement('input');
          input.setAttribute('type', 'file');
          input.setAttribute('accept', 'image/*');
          input.click();

          input.onchange = async () => {
            if (input.files && input.files.length > 0) {
              const file = input.files[0];
              const range = quill.getSelection(true);

              // Llamamos a nuestra función que sube la imagen al servidor
              await subirImagenAlServidor(file, quill, range.index);
            }
          };
        }
      }
    }
  }
};
// --- FIN: LÓGICA DE IMÁGENES PARA QUILL ---

const guardarRegistro = async () => {
  if (!registro.contenido || registro.contenido === '<p><br></p>') {
    alert('El contenido del blog no puede estar vacío.');
    return;
  }

  enviando.value = true;

  try {
    const formData = new FormData();

    formData.append('actualizacion_titulo', registro.titulo);
    formData.append('actualizacion_version', registro.version);
    formData.append('actualizacion_contenido', registro.contenido);
    formData.append('actualizacion_resumen', registro.resumen);

    // Si hay una imagen seleccionada, la agregamos al FormData
    if (archivoMiniatura.value) {
      formData.append('actualizacion_imagen_destacada', archivoMiniatura.value);
    }

    formData.append('actualizacion_area_servicio_id', String(registro.area_servicio_id));
    formData.append('actualizacion_usuario_id_autor', String(registro.usuario_id_autor));
    formData.append('actualizacion_estado', registro.estado);
    formData.append('actualizacion_fecha_publicacion', registro.fecha_publicacion || '');
    formData.append('imagenes_quill', JSON.stringify(registro.imagenes_quill));

    await api.post('/admin/actualizaciones', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    });

    alert('¡Registro guardado con éxito!');
    limpiarFormulario();
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
  
  // Limpiar archivo y vista previa
  archivoMiniatura.value = null;
  previewMiniatura.value = null;
  
  // Limpiar el input file visualmente
  const inputMiniatura = document.getElementById('miniatura') as HTMLInputElement;
  if (inputMiniatura) inputMiniatura.value = '';
};
</script>

<style scoped>
.editor-container {
  background-color: white;
  border-radius: 4px;
}

:deep(.ql-editor) {
  min-height: 300px;
  font-size: 1rem;
  font-family: inherit;
}

:deep(.ql-toolbar) {
  border-top-left-radius: 4px;
  border-top-right-radius: 4px;
  background-color: #f8f9fa;
}

:deep(.ql-container) {
  border-bottom-left-radius: 4px;
  border-bottom-right-radius: 4px;
}

.form-container {
  max-width: 800px;
  margin: 0 auto;
  padding: 20px;
  background-color: #f9f9f9;
  border-radius: 8px;
  font-family: sans-serif;
}
</style>