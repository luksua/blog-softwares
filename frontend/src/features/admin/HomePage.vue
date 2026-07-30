<template>
  <div class="pagina-principal contenedor-lista container-fluid">
    <PageHero eyebrow="Panel de registros" titulo="Registros del área">
      <template v-if="!esVistaSupervision" #acciones>
        <button type="button" class="btn-primary" @click="abrirModalNuevo">
          +<i class="bi bi-pencil-fill"></i> Nuevo Registro
        </button>
      </template>
    </PageHero>

    <div class="row">
      <div class="col-lg-12">
        <List ref="componenteLista" :vista="props.vista" @duplicar="abrirModalDuplicado" />
      </div>
    </div>

    <div v-if="!esVistaSupervision" class="modal fade" id="modalNuevoRegistro" ref="modalNuevoRegistroRef" tabindex="-1"
      aria-labelledby="modalLabel" aria-hidden="true">
      <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable modal-registro-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title fw-bold" id="modalLabel">
              {{ datosDuplicado ? 'Duplicar Registro' : 'Registrar Nueva Actualización' }}
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"
              @click="datosDuplicado = null"></button>
          </div>

          <div class="modal-body p-4">
            <Store v-if="mostrarFormulario" :key="claveFormularioNuevo" :datos-iniciales="datosDuplicado"
              @recargar-lista="avisarALaLista" @cerrar="cerrarModalBootstrap" />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Modal } from 'bootstrap'
import {
  computed,
  nextTick,
  onBeforeUnmount,
  onMounted,
  ref,
} from 'vue'

import List from '../../components/register/List.vue'
import Store from '../../components/register/NewVersion.vue'
import PageHero from '../../components/shared/PageHero.vue'
import type { DatosDuplicado } from '../../types/datos'
import type { DatosDuplicadoEntrada } from '../../types/datos'

import type {
  OutputBlockData,
} from '@editorjs/editorjs'

const props = withDefaults(
  defineProps<{
    vista?: 'mis-registros' | 'supervision'
  }>(),
  {
    vista: 'mis-registros',
  },
)

const componenteLista = ref<InstanceType<typeof List> | null>(null)
const modalNuevoRegistroRef = ref<HTMLElement | null>(null)

const esVistaSupervision = computed(
  () => props.vista === 'supervision',
)

/* =========================================================
 * ESTADO DEL FORMULARIO
 * =======================================================*/
const datosDuplicado = ref<DatosDuplicado | null>(null)
const claveFormularioNuevo = ref(0)
const mostrarFormulario = ref(false)

/* =========================================================
 * PARSEO SEGURO
 * =======================================================*/
const parsearJsonSeguro = (valor: unknown): unknown => {
  let resultado = valor

  for (let intento = 0; intento < 3; intento++) {
    if (typeof resultado !== 'string') {
      break
    }

    const texto = resultado.trim()

    if (!texto) {
      return []
    }

    try {
      resultado = JSON.parse(texto)
    } catch (error) {
      console.error(
        'No se pudo interpretar el contenido de EditorJS:',
        error,
        resultado,
      )

      return []
    }
  }

  return resultado
}

const extraerBlocks = (
  valor: unknown,
): OutputBlockData[] => {
  const contenido = parsearJsonSeguro(valor)

  if (Array.isArray(contenido)) {
    return structuredClone(
      contenido,
    ) as OutputBlockData[]
  }

  if (
    contenido &&
    typeof contenido === 'object'
  ) {
    const objeto =
      contenido as Record<string, unknown>

    if ('blocks' in objeto) {
      return extraerBlocks(objeto.blocks)
    }

    if ('data' in objeto) {
      return extraerBlocks(objeto.data)
    }

    if ('contenido' in objeto) {
      return extraerBlocks(objeto.contenido)
    }

    if ('actualizacion_contenido' in objeto) {
      return extraerBlocks(
        objeto.actualizacion_contenido,
      )
    }
  }

  console.warn(
    'No se encontraron bloques válidos de EditorJS:',
    contenido,
  )

  return []
}

const normalizarDatosDuplicado = (
  payload: DatosDuplicadoEntrada,
): DatosDuplicado => {
  const bloques = extraerBlocks(
    payload.contenidoBlocks,
  )

  console.log(
    'Contenido original recibido:',
    payload.contenidoBlocks,
  )

  console.log(
    'Bloques enviados a EditorJS:',
    structuredClone(bloques),
  )

  console.table(
    bloques.map((bloque, indice) => ({
      indice,
      id: bloque.id,
      tipo: bloque.type,
      estilo:
        bloque.type === 'list'
          ? bloque.data?.style
          : '',
      items:
        bloque.type === 'list' &&
        Array.isArray(bloque.data?.items)
          ? bloque.data.items.length
          : '',
    })),
  )

  return {
    titulo: payload.titulo ?? '',
    resumen: payload.resumen ?? '',
    area_servicio_id:
      payload.area_servicio_id ?? '',

    actualizacion_categoria_ids:
      Array.isArray(
        payload.actualizacion_categoria_ids,
      )
        ? [
            ...payload.actualizacion_categoria_ids,
          ]
        : [],
    contenidoBlocks: bloques,
  }
}


/* =========================================================
 * APERTURA DEL MODAL
 * =======================================================*/

const mostrarModalFormulario = async () => {
  /*
   * Desmontamos cualquier instancia anterior.
   */
  mostrarFormulario.value = false

  await nextTick()

  const elementoModal = modalNuevoRegistroRef.value

  if (!elementoModal) {
    return
  }

  const modal =
    Modal.getOrCreateInstance(elementoModal)

  modal.show()
}

const abrirModalNuevo = async () => {
  datosDuplicado.value = null
  await mostrarModalFormulario()
}

const abrirModalDuplicado = async (
  payload: DatosDuplicadoEntrada,
) => {
  datosDuplicado.value =
    normalizarDatosDuplicado(payload)

  await mostrarModalFormulario()
}

const manejarModalMostrado = async () => {
  claveFormularioNuevo.value++
  mostrarFormulario.value = true

  await nextTick()
}

const manejarModalOculto = () => {
  mostrarFormulario.value = false
  datosDuplicado.value = null
}

/* =========================================================
 * LISTADO Y CIERRE
 * =======================================================*/

const avisarALaLista = () => {
  componenteLista.value?.obtenerActualizaciones()
}

const cerrarModalBootstrap = () => {
  const elementoModal = modalNuevoRegistroRef.value

  if (!elementoModal) {
    return
  }

  Modal.getOrCreateInstance(elementoModal).hide()
}

/* =========================================================
 * EVENTOS BOOTSTRAP
 * =======================================================*/

onMounted(() => {
  const elementoModal = modalNuevoRegistroRef.value

  if (!elementoModal) {
    return
  }

  elementoModal.addEventListener(
    'shown.bs.modal',
    manejarModalMostrado,
  )

  elementoModal.addEventListener(
    'hidden.bs.modal',
    manejarModalOculto,
  )
})

onBeforeUnmount(() => {
  const elementoModal = modalNuevoRegistroRef.value

  if (!elementoModal) {
    return
  }

  elementoModal.removeEventListener(
    'shown.bs.modal',
    manejarModalMostrado,
  )

  elementoModal.removeEventListener(
    'hidden.bs.modal',
    manejarModalOculto,
  )

  Modal.getInstance(elementoModal)?.dispose()
})
</script>


<style scoped>
.modal-header {
  border-bottom: none;
  border-top: 3px solid var(--warning);
}

.contenedor-lista {
  max-width: 1400px;
  margin: 0 auto;
}
</style>