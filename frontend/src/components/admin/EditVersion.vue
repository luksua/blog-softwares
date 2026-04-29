<template>
  <div class="container">
    <div v-if="cargando" class="text-center py-5">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Cargando...</span>
      </div>
      <p class="mt-2">Cargando actualización...</p>
    </div>

    <div v-else>
      <form @submit.prevent="guardarCambios">

        <div class="col-md-12 mb-3">
          <label class="form-label fw-bold">Título</label>
          <input v-model="form.titulo" type="text" class="form-control" />
          <div v-if="errores.titulo || errores.actualizacion_titulo" class="text-danger small mt-1">
            {{ errores.titulo?.[0] || errores.actualizacion_titulo?.[0] }}
          </div>
        </div>
        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label fw-bold">Versión</label>
            <input v-model="form.version" type="text" class="form-control" />
            <div v-if="errores.version || errores.actualizacion_version" class="text-danger small mt-1">
              {{ errores.version?.[0] || errores.actualizacion_version?.[0] }}
            </div>
          </div>

          <div class="col-md-6 mb-3">
            <label class="form-label fw-bold">Imagen destacada (Portada)</label>

            <!-- Preview de la imagen actual -->
            <div v-if="previewImagen || form.imagen_destacada" class="mb-2">
              <img :src="previewImagen || obtenerUrlImagen(form.imagen_destacada)" alt="Portada actual"
                class="img-thumbnail" style="max-height: 150px; border-radius: 8px;" />
              <div class="form-text">
                {{ previewImagen ? 'Nueva imagen seleccionada' : 'Imagen actual' }}
              </div>
            </div>

            <input type="file" class="form-control" accept="image/*" @change="manejarImagen" />
          </div>

        </div>

        <div class="mb-3">
          <label class="form-label fw-bold">Resumen</label>
          <textarea v-model="form.resumen" class="form-control" rows="3"></textarea>
          <div v-if="errores.resumen || errores.actualizacion_resumen" class="text-danger small mt-1">
            {{ errores.resumen?.[0] || errores.actualizacion_resumen?.[0] }}
          </div>
        </div>

        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label fw-bold">Estado</label>
            <select v-model="form.estado" class="form-select">
              <option value="borrador">Borrador</option>
              <option value="revision">Revisión</option>
              <option value="publicado">Publicado</option>
              <option value="inactivo">Inactivo</option>
            </select>
            <div v-if="errores.estado || errores.actualizacion_estado" class="text-danger small mt-1">
              {{ errores.estado?.[0] || errores.actualizacion_estado?.[0] }}
            </div>
          </div>
          <!-- <div class="mb-3">
          <template v-if="form.estado !== 'publicado'">
            <label class="form-label fw-bold">Fecha de creación</label>
            <input v-model="form.fecha_creacion" type="date" class="form-control bg-light" readonly disabled />
            <div class="form-text mt-1">
              Este registro aún no se ha publicado. Mostrando fecha de creación.
            </div>
          </template>

<template v-else>
            <label class="form-label fw-bold text-success">Fecha de publicación</label>
            <input v-model="form.fecha_publicacion" type="date" class="form-control border-success" />
            <div v-if="errores.fecha_publicacion || errores.actualizacion_fecha_publicacion"
              class="text-danger small mt-1">
              {{ errores.fecha_publicacion?.[0] || errores.actualizacion_fecha_publicacion?.[0] }}
            </div>
            <div class="form-text mt-1 text-success">
              El registro es visible públicamente desde esta fecha.
            </div>
          </template>
