<template>
  <section class="historial-page container py-4">
    <PageHero
      eyebrow="Blog"
      titulo="Mi historial de lecturas"
    >
      Publicaciones consultadas recientemente.
    </PageHero>

    <div v-if="cargando" class="estado-mensaje">
      <div class="spinner-border text-primary"></div>
      <p>Cargando historial...</p>
    </div>

    <div v-else-if="error" class="estado-mensaje error">
      <p>{{ error }}</p>
    </div>

    <div v-else-if="!historial.length" class="estado-mensaje">
      <i class="bi bi-clock-history"></i>
      <p>Todavía no has consultado publicaciones.</p>
    </div>

    <div v-else class="historial-lista">
      <article
        v-for="lectura in historial"
        :key="lectura.id"
        class="historial-item"
        @click="abrirPublicacion(lectura.id)"
      >
        <div>
          <strong>{{ lectura.actualizacion_titulo }}</strong>

          <p>
            {{ lectura.actualizacion_resumen }}
          </p>

          <small>
            Última lectura:
            {{ formatearFecha(lectura.ultima_visualizacion) }}
          </small>
        </div>

        <span class="contador">
          <i class="bi bi-eye-fill"></i>
          {{ lectura.veces_visualizado }}
        </span>
      </article>
    </div>
  </section>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import api from '../../api/api'
import PageHero from '../../components/shared/PageHero.vue'

interface LecturaReciente {
  id: number
  actualizacion_titulo: string
  actualizacion_resumen: string | null
  actualizacion_imagen_destacada: string | null
  actualizacion_version: string | null
  actualizacion_fecha_publicacion: string | null
  ultima_visualizacion: string
  veces_visualizado: number
}

const router = useRouter()

const historial = ref<LecturaReciente[]>([])
const cargando = ref(false)
const error = ref('')

const cargarHistorial = async () => {
  cargando.value = true
  error.value = ''

  try {
    const respuesta = await api.get('/mi-historial-lecturas')
    historial.value = respuesta.data?.data ?? []
  } catch (err) {
    console.error('Error cargando historial:', err)
    error.value = 'No se pudo consultar tu historial de lecturas.'
  } finally {
    cargando.value = false
  }
}

const abrirPublicacion = (id: number) => {
  router.push({
    name: 'actualizaciones-show',
    params: { id },
  })
}

const formatearFecha = (fecha: string) => {
  return new Intl.DateTimeFormat('es-CO', {
    dateStyle: 'medium',
    timeStyle: 'short',
    timeZone: 'America/Bogota',
  }).format(new Date(fecha))
}

onMounted(cargarHistorial)
</script>

