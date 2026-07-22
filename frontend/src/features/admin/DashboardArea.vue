<template>
  <section class="blog-dashboard-container container-fluid mt-4">
    <!-- Header -->
    <div class="dashboard-header">
      <PageHero eyebrow="Blog" titulo="Dashboard de actualizaciones" ancho-completo>
        Consulta el resumen de registros, estados, áreas, autores y lecturas disponibles.

        <template #acciones>
          <span v-if="dashboard?.alcance" class="scope-badge">
            <i class="bi bi-funnel-fill"></i>
            {{ dashboard.alcance.descripcion }}
          </span>

          <button type="button" class="btn-refresh" :disabled="cargando" title="Actualizar dashboard"
            @click="cargarDashboard(); cargarProgramados()">
            <span v-if="cargando" class="spinner-border spinner-border-sm" role="status"></span>

            <i v-else class="bi bi-arrow-clockwise"></i>
          </button>
        </template>
      </PageHero>
    </div>

    <!-- Loading -->
    <div v-if="cargando && !dashboard" class="dashboard-loading">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Cargando...</span>
      </div>

      <p>Cargando dashboard...</p>
    </div>

    <!-- Error -->
    <div v-else-if="error" class="dashboard-error">
      <i class="bi bi-exclamation-triangle-fill"></i>

      <div>
        <strong>No se pudo cargar el dashboard</strong>
        <p>{{ error }}</p>

        <button type="button" class="btn-retry" @click="cargarDashboard">
          Reintentar
        </button>
      </div>
    </div>

    <!-- Contenido -->
    <div v-else-if="dashboard" class="dashboard-content">
      <!-- Resumen -->
      <div class="summary-grid">
        <article class="summary-card total">
          <div class="summary-icon">
            <i class="bi bi-collection-fill"></i>
          </div>

          <div>
            <span class="summary-label">Total registros</span>
            <strong class="summary-value">
              {{ formatearNumero(dashboard.resumen.total_registros) }}
            </strong>
          </div>
        </article>

        <article class="summary-card published">
          <div class="summary-icon">
            <i class="bi bi-check-circle-fill"></i>
          </div>

          <div>
            <span class="summary-label">Publicados</span>
            <strong class="summary-value">
              {{ formatearNumero(dashboard.resumen.publicados) }}
            </strong>
          </div>
        </article>

        <article class="summary-card review">
          <div class="summary-icon">
            <i class="bi bi-hourglass-split"></i>
          </div>

          <div>
            <span class="summary-label">Por revisar</span>
            <strong class="summary-value">
              {{ formatearNumero(dashboard.resumen.revision) }}
            </strong>
          </div>
        </article>

        <article class="summary-card review">
          <div class="summary-icon">
            <i class="bi bi-hourglass-split"></i>
          </div>

          <div>
            <span class="summary-label">Programados</span>
            <strong class="summary-value">
              {{ formatearNumero(programados.length) }}
            </strong>
          </div>
        </article>

        <!-- <article class="summary-card review">
          <div class="summary-icon">
            <i class="bi "></i>
          </div>

          <div>
            <span class="summary-label">Por publicar</span>
            <strong class="summary-value">
              {{ formatearNumero(programados.length) }}
            </strong>
          </div>
        </article> -->

        <article class="summary-card draft">
          <div class="summary-icon">
            <i class="bi bi-pencil-square"></i>
          </div>

          <div>
            <span class="summary-label">Borradores</span>
            <strong class="summary-value">
              {{ formatearNumero(dashboard.resumen.borradores) }}
            </strong>
          </div>
        </article>
      </div>

      <!-- Grid principal -->
      <div class="dashboard-grid">
        <!-- Registros por estado -->
        <article class="dashboard-panel">
          <div class="panel-header">
            <div>
              <h2>
                <i class="bi bi-bar-chart-fill"></i>
                Registros por estado
              </h2>

              <p>Distribución general según estado de publicación.</p>
            </div>
          </div>

          <div v-if="dashboard.registros_por_estado.length" class="bar-list">
            <div v-for="estado in dashboard.registros_por_estado" :key="estado.estado" class="bar-row">
              <div class="bar-row-header">
                <span class="state-pill" :class="`state-${estado.estado}`">
                  {{ getEstadoLabel(estado.estado) }}
                </span>

                <strong>{{ formatearNumero(estado.total) }}</strong>
              </div>

              <div class="bar-track">
                <div class="bar-fill" :style="{ width: `${getPorcentaje(estado.total, maxEstado)}%` }"></div>
              </div>
            </div>
          </div>

          <div v-else class="empty-panel">
            <i class="bi bi-inboxes"></i>
            <p>No hay datos por estado.</p>
          </div>
        </article>

        <!-- Publicaciones programadas próximas -->
        <article class="dashboard-panel">
          <div class="panel-header">
            <div>
              <h2>
                <i class="bi bi-alarm-fill"></i>
                Próximas a publicarse
              </h2>

              <p>Registros programados que se publican en las próximas 24 horas.</p>
            </div>

            <strong class="summary-icon">
              {{ formatearNumero(programados.length) }}
            </strong>

          </div>


          <div v-if="programados.length" class="scheduled-list scroll-area">
            <div v-for="registro in programados" :key="registro.id" class="scheduled-item">
              <div class="scheduled-content">
                <strong class="scheduled-title" :title="registro.titulo">
                  {{ registro.titulo }}
                </strong>

                <!-- <small>{{ registro.area }}</small> -->
              </div>

              <span class="scheduled-countdown">
                <i class="bi bi-clock-fill"></i>
                {{ formatearTiempoRestante(registro.fecha_publicacion) }}
              </span>
            </div>
          </div>

          <div v-else class="empty-panel">
            <i class="bi bi-check2-circle"></i>
            <p>
              {{
                cargandoProgramados
                  ? 'Consultando publicaciones programadas...'
                  : 'No hay publicaciones programadas para las próximas 24 horas.'
              }}
            </p>
          </div>
        </article>

        <!-- Registros por área -->
        <!--  <article class="dashboard-panel">
          <div class="panel-header">
            <div>
              <h2>
                <i class="bi bi-diagram-3-fill"></i>
                Registros por área
              </h2>

              <p>Cantidad de registros escritos y pendientes de revisión por área.</p>
            </div>
          </div>

          <div v-if="dashboard.registros_por_area.length" class="area-list scroll-area">
            <div
              v-for="area in dashboard.registros_por_area"
              :key="area.area_servicio_id || area.area"
              class="area-item"
            >
              <div class="area-info">
                <span class="area-icon">
                  <i class="bi bi-building-fill"></i>
                </span>

                <div class="area-text">
                  <strong :title="area.area">
                    {{ area.area }}
                  </strong>

                  <small>
                    {{ formatearNumero(area.total) }} escritos ·
                    {{ formatearNumero(getPendientesArea(area)) }} por revisar
                  </small>
                </div>
              </div>

              <div class="area-stats">
                <span class="area-stat total" title="Registros escritos">
                  <i class="bi bi-file-earmark-text-fill"></i>
                  {{ formatearNumero(area.total) }}
                </span>

                <span class="area-stat revision" title="Pendientes por revisar">
                  <i class="bi bi-hourglass-split"></i>
                  {{ formatearNumero(getPendientesArea(area)) }}
                </span>
              </div>

              <div class="mini-bar">
                <span :style="{ width: `${getPorcentaje(area.total, maxArea)}%` }"></span>
              </div>
            </div>
          </div>

          <div v-else class="empty-panel">
            <i class="bi bi-building"></i>
            <p>No hay datos por área.</p>
          </div>
        </article> -->

        <!-- Usuarios con más registros -->
        <article class="dashboard-panel">
          <div class="panel-header">
            <div>
              <h2>
                <i class="bi bi-people-fill"></i>
                Usuarios más activos
              </h2>

              <p>Autores con mayor número de registros.</p>
            </div>
          </div>

          <div v-if="dashboard.usuarios_mas_registros.length" class="user-list scroll-area">
            <div v-for="(usuario, index) in dashboard.usuarios_mas_registros" :key="usuario.usuario_id"
              class="user-item">
              <span class="rank-number">
                {{ index + 1 }}
              </span>

              <div class="user-main">
                <strong :title="usuario.usuario">
                  {{ usuario.usuario }}
                </strong>

                <div class="user-progress">
                  <span :style="{ width: `${getPorcentaje(usuario.total, maxUsuarios)}%` }"></span>
                </div>
              </div>

              <strong class="user-total">
                {{ formatearNumero(usuario.total) }}
              </strong>
            </div>
          </div>

          <div v-else class="empty-panel">
            <i class="bi bi-person-x"></i>
            <p>No hay usuarios para mostrar.</p>
          </div>
        </article>

        <!-- Registros más leídos -->
        <article class="dashboard-panel ">
          <div class="panel-header">
            <div>
              <h2>
                <i class="bi bi-eye-fill"></i>
                Registros más leídos
              </h2>

              <p>Publicaciones con más lecturas registradas.</p>
            </div>

            <span class="availability-badge" :class="{ disabled: !dashboard.lecturas_disponibles }">
              <i class="bi"
                :class="dashboard.lecturas_disponibles ? 'bi-check-circle-fill' : 'bi-slash-circle-fill'"></i>

              {{ dashboard.lecturas_disponibles ? 'Lecturas activas' : 'Sin columna de lecturas' }}
            </span>
          </div>

          <div v-if="dashboard.lecturas_disponibles && dashboard.registros_mas_leidos_area.length"
            class="read-list scroll-area">
            <div v-for="(registro, index) in dashboard.registros_mas_leidos_area" :key="registro.id" class="read-item">
              <div class="read-rank">
                #{{ index + 1 }}
              </div>

              <div class="read-content">
                <strong class="read-title" :title="registro.titulo">
                  {{ registro.titulo }}
                </strong>

                <small>
                  Área ID:
                  {{ registro.area_servicio_id || 'Sin área' }}
                </small>
              </div>

              <div class="read-count">
                <i class="bi bi-eye-fill"></i>
                {{ formatearNumero(registro.lecturas) }}
              </div>
            </div>
          </div>

          <div v-else class="empty-panel">
            <i class="bi bi-eye-slash"></i>

            <p>
              {{
                dashboard.lecturas_disponibles
                  ? 'Todavía no hay registros con lecturas.'
                  : 'La tabla no tiene disponible la columna actualizacion_lecturas.'
              }}
            </p>
          </div>
        </article>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import api from '../../api/api'
