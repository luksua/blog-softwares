<template>
    <div class="pagina-principal container-fluid mt-4">

        <div class="row justify-content-center align-items-center mb-4">
            <div class="col-lg-10">
                <h2>Actualizaciones</h2>
            </div>
            <div class="col-lg-2 text-end">
                <button type="button" class="btn-primary" data-bs-toggle="modal" data-bs-target="#modalNuevoRegistro">
                    +<i class="bi bi-pencil-fill"></i> Nuevo Registro
                </button>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-12">
                <List ref="componenteLista" />
            </div>
            <!-- <div class="col-lg-2">
        <div class="card p-3 shadow-sm bg-light">
          Resumen aquí...
        </div>
      </div> -->
        </div>

        <div class="modal fade" id="modalNuevoRegistro" ref="modalNuevoRegistroRef" tabindex="-1"
            aria-labelledby="modalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl">
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

import List from '../../components/admin/List.vue';
import Store from '../../components/admin/NewVersion.vue';
// Borramos la importación de MainLayout de aquí
import { ref } from 'vue';
import { Modal } from 'bootstrap';

// referencia conectada al componente <List />
const componenteLista = ref<InstanceType<typeof List> | null>(null);
const modalNuevoRegistroRef = ref<HTMLElement | null>(null);


const avisarALaLista = () => {
    if (componenteLista.value) {
        // Envolvemos la variable en ( ... as any) para calmar a TypeScript
        (componenteLista.value as any).obtenerActualizaciones();
    }
};

const cerrarModalBootstrap = () => {
    const el = modalNuevoRegistroRef.value;
    if (!el) return;

    const modal = Modal.getInstance(el) || new Modal(el);
    modal.hide();
};


// const limpiarBackdropResidual = () => {
//   document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
//   document.body.classList.remove('modal-open');
//   document.body.style.overflow = '';
//   document.body.style.paddingRight = '';
// };

</script>

<style scoped>
.modal-header {
    border-bottom: none;
    border-top: 3px solid var(--warning);
}
</style>