<style scoped>
.historial-page {
  --historial-primary: var(--primary, #077e9d);
  --historial-secondary: var(--secondary, #025b7d);
  --historial-warning: var(--warning, #fcbb1c);
  --historial-text: #1e293b;
  --historial-muted: #64748b;
  --historial-border: #e2e8f0;
  --historial-surface: #ffffff;
  --historial-background: #f8fafc;

  width: 100%;
  max-width: 1200px;
  min-height: calc(100vh - 100px);
  margin: 0 auto;
}

/* =========================================================
   ESTADOS: CARGANDO, ERROR Y VACÍO
   ========================================================= */

.estado-mensaje {
  min-height: 320px;
  margin-top: 24px;
  padding: 48px 24px;
  color: var(--historial-muted);
  text-align: center;
  background:
    radial-gradient(
      circle at top,
      rgba(7, 126, 157, 0.08),
      transparent 45%
    ),
    var(--historial-surface);
  border: 1px solid var(--historial-border);
  border-radius: 24px;
  box-shadow: 0 12px 34px rgba(15, 23, 42, 0.07);

  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 14px;
}

.estado-mensaje p {
  max-width: 480px;
  margin: 0;
  font-size: 0.95rem;
  line-height: 1.6;
}

.estado-mensaje > i {
  display: inline-flex;
  width: 64px;
  height: 64px;
  align-items: center;
  justify-content: center;

  color: var(--historial-primary);
  font-size: 1.8rem;
  background: rgba(7, 126, 157, 0.1);
  border: 1px solid rgba(7, 126, 157, 0.16);
  border-radius: 20px;
}

.estado-mensaje.error {
  color: #991b1b;
  background:
    radial-gradient(
      circle at top,
      rgba(225, 29, 72, 0.08),
      transparent 45%
    ),
    #fffafb;
  border-color: #fecdd3;
}

.estado-mensaje.error::before {
  content: '\F33A';
  display: inline-flex;
  width: 64px;
  height: 64px;
  align-items: center;
  justify-content: center;

  color: #be123c;
  font-family: 'bootstrap-icons';
  font-size: 1.8rem;
  background: #ffe4e6;
  border-radius: 20px;
}

.spinner-border {
  width: 2.25rem;
  height: 2.25rem;
  color: var(--historial-primary) !important;
  border-width: 0.22em;
}

/* =========================================================
   LISTADO
   ========================================================= */

.historial-lista {
  position: relative;
  margin-top: 24px;
  padding-left: 34px;

  display: flex;
  flex-direction: column;
  gap: 14px;
}

/* Línea vertical de tiempo */
.historial-lista::before {
  content: '';
  position: absolute;
  top: 24px;
  bottom: 24px;
  left: 11px;
  width: 2px;

  background: linear-gradient(
    to bottom,
    var(--historial-primary),
    rgba(7, 126, 157, 0.08)
  );
  border-radius: 999px;
}

/* =========================================================
   REGISTRO INDIVIDUAL
   ========================================================= */

.historial-item {
  position: relative;
  min-width: 0;
  padding: 18px 20px;

  background: var(--historial-surface);
  border: 1px solid var(--historial-border);
  border-radius: 18px;
  box-shadow: 0 4px 14px rgba(15, 23, 42, 0.05);
  cursor: pointer;

  display: grid;
  grid-template-columns: minmax(0, 1fr) auto;
  align-items: center;
  gap: 20px;

  transition:
    transform 0.22s ease,
    box-shadow 0.22s ease,
    border-color 0.22s ease;
}

/* Punto sobre la línea de tiempo */
.historial-item::before {
  content: '';
  position: absolute;
  top: 27px;
  left: -31px;

  width: 18px;
  height: 18px;

  background: var(--historial-surface);
  border: 5px solid var(--historial-primary);
  border-radius: 50%;
  box-shadow: 0 0 0 5px rgba(7, 126, 157, 0.1);
}

.historial-item:hover {
  border-color: rgba(7, 126, 157, 0.35);
  box-shadow: 0 14px 30px rgba(15, 23, 42, 0.1);
  transform: translateY(-3px);
}

.historial-item:active {
  transform: translateY(-1px) scale(0.995);
}

.historial-item > div {
  min-width: 0;
}

.historial-item strong {
  display: block;
  overflow: hidden;

  color: var(--historial-text);
  font-size: 1rem;
  font-weight: 800;
  line-height: 1.35;
  text-overflow: ellipsis;
  white-space: nowrap;

  transition: color 0.2s ease;
}

.historial-item:hover strong {
  color: var(--historial-primary);
}

.historial-item p {
  display: -webkit-box;
  margin: 7px 0 10px;
  overflow: hidden;

  color: var(--historial-muted);
  font-size: 0.87rem;
  line-height: 1.55;

  -webkit-box-orient: vertical;
  -webkit-line-clamp: 2;
}

.historial-item small {
  display: inline-flex;
  align-items: center;
  gap: 6px;

  color: #64748b;
  font-size: 0.75rem;
  font-weight: 700;
}

.historial-item small::before {
  content: '\F293';
  color: var(--historial-primary);
  font-family: 'bootstrap-icons';
  font-size: 0.78rem;
}

/* =========================================================
   CONTADOR DE VISUALIZACIONES
   ========================================================= */

.contador {
  display: inline-flex;
  min-width: 62px;
  padding: 9px 12px;
  align-items: center;
  justify-content: center;
  gap: 7px;

  color: var(--historial-primary);
  font-size: 0.82rem;
  font-weight: 900;
  white-space: nowrap;

  background: rgba(7, 126, 157, 0.08);
  border: 1px solid rgba(7, 126, 157, 0.15);
  border-radius: 999px;

  transition:
    color 0.2s ease,
    background 0.2s ease,
    transform 0.2s ease;
}

.contador i {
  font-size: 0.9rem;
}

.historial-item:hover .contador {
  color: #ffffff;
  background: linear-gradient(
    135deg,
    var(--historial-primary),
    var(--historial-secondary)
  );
  border-color: transparent;
  transform: scale(1.04);
}

/* =========================================================
   ANIMACIÓN DE ENTRADA
   ========================================================= */

.historial-item {
  animation: historialEntrada 0.35s ease both;
}

.historial-item:nth-child(2) {
  animation-delay: 0.04s;
}

.historial-item:nth-child(3) {
  animation-delay: 0.08s;
}

.historial-item:nth-child(4) {
  animation-delay: 0.12s;
}

.historial-item:nth-child(5) {
  animation-delay: 0.16s;
}

@keyframes historialEntrada {
  from {
    opacity: 0;
    transform: translateY(12px);
  }

  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* =========================================================
   RESPONSIVE
   ========================================================= */

@media (max-width: 767px) {
  .historial-page {
    padding-inline: 12px !important;
  }

  .estado-mensaje {
    min-height: 260px;
    padding: 36px 20px;
    border-radius: 18px;
  }

  .historial-lista {
    padding-left: 26px;
    gap: 12px;
  }

  .historial-lista::before {
    left: 8px;
  }

  .historial-item {
    padding: 16px;
    grid-template-columns: 1fr;
    gap: 13px;
    border-radius: 16px;
  }

  .historial-item::before {
    top: 24px;
    left: -25px;
    width: 16px;
    height: 16px;
    border-width: 4px;
  }

  .historial-item strong {
    white-space: normal;
  }

  .contador {
    justify-self: flex-start;
  }
}

@media (max-width: 420px) {
  .historial-lista {
    padding-left: 0;
  }

  .historial-lista::before,
  .historial-item::before {
    display: none;
  }

  .historial-item p {
    -webkit-line-clamp: 3;
  }
}

@media (prefers-reduced-motion: reduce) {
  .historial-item,
  .contador {
    animation: none;
    transition: none;
  }
}
</style>