import PageHero from '../../components/shared/PageHero.vue'

interface AlcanceDashboard {
  tipo: string
  areas: number[]
  descripcion: string
}

interface ResumenDashboard {
  total_registros: number
  publicados: number
  revision: number
  borradores: number
  programados: number
}

interface RegistroPorEstado {
  estado: string
  total: number
}

interface RegistroPorArea {
  area_servicio_id: number | null
  area: string
  total: number
  pendientes_revision?: number
}

interface UsuarioMasRegistros {
  usuario_id: number
  usuario: string
  total: number
}

interface RegistroMasLeido {
  id: number
  titulo: string
  lecturas: number
  area_servicio_id: number | null
}

interface BlogDashboardData {
  alcance: AlcanceDashboard
  resumen: ResumenDashboard
  registros_mas_leidos_area: RegistroMasLeido[]
  usuarios_mas_registros: UsuarioMasRegistros[]
  registros_por_estado: RegistroPorEstado[]
  registros_por_area: RegistroPorArea[]
  lecturas_disponibles: boolean
}

interface RegistroProgramado {
  id: number
  titulo: string
  fecha_publicacion: string
  area: string
}

const dashboard = ref<BlogDashboardData | null>(null)
const cargando = ref(false)
const error = ref('')

const programados = ref<RegistroProgramado[]>([])
const cargandoProgramados = ref(false)

