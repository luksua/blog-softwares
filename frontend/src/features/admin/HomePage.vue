<template>
    <div class="pagina-principal container mt-4">

        <div class="row align-items-center mb-4">
            <div class="col-lg-10">
                <h2>Actualizaciones</h2>
            </div>
            <div class="col-lg-2 text-end">
                <button type="button" class="btn btn-primary w-100" data-bs-toggle="modal"
                    data-bs-target="#modalNuevoRegistro">
                    + Nuevo Registro
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

        <div class="modal fade" id="modalNuevoRegistro" tabindex="-1" aria-labelledby="modalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">

                    <div class="modal-header bg-light">
                        <h5 class="modal-title fw-bold" id="modalLabel">Crear Nueva Actualización</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"
                            id="btnCerrarModal"></button>
                    </div>

                    <div class="modal-body">
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

// referencia conectada al componente <List />
const componenteLista = ref<InstanceType<typeof List> | null>(null);

// esta función atrapa el evento del formulario y manda a llamar a la función de la lista
const avisarALaLista = () => {
  if (componenteLista.value) {
    // Envolvemos la variable en ( ... as any) para calmar a TypeScript
    (componenteLista.value as any).obtenerActualizaciones();
  }
};

const cerrarModalBootstrap = () => {
    const botonCerrar = document.getElementById('btnCerrarModal');
    if (botonCerrar) {
        botonCerrar.click();
    }
};
</script>