</div> -->

          <div class="col-md-6 mb-3">
            <template v-if="form.estado !== 'publicado'">
              <label class="form-label fw-bold">Fecha de creación</label>
              <input v-model="form.fecha_creacion" type="date" class="form-control bg-light" readonly disabled />
              <div class="form-text mt-1">
                Registro no publicado. Mostrando fecha de creación.
              </div>
            </template>

            <template v-else>
              <label class="form-label fw-bold text-success">Fecha de publicación</label>
              <input v-model="form.fecha_publicacion" type="date" class="form-control bg-light border-success"
                readonly />
              <div v-if="errores.fecha_publicacion || errores.actualizacion_fecha_publicacion"
                class="text-danger small mt-1">
                {{ errores.fecha_publicacion?.[0] || errores.actualizacion_fecha_publicacion?.[0] }}
              </div>
              <div class="form-text mt-1 text-success fw-bold">
                <i class="bi bi-check-circle me-1"></i>
                El registro se publicará con esta fecha.
              </div>
            </template>
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label fw-bold">Contenido</label>
          <div ref="editorHolder" id="editorjs" class="editor-container border p-3 bg-white"></div>
        </div>

        <div class="d-flex justify-content-end gap-2 mt-4">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" ref="btnCerrarEdit">Cancelar</button>
          <button type="submit" class="btn-primary" :disabled="guardando">
            {{ guardando ? 'Guardando...' : 'Guardar cambios' }}
          </button>
        </div>

        <div v-if="mensajeOk" class="alert alert-success mt-3 mb-0">
          {{ mensajeOk }}
        </div>
      </form>
    </div>
  </div>

</template>

<script setup lang="ts">
import { reactive, ref, shallowRef, watch, nextTick, onBeforeUnmount } from 'vue';
import api from '../../api/api';
import type { NewVersion } from '../../types/newVersion';

// Importaciones de Editor.js
import EditorJS from '@editorjs/editorjs';
import Header from '@editorjs/header';
import ImageTool from '@editorjs/image';
import List from '@editorjs/list';

const props = defineProps<{
  id: string | number
}>();

const emit = defineEmits(['guardado', 'cerrar']);

const btnCerrarEdit = ref<HTMLButtonElement | null>(null);

const cargando = ref(true);
const guardando = ref(false);
const mensajeOk = ref('');
const errores = ref<Record<string, string[]>>({});

const editorHolder = ref<HTMLElement | null>(null);
const editor = shallowRef<EditorJS | null>(null);

const archivoImagen = ref<File | null>(null)
const previewImagen = ref<string | null>(null)

const manejarImagen = (event: Event) => {
  const target = event.target as HTMLInputElement
  if (target.files && target.files.length > 0) {
    archivoImagen.value = target.files[0]
    previewImagen.value = URL.createObjectURL(target.files[0])
  } else {
    archivoImagen.value = null
    previewImagen.value = null
  }
}

const obtenerUrlImagen = (ruta: string) => {
  if (!ruta) return ''
  if (ruta.startsWith('http')) return ruta
  const baseUrl = import.meta.env.VITE_API_URL || 'http://localhost:8000'
  return `${baseUrl}/storage/${ruta}`
}

// 1. Objeto reactivo usando tu Interfaz
const form = reactive<NewVersion>({
  titulo: '',
  version: '',
  contenido: '',
  resumen: '',
  imagen_destacada: '',
  area_servicio_id: null,
  usuario_id_autor: null,
  estado: 'borrador',
  fecha_creacion: '',
  fecha_publicacion: ''
});

// 2. Función universal para manejar Timestamps o Strings ISO
const formatearFechaParaInput = (fecha: any) => {
  if (!fecha) return '';

  const fechaObj = typeof fecha === 'number' && fecha < 10000000000
    ? new Date(fecha * 1000)
    : new Date(fecha);

  // Verificamos que sea una fecha válida antes de extraer el YYYY-MM-DD
  if (isNaN(fechaObj.getTime())) return '';
  return fechaObj.toISOString().split('T')[0];
};

