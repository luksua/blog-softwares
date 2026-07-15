<template>
  <section class="supervision-page container-fluid">
    <PageHero eyebrow="Panel de supervisión" titulo="Registros del área">
      Revisa las actualizaciones creadas por los empleados de tu área y marca como
      <strong>revisión</strong> los registros que necesiten seguimiento.
    </PageHero>

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
      <List vista="supervision" />
    </div>
  </section>
</template>

<script setup lang="ts">
import List from '../../components/register/List.vue'
import PageHero from '../../components/shared/PageHero.vue'
</script>

<style scoped>
.supervision-content {
  max-width: 1400px;
  margin: 0 auto;
}
</style>