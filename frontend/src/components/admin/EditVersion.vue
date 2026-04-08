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
                <div class="mb-3">
                    <label class="form-label fw-bold">Título</label>
                    <input v-model="form.actualizacion_titulo" type="text" class="form-control" />
                    <div v-if="errores.actualizacion_titulo" class="text-danger small mt-1">
                        {{ errores.actualizacion_titulo[0] }}
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Versión</label>
                    <input v-model="form.actualizacion_version" type="text" class="form-control" />
                    <div v-if="errores.actualizacion_version" class="text-danger small mt-1">
                        {{ errores.actualizacion_version[0] }}
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Resumen</label>
                    <textarea v-model="form.actualizacion_resumen" class="form-control" rows="3"></textarea>
                    <div v-if="errores.actualizacion_resumen" class="text-danger small mt-1">
                        {{ errores.actualizacion_resumen[0] }}
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Fecha de publicación</label>
                    <input v-model="form.actualizacion_fecha_publicacion" type="date" class="form-control" />
                    <div v-if="errores.actualizacion_fecha_publicacion" class="text-danger small mt-1">
                        {{ errores.actualizacion_fecha_publicacion[0] }}
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Estado</label>
                    <select v-model="form.actualizacion_estado" class="form-select">
                        <option value="borrador">Borrador</option>
                        <option value="revision">Revisión</option>
                        <option value="publicado">Publicado</option>
                        <option value="inactivo">Inactivo</option>
                    </select>
                    <div v-if="errores.actualizacion_estado" class="text-danger small mt-1">
                        {{ errores.actualizacion_estado[0] }}
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Contenido</label>
                    <div ref="editorHolder" class="editor-container border p-3"></div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
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
import { reactive, ref, watch } from 'vue';
import api from '../../api/api';

const props = defineProps<{
  id: string | number
}>();

const emit = defineEmits(['guardado', 'cerrar']);

const cargando = ref(true);
const guardando = ref(false);
const mensajeOk = ref('');
const errores = ref<Record<string, string[]>>({});

const form = reactive({
  actualizacion_titulo: '',
  actualizacion_version: '',
  actualizacion_resumen: '',
  actualizacion_fecha_publicacion: '',
  actualizacion_estado: 'borrador',
  actualizacion_contenido: ''
});

const cargarRegistro = async () => {
  cargando.value = true;
  errores.value = {};
  mensajeOk.value = '';

  try {
    const respuesta = await api.get(`/admin/actualizaciones/${props.id}`);
    const data = respuesta.data.data;

    form.actualizacion_titulo = data.actualizacion_titulo ?? '';
    form.actualizacion_version = data.actualizacion_version ?? '';
    form.actualizacion_resumen = data.actualizacion_resumen ?? '';
    form.actualizacion_fecha_publicacion = data.actualizacion_fecha_publicacion ?? '';
    form.actualizacion_estado = data.actualizacion_estado ?? 'borrador';
    form.actualizacion_contenido = data.actualizacion_contenido ?? '';
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

  try {
    await api.post(`/admin/actualizaciones/${props.id}`, form);
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