const initEditor = (initialData: any = {}) => {
  if (editor.value) {
    editor.value.destroy();
  }

  editor.value = new EditorJS({
    holder: editorHolder.value!,
    data: initialData,
    tools: {
      header: Header,
      list: List,
      image: {
        class: ImageTool,
        config: {
          uploader: {
            async uploadByFile(file: File) {
              try {
                const formData = new FormData();
                formData.append('imagen', file);
                const respuesta = await api.post('/admin/subir-imagen-blog', formData);
                return {
                  success: 1,
                  file: { url: respuesta.data.url }
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
};

// Observamos los cambios en el select de "estado"
watch(() => form.estado, (nuevoEstado, viejoEstado) => {
  if (cargando.value) return;
  // Si el usuario acaba de seleccionar "Publicado"
  if (nuevoEstado === 'publicado') {

    // Asignamos la fecha de hoy SI:
    // 1. La fecha de publicación estaba vacía (era un borrador nuevo)
    // 2. O si el estado anterior era 'inactivo', 'borrador' o 'revision' (lo está republicando)
    if (!form.fecha_publicacion || viejoEstado === 'inactivo' || viejoEstado === 'borrador' || viejoEstado === 'revision') {
      form.fecha_publicacion = new Date().toISOString().split('T')[0];
    }
  }

  // OPCIONAL: Si pasa a inactivo o borrador, limpiamos la fecha de publicación
  // para que si en el futuro se vuelve a publicar, sí o sí tome la nueva fecha.
  else if (nuevoEstado === 'inactivo' || nuevoEstado === 'borrador') {
    form.fecha_publicacion = '';
  }
});

const cargarRegistro = async () => {
  cargando.value = true;
  errores.value = {};
  mensajeOk.value = '';

  try {
    const respuesta = await api.get(`/admin/actualizaciones/${props.id}`);
    const data = respuesta.data.data;

    // 3. Mapeo limpio: De los datos de la API a tu interfaz NewVersion
    form.titulo = data.actualizacion_titulo ?? '';
    form.version = data.actualizacion_version ?? '';
    form.imagen_destacada = data.actualizacion_imagen_destacada ?? '';
    form.resumen = data.actualizacion_resumen ?? '';
    form.estado = data.actualizacion_estado ?? 'borrador';

    // Procesamos el timestamp
    form.fecha_publicacion = formatearFechaParaInput(data.actualizacion_fecha_publicacion);
    form.fecha_creacion = formatearFechaParaInput(data.actualizacion_fecha_creacion);

    const contenidoBD = data.actualizacion_contenido;

    await nextTick();

    let editorData = {};
    if (contenidoBD) {
      try {
        editorData = typeof contenidoBD === 'string' ? JSON.parse(contenidoBD) : contenidoBD;
      } catch (e) {
        console.error("Error al parsear el JSON:", e);
      }
    }

    initEditor(editorData);

  } catch (error) {
    console.error('Error al cargar registro:', error);
  } finally {
    cargando.value = false;
  }
};

const guardarCambios = async () => {
  guardando.value = true;
  errores.value = {};
  mensajeOk.value = '';

  let rutaImagen = form.imagen_destacada;

  try {
    let contenidoFinal = null;

    if (editor.value) {
      const editorData = await editor.value.save();
      contenidoFinal = editorData;
      form.contenido = editorData;
    }

    if (archivoImagen.value) {
      const imgData = new FormData();
      imgData.append('imagen', archivoImagen.value);

      const res = await api.post('/admin/subir-imagen-portada', imgData, {
        headers: {
          'Content-Type': 'multipart/form-data',
        },
      });

      rutaImagen = res.data.path;
    }

    const payload = {
      actualizacion_titulo: form.titulo,
      actualizacion_version: form.version,
      actualizacion_resumen: form.resumen,
      actualizacion_imagen_destacada: rutaImagen,
      actualizacion_estado: form.estado,
      actualizacion_fecha_publicacion: form.fecha_publicacion,
      actualizacion_contenido: contenidoFinal,
    };

    await api.post(`/admin/actualizaciones/${props.id}`, payload);

    mensajeOk.value = 'Guardado exitosamente.';
    emit('guardado');
  } catch (error: any) {
    console.error('Error al guardar:', error);

    if (error.response?.status === 422) {
      errores.value = error.response.data.errors || {};
    }
  } finally {
    guardando.value = false;
  }
};

// 6. Prevenir fugas de memoria limpiando el editor al cerrar el componente
onBeforeUnmount(() => {
  if (editor.value && typeof editor.value.destroy === 'function') {
    editor.value.destroy();
  }
});

watch(
  () => props.id,
  () => {
    if (props.id) {
      cargarRegistro();
    }
  },
  { immediate: true }
);
</script>

<style scoped>
:deep(.codex-editor__redactor) {
  padding-bottom: 20px !important;
  /* el valor que prefieras */
}

.editor-container {
  min-height: 150px;
}
</style>