/** Horas hacia adelante dentro de las cuales se considera "próxima a publicarse". */
const HORAS_PROXIMIDAD = 48

const cargarProgramados = async () => {
  cargandoProgramados.value = true

  try {
    const respuesta = await api.get('/actualizaciones?estado=programado&vista=supervision')
    const registros = respuesta.data?.data || []

    const ahora = Date.now()
    const limite = ahora + HORAS_PROXIMIDAD * 60 * 60 * 1000

    programados.value = registros
      .map((registro: any) => ({
        id: registro.id,
        titulo: registro.actualizacion_titulo,
        fecha_publicacion: registro.actualizacion_fecha_publicacion,
        area: registro.area_servicio?.area_servicio_nombre || 'Sin área',
      }))
      .filter((registro: RegistroProgramado) => {
        const fecha = new Date(registro.fecha_publicacion).getTime()
        return Number.isFinite(fecha) && fecha >= ahora && fecha <= limite
      })
      .sort(
        (a: RegistroProgramado, b: RegistroProgramado) =>
          new Date(a.fecha_publicacion).getTime() - new Date(b.fecha_publicacion).getTime()
      )
  } catch (err) {
    console.error('Error al cargar publicaciones programadas:', err)
  } finally {
    cargandoProgramados.value = false
  }
}

/** Texto tipo "en 3 h" / "en 45 min" a partir de una fecha futura cercana. */
const formatearTiempoRestante = (fechaIso: string) => {
  const diffMs = new Date(fechaIso).getTime() - Date.now()
  if (diffMs <= 0) return 'a punto de publicarse'

  const minutos = Math.round(diffMs / 60000)
  if (minutos < 60) return `en ${minutos} min`

  const horas = Math.round(minutos / 60)
  return `en ${horas} h`
}

const cargarDashboard = async () => {
  cargando.value = true
  error.value = ''

  try {
    const respuesta = await api.get('/blog-dashboard')
    dashboard.value = respuesta.data.data
  } catch (err: any) {
    if (err.response?.status === 403) {
      error.value = 'No tienes permiso para consultar este dashboard.'
    } else {
      error.value = 'Ocurrió un error al consultar la información del dashboard.'
    }
  } finally {
    cargando.value = false
  }
}

const maxEstado = computed(() => {
  return Math.max(
    ...(dashboard.value?.registros_por_estado.map((item) => item.total) || [0]),
    1
  )
})

// const maxArea = computed(() => {
//   return Math.max(
//     ...(dashboard.value?.registros_por_area.map((item) => item.total) || [0]),
//     1
//   )
// })

const maxUsuarios = computed(() => {
  return Math.max(
    ...(dashboard.value?.usuarios_mas_registros.map((item) => item.total) || [0]),
    1
  )
})

const getPorcentaje = (valor: number, maximo: number) => {
  if (!maximo) return 0
  return Math.max(6, Math.round((valor / maximo) * 100))
}

const formatearNumero = (valor: number) => {
  return new Intl.NumberFormat('es-CO').format(valor || 0)
}

const getEstadoLabel = (estado: string) => {
  const labels: Record<string, string> = {
    publicado: 'Publicado',
    revision: 'Revisión',
    borrador: 'Borrador',
    inactivo: 'Inactivo',
    programado: 'Programado',
    sin_estado: 'Sin estado',
  }

  return labels[estado] || estado
}

onMounted(() => {
  cargarDashboard()
  cargarProgramados()
})
</script>

<style scoped>
.text-primary {
  color: var(--primary) !important;
}

.blog-dashboard-container {
  max-width: 1500px;
  margin: 0 auto;
  padding: 0 16px 32px;
}

.dashboard-header {
  margin-bottom: 20px;
}

/* Hero: base y responsive viven en components/shared/PageHero.vue.
   Aquí solo lo propio de las acciones de esta página. */
.hero-actions {
  display: flex;
  align-items: center;
  gap: 12px;
  flex-shrink: 0;
}

.scope-badge {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  max-width: 240px;
  padding: 8px 14px;
  border-radius: 999px;
  color: #fff7d6;
  background: rgba(255, 255, 255, 0.14);
  border: 1px solid rgba(255, 255, 255, 0.22);
  font-size: 0.82rem;
  font-weight: 800;
  white-space: nowrap;
}

.btn-refresh {
  width: 42px;
  height: 42px;
  border: none;
  border-radius: 14px;
  background: #ffffff;
  color: var(--secondary);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: var(--transition);
  box-shadow: var(--shadow-sm);
}

.btn-refresh:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: var(--shadow-md);
}

.btn-refresh:disabled {
  opacity: 0.65;
  cursor: not-allowed;
}

/* Estados */
.dashboard-loading,
.dashboard-error {
  min-height: 360px;
  border: 1px solid #e2e8f0;
  border-radius: 18px;
  background: #ffffff;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 14px;
  color: #64748b;
  box-shadow: var(--shadow-sm);
}

.dashboard-loading {
  flex-direction: column;
}

.dashboard-loading p {
  margin: 0;
  font-weight: 600;
}

.dashboard-error {
  justify-content: flex-start;
  padding: 24px;
  color: #991b1b;
  background: #fff1f2;
  border-color: #fecdd3;
}

.dashboard-error i {
  font-size: 1.5rem;
}

.dashboard-error p {
  margin: 4px 0 12px;
}

.btn-retry {
  padding: 8px 16px;
  border: none;
  border-radius: 10px;
  color: white;
  background: #991b1b;
  font-weight: 700;
  cursor: pointer;
}

/* Resumen */
.summary-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 16px;
  margin-bottom: 16px;
}

.summary-card {
  position: relative;
  overflow: hidden;
  border: 1px solid #e2e8f0;
  border-radius: 18px;
  background: #ffffff;
  padding: 18px;
  display: flex;
  align-items: center;
  gap: 14px;
  box-shadow: var(--shadow-sm);
  transition: var(--transition);
}

.summary-card:hover {
  transform: translateY(-3px);
  box-shadow: var(--shadow-md);
}

.summary-card::after {
  content: '';
  position: absolute;
  inset: auto -30px -44px auto;
  width: 110px;
  height: 110px;
  border-radius: 50%;
  background: rgba(7, 126, 157, 0.08);
}

.summary-icon {
  width: 46px;
  height: 46px;
  border-radius: 14px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  color: #ffffff;
  background: linear-gradient(135deg, var(--primary), var(--secondary));
  flex-shrink: 0;
  box-shadow: 0 10px 20px rgba(7, 126, 157, 0.22);
}

.summary-card.published .summary-icon {
  background: linear-gradient(135deg, #16a34a, #15803d);
}

.summary-card.review .summary-icon {
  background: linear-gradient(135deg, #f9a825, #e4971b);
}

.summary-card.draft .summary-icon {
  background: linear-gradient(135deg, #64748b, #475569);
}

.summary-label {
  display: block;
  color: #64748b;
  font-size: 0.76rem;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.summary-value {
  display: block;
  margin-top: 2px;
  color: #0f172a;
  font-size: 1.75rem;
  line-height: 1;
}

/* Grid principal */
.dashboard-grid {
  display: grid;
  grid-template-columns: minmax(0, 1.05fr) minmax(360px, 0.95fr);
  gap: 16px;
}

.dashboard-panel {
  min-width: 0;
  border: 1px solid #e2e8f0;
  border-radius: 18px;
  background: #ffffff;
  overflow: hidden;
  box-shadow: var(--shadow-sm);
}

.featured-panel {
  grid-column: 1 / -1;
}

.panel-header {
  padding: 18px 20px;
  border-bottom: 1px solid #e2e8f0;
  background: #fdfeff;
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
}

.panel-header h2 {
  margin: 0;
  color: #1e293b;
  font-size: 1rem;
  font-weight: 800;
  display: flex;
  align-items: center;
  gap: 8px;
}

.panel-header h2 i {
  color: var(--primary);
}

.panel-header p {
  margin: 4px 0 0;
  color: #64748b;
  font-size: 0.82rem;
}

/* Barras por estado */
.bar-list {
  padding: 18px 20px;
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.bar-row-header {
  display: flex;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 8px;
}

.state-pill {
  display: inline-flex;
  align-items: center;
  border-radius: 999px;
  padding: 4px 10px;
  font-size: 0.75rem;
  font-weight: 700;
  background: #f1f5f9;
  color: #475569;
}


.state-publicado {
  background-color: #e6f7e9;
  color: #2e7d32;
}

.state-borrador {
  background-color: #fef8e3;
  color: #f9a825;
}

.state-revision {
  background-color: #eaf4fd;
  color: #2f84d3;
}

.state-inactivo {
  background-color: #e5e7eb;
  color: #374151;
}


.bar-track {
  height: 9px;
  overflow: hidden;
  border-radius: 999px;
  background: #f1f5f9;
}

.bar-fill {
  height: 100%;
  border-radius: inherit;
  background: linear-gradient(90deg, var(--primary), var(--secondary));
}

/* Área */
.area-list,
.user-list,
.read-list,
.scheduled-list {
  padding: 12px;
}

.scroll-area {
  max-height: 360px;
  overflow-y: auto;
  overflow-x: hidden;
}

.area-item,
.user-item,
.read-item,
.scheduled-item {
  border: 1px solid #e2e8f0;
  background: #ffffff;
  border-radius: 14px;
  padding: 12px;
}

.area-item+.area-item,
.user-item+.user-item,
.read-item+.read-item,
.scheduled-item+.scheduled-item {
  margin-top: 10px;
}

.area-info {
  display: flex;
  align-items: center;
  gap: 12px;
}

.area-icon {
  width: 36px;
  height: 36px;
  border-radius: 10px;
  color: var(--primary);
  background: rgba(7, 126, 157, 0.1);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.area-text {
  min-width: 0;
  flex: 1;
}

.area-text strong {
  display: block;
  max-width: 100%;
  color: #1e293b;
  font-size: 0.88rem;
  overflow: hidden;
  white-space: nowrap;
  text-overflow: ellipsis;
}

.area-text small {
  color: #64748b;
  font-size: 0.75rem;
}

.area-stats {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-top: 12px;
  flex-wrap: wrap;
}

.area-stat {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 5px 9px;
  border-radius: 999px;
  font-size: 0.75rem;
  font-weight: 800;
}

.area-stat.total {
  background: rgba(7, 126, 157, 0.1);
  color: var(--primary);
}

.area-stat.revision {
  background: rgba(252, 187, 28, 0.18);
  color: #b45309;
}

.area-stat i {
  font-size: 0.85rem;
}

.mini-bar {
  height: 6px;
  margin-top: 10px;
  border-radius: 999px;
  overflow: hidden;
  background: #f1f5f9;
}

.mini-bar span {
  display: block;
  height: 100%;
  border-radius: inherit;
  background: linear-gradient(90deg, var(--primary), var(--secondary));
}

/* Usuarios */
.user-item {
  display: flex;
  align-items: center;
  gap: 12px;
}

.rank-number {
  width: 28px;
  height: 28px;
  border-radius: 999px;
  background: #f1f5f9;
  color: var(--secondary);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 0.78rem;
  font-weight: 900;
  flex-shrink: 0;
}

.user-main {
  flex: 1;
  min-width: 0;
}

.user-main strong {
  display: block;
  color: #1e293b;
  font-size: 0.88rem;
  overflow: hidden;
  white-space: nowrap;
  text-overflow: ellipsis;
}

.user-progress {
  height: 6px;
  margin-top: 8px;
  border-radius: 999px;
  overflow: hidden;
  background: #f1f5f9;
}

.user-progress span {
  display: block;
  height: 100%;
  border-radius: inherit;
  background: linear-gradient(90deg, var(--primary), var(--secondary));
}

.user-total {
  color: var(--primary);
  font-size: 0.9rem;
}

/* Más leídos */
.availability-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  border-radius: 999px;
  padding: 6px 10px;
  background: #dcfce7;
  color: #166534;
  font-size: 0.72rem;
  font-weight: 800;
  white-space: nowrap;
}

.availability-badge.disabled {
  background: #f1f5f9;
  color: #64748b;
}

.read-item {
  display: grid;
  grid-template-columns: auto minmax(0, 1fr) auto;
  align-items: center;
  gap: 12px;
}

.read-rank {
  width: 42px;
  height: 42px;
  border-radius: 12px;
  color: #ffffff;
  background: linear-gradient(135deg, var(--primary), var(--secondary));
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-weight: 900;
  font-size: 0.8rem;
}

.read-content {
  min-width: 0;
}

.read-title {
  display: block;
  max-width: 100%;
  color: #1e293b;
  font-size: 0.9rem;
  overflow: hidden;
  white-space: nowrap;
  text-overflow: ellipsis;
}

.read-content small {
  color: #64748b;
  font-size: 0.75rem;
}

.read-count {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  color: var(--primary);
  background: rgba(7, 126, 157, 0.08);
  border: 1px solid rgba(7, 126, 157, 0.15);
  border-radius: 999px;
  padding: 6px 10px;
  font-weight: 900;
  white-space: nowrap;
}

.scheduled-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.scheduled-content {
  min-width: 0;
}

.scheduled-title {
  display: block;
  max-width: 100%;
  color: #1e293b;
  font-size: 0.9rem;
  overflow: hidden;
  white-space: nowrap;
  text-overflow: ellipsis;
}

.scheduled-content small {
  color: #64748b;
  font-size: 0.75rem;
}

.scheduled-countdown {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  color: var(--warning);
  background: rgba(252, 187, 28, 0.14);
  border: 1px solid rgba(252, 187, 28, 0.3);
  border-radius: 999px;
  padding: 6px 10px;
  font-weight: 800;
  font-size: 0.8rem;
  white-space: nowrap;
  flex-shrink: 0;
}

/* Empty */
.empty-panel {
  min-height: 180px;
  padding: 24px;
  color: #94a3b8;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 10px;
  text-align: center;
}

.empty-panel i {
  font-size: 2rem;
  opacity: 0.7;
}

.empty-panel p {
  margin: 0;
  font-size: 0.88rem;
}

/* Scrollbar */
.scroll-area::-webkit-scrollbar {
  width: 8px;
}

.scroll-area::-webkit-scrollbar-track {
  background: #f1f5f9;
  border-radius: 999px;
}

.scroll-area::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 999px;
}

.scroll-area::-webkit-scrollbar-thumb:hover {
  background: var(--primary);
}

/* Responsive */
@media (max-width: 1100px) {
  .summary-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .dashboard-grid {
    grid-template-columns: 1fr;
  }

  .featured-panel {
    grid-column: span 1;
  }
}

@media (max-width: 768px) {
  .blog-dashboard-container {
    padding: 0 12px 24px;
  }

  .hero-actions {
    width: 100%;
    justify-content: space-between;
  }

  .scope-badge {
    max-width: none;
    flex: 1;
  }

  .summary-grid {
    grid-template-columns: 1fr;
  }

  .panel-header {
    flex-direction: column;
  }

  .read-item {
    grid-template-columns: auto minmax(0, 1fr);
  }

  .read-count {
    grid-column: 2;
    justify-self: flex-start;
  }
}

@media (max-width: 480px) {
  .summary-card {
    padding: 16px;
  }

  .summary-value {
    font-size: 1.45rem;
  }

  .area-stats {
    flex-direction: column;
    align-items: flex-start;
  }
}
</style>
