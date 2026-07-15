<template>
  <div class="pagina-principal contenedor-lista container-fluid">
    <PageHero eyebrow="Panel de registros" titulo="Registros del área">
      <template v-if="!esVistaSupervision" #acciones>
        <button type="button" class="btn-primary" data-bs-toggle="modal" data-bs-target="#modalNuevoRegistro">
          +<i class="bi bi-pencil-fill"></i> Nuevo Registro
        </button>
      </template>
    </PageHero>

    <div class="row">
      <div class="col-lg-12">
        <List ref="componenteLista" :vista="props.vista" />
      </div>
    </div>

    <div v-if="!esVistaSupervision" class="modal fade" id="modalNuevoRegistro" ref="modalNuevoRegistroRef" tabindex="-1"
      aria-labelledby="modalLabel" aria-hidden="true">
      <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable modal-registro-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title fw-bold" id="modalLabel">Registrar Nueva Actualización</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>

          <div class="modal-body p-4">
            <Store @recargar-lista="avisarALaLista" @cerrar="cerrarModalBootstrap" />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Modal } from 'bootstrap'
import { computed, nextTick, ref } from 'vue'
import List from '../../components/register/List.vue'
import Store from '../../components/register/NewVersion.vue'
import PageHero from '../../components/shared/PageHero.vue'

const props = withDefaults(defineProps<{
  vista?: 'mis-registros' | 'supervision'
}>(), {
  vista: 'mis-registros',
})

const componenteLista = ref<InstanceType<typeof List> | null>(null)
const modalNuevoRegistroRef = ref<HTMLElement | null>(null)

const esVistaSupervision = computed(() => props.vista === 'supervision')

const avisarALaLista = () => {
  if (componenteLista.value) {
    ; (componenteLista.value as any).obtenerActualizaciones()
  }
}

const cerrarModalBootstrap = async () => {
  await nextTick()
  const el = modalNuevoRegistroRef.value
  if (!el) return

  let modal = Modal.getInstance(el)
  if (!modal) modal = new Modal(el)

  modal.hide()

  setTimeout(() => {
    document.querySelectorAll('.modal-backdrop').forEach(el => el.remove())
    document.body.classList.remove('modal-open')
    document.body.style.overflow = ''
    document.body.style.paddingRight = ''
  }, 300)
}
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