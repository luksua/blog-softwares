<template>
  <section class="supervision-page container-fluid mt-4">
    <div class="supervision-hero">
      <div>
        <span class="eyebrow">Panel de supervisión</span>
        <h2>Registros del área</h2>
        <p>
          Revisa las actualizaciones creadas por los empleados de tu área y marca como
          <strong>revisión</strong> los registros que necesiten seguimiento.
        </p>
      </div>

      <div class="hero-badge">
        <i class="bi bi-shield-check"></i>
        <span>Supervisor</span>
      </div>
    </div>

    <!-- <div class="supervision-resumen">
      <article class="resumen-card">
        <span class="resumen-label">Acceso</span>
        <strong>{{ puedeSupervisar ? 'Habilitado' : 'Validando sesión' }}</strong>
        <small>Según tu sesión SSO actual.</small>
      </article>

      <article class="resumen-card">
        <span class="resumen-label">Áreas supervisadas</span>
        <strong>{{ areasTexto }}</strong>
        <small>La API limita la consulta a estas áreas.</small>
      </article>

      <article class="resumen-card">
        <span class="resumen-label">Acción disponible</span>
        <strong>Marcar revisión</strong>
        <small>No se habilita edición sobre registros ajenos.</small>
      </article>
    </div> -->

    <div class="supervision-content">
      <List ref="componenteLista" vista="supervision" />
    </div>
  </section>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import api from '../../api/api'
import List from '../../components/employees/register/List.vue'

const componenteLista = ref<InstanceType<typeof List> | null>(null)
 const usuario = ref<any>(null)
 const puedeSupervisar = ref(false)
 const areasSupervisadas = ref<number[]>([])

 const areasTexto = computed(() => {
   if (!areasSupervisadas.value.length) {
   return 'Área asignada'
  }

   return areasSupervisadas.value.join(', ')
 })

onMounted(async () => {
   try {
   const response = await api.get('/me')
   const data = response.data || {}

  usuario.value = data.usuario || data.user || data
  puedeSupervisar.value = data.puede_supervisar_area === true || data.es_admin_area === true
  areasSupervisadas.value = Array.isArray(data.areas_supervisadas)
      ? data.areas_supervisadas.map((area: any) => Number(area)).filter(Boolean)
       : []
   } catch (error) {
     console.error('Error al cargar datos de supervisión:', error)
}
})
</script>

<style scoped>
.supervision-page {
  --primary: #077e9d;
  --secondary: #025b7d;
  --warning: #fcbb1c;
  --text: #1f2937;
  --muted: #6b7280;
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
  margin: 6px 0 10px;
  font-size: clamp(1.7rem, 3vw, 2.6rem);
  font-weight: 800;
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

.supervision-resumen {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 16px;
  max-width: 1500px;
  margin: 0 auto 22px;
}

.resumen-card {
  padding: 18px 20px;
  border-radius: 18px;
  background: white;
  border: 1px solid #e1e7f0;
  box-shadow: 0 4px 14px rgba(15, 23, 42, 0.06);
}

.resumen-label {
  display: block;
  margin-bottom: 6px;
  color: var(--muted);
  font-size: 0.78rem;
  font-weight: 800;
  letter-spacing: 0.05em;
  text-transform: uppercase;
}

.resumen-card strong {
  display: block;
  color: var(--text);
  font-size: 1.1rem;
  margin-bottom: 4px;
}

.resumen-card small {
  color: var(--muted);
}

.supervision-content {
  max-width: 1500px;
  margin: 0 auto;
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
