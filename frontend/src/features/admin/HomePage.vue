<template>
  <div class="pagina-principal container-fluid mt-4">
    <div class="row justify-content-center align-items-center mb-4 titulo">
      <div class="supervision-hero">
      <div>
        <span class="eyebrow">Panel de registros</span>
        <h2>Registros del área</h2>
        <p>
          
        </p>
      </div>

      <div v-if="!esVistaSupervision" class="col-lg-2 text-end">
        <button type="button" class="btn-primary" data-bs-toggle="modal" data-bs-target="#modalNuevoRegistro">
          +<i class="bi bi-pencil-fill"></i> Nuevo Registro
        </button>
      </div>
    </div>
      <!-- <div class="col-lg-10">
        <h2>{{ tituloPagina }}</h2>
      </div> -->

      
    </div>

    <div class="row">
      <div class="col-lg-12">
        <List ref="componenteLista" :vista="props.vista" />
      </div>
    </div>

    <div
      v-if="!esVistaSupervision"
      class="modal fade"
      id="modalNuevoRegistro"
      ref="modalNuevoRegistroRef"
      tabindex="-1"
      aria-labelledby="modalLabel"
      aria-hidden="true"
    >
      <div class="modal-dialog modal-lg">
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
import { computed, nextTick, onMounted, ref } from 'vue'
import api from '../../api/api'
import List from '../../components/register/List.vue'
import Store from '../../components/register/NewVersion.vue'

const props = withDefaults(defineProps<{
  vista?: 'mis-registros' | 'supervision'
}>(), {
  vista: 'mis-registros',
})

const usuario = ref<any>(null)
const componenteLista = ref<InstanceType<typeof List> | null>(null)
const modalNuevoRegistroRef = ref<HTMLElement | null>(null)

const esVistaSupervision = computed(() => props.vista === 'supervision')
const tituloPagina = computed(() => esVistaSupervision.value ? 'Supervisión de registros' : 'Mis registros')

const avisarALaLista = () => {
  if (componenteLista.value) {
    ;(componenteLista.value as any).obtenerActualizaciones()
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

onMounted(async () => {
  try {
    const response = await api.get('/me')
    usuario.value = response.data.usuario
  } catch (error) {
    console.error(error)
  }
})
</script>

<style scoped>
.modal-header {
  border-bottom: none;
  border-top: 3px solid var(--warning);
}

.supervision-hero {
  display: flex;
  justify-content: space-between;
  gap: 24px;
  align-items: center;
  max-width: 1500px;
  margin: 0 auto 20px;
  padding: 28px;
  border-radius: 24px;
  background:
    radial-gradient(circle at top right, rgba(252, 187, 28, 0.24), transparent 32%),
    linear-gradient(135deg, #073b4c 0%, var(--secondary) 100%);
  color: white;
  box-shadow: 0 14px 32px rgba(2, 91, 125, 0.22);
}

.supervision-hero h2 {
  font-weight: 700;
}

.supervision-hero p {
  max-width: 760px;
  margin: 0;
  color: rgba(255, 255, 255, 0.86);
  font-size: 1rem;
}

.eyebrow {
  display: inline-flex;
  padding: 6px 12px;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.14);
  color: #fff7d6;
  font-size: 0.8rem;
  font-weight: 800;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

.hero-badge {
  display: inline-flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-width: 130px;
  height: 120px;
  border-radius: 24px;
  background: rgba(255, 255, 255, 0.14);
  border: 1px solid rgba(255, 255, 255, 0.18);
  backdrop-filter: blur(8px);
  font-weight: 800;
}

.hero-badge i {
  font-size: 2rem;
  color: var(--warning);
  margin-bottom: 8px;
}

@media (max-width: 900px) {
  .supervision-hero {
    align-items: flex-start;
    flex-direction: column;
  }

  .hero-badge {
    width: 100%;
    height: auto;
    flex-direction: row;
    gap: 10px;
    padding: 14px;
  }

  .hero-badge i {
    margin-bottom: 0;
  }

  .supervision-resumen {
    grid-template-columns: 1fr;
  }
}
</style>
