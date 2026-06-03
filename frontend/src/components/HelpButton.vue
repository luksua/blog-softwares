<template>
  <!-- Botón de ayuda flotante -->
  <div class="help-wrapper">
    <button
      class="help-btn"
      type="button"
      title="Ayuda"
      aria-label="Abrir ayuda"
      @click.stop="togglePanel"
    >
      <span class="help-icon">?</span>
    </button>

    <!-- Panel de ayuda -->
    <Transition name="help-panel">
      <div v-if="mostrarPanel" class="help-panel" @click.stop>
        <!-- Header -->
        <div class="help-panel-header">
          <div class="help-panel-title">
            <span class="help-panel-emoji">💡</span>
            <strong>Centro de Ayuda</strong>
          </div>
          <button class="help-close-btn" type="button" aria-label="Cerrar ayuda" @click="cerrarPanel">
            ✕
          </button>
        </div>

        <!-- Búsqueda -->
        <div class="help-search-wrapper">
          <span class="help-search-icon">🔍</span>
          <input
            v-model="busqueda"
            class="help-search"
            type="text"
            placeholder="Buscar en la ayuda..."
            @input="filtrarAyuda"
          />
        </div>

        <!-- Secciones de ayuda -->
        <div class="help-content">
          <div v-if="seccionesFiltradas.length === 0" class="help-empty">
            <span>😕</span>
            <p>No se encontraron resultados para "<strong>{{ busqueda }}</strong>"</p>
          </div>

          <div
            v-for="seccion in seccionesFiltradas"
            :key="seccion.id"
            class="help-section"
          >
            <button
              class="help-section-toggle"
              type="button"
              @click="toggleSeccion(seccion.id)"
            >
              <span class="help-section-icon">{{ seccion.icono }}</span>
              <span class="help-section-title">{{ seccion.titulo }}</span>
              <span class="help-chevron" :class="{ rotated: seccionesAbiertas.has(seccion.id) }">
                ›
              </span>
            </button>

            <Transition name="help-collapse">
              <div v-if="seccionesAbiertas.has(seccion.id)" class="help-section-body">
                <p class="help-section-desc">{{ seccion.descripcion }}</p>
                <ul v-if="seccion.pasos" class="help-steps">
                  <li v-for="(paso, idx) in seccion.pasos" :key="idx" class="help-step">
                    <span class="help-step-num">{{ idx + 1 }}</span>
                    <span>{{ paso }}</span>
                  </li>
                </ul>
                <div v-if="seccion.consejo" class="help-tip">
                  <span>💡</span>
                  <span>{{ seccion.consejo }}</span>
                </div>
              </div>
            </Transition>
          </div>
        </div>

        <!-- Footer -->
        <div class="help-panel-footer">
          <span>¿Necesitas más ayuda?</span>
          <a href="mailto:soporte@asotrauma.com" class="help-contact-link">Contáctanos</a>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'

const mostrarPanel = ref(false)
const busqueda = ref('')
const seccionesAbiertas = ref<Set<string>>(new Set(['blog']))

interface SeccionAyuda {
  id: string
  icono: string
  titulo: string
  descripcion: string
  pasos?: string[]
  consejo?: string
  palabrasClave: string[]
}

const secciones: SeccionAyuda[] = [
  {
    id: 'blog',
    icono: '📰',
    titulo: 'Cómo leer el Blog',
    descripcion: 'El Blog muestra las actualizaciones y novedades de la organización.',
    pasos: [
      'Haz clic en "Blog" en el menú superior.',
      'Desplázate para ver las publicaciones más recientes.',
      'Haz clic en una publicación para ver el detalle completo.',
      'Usa el botón de marcador (🔖) para guardar publicaciones de interés.',
    ],
    consejo: 'Las publicaciones más recientes aparecen primero. Usa los filtros de categoría para encontrar temas específicos.',
    palabrasClave: ['blog', 'publicacion', 'noticia', 'leer', 'articulo'],
  },
  {
    id: 'registros',
    icono: '📋',
    titulo: 'Mis Registros',
    descripcion: 'En esta sección puedes crear y gestionar tus propias actualizaciones o reportes.',
    pasos: [
      'Ve a "Mis registros" desde el menú superior.',
      'Haz clic en "+ Nuevo registro" para crear uno nuevo.',
      'Completa el formulario con título, contenido y categoría.',
      'Guarda el borrador o envíalo directamente.',
    ],
    consejo: 'Puedes guardar como borrador para continuar editando después sin perder el trabajo.',
    palabrasClave: ['registro', 'crear', 'nuevo', 'formulario', 'borrador', 'actualización'],
  },
  {
    id: 'guardados',
    icono: '🔖',
    titulo: 'Publicaciones Guardadas',
    descripcion: 'Aquí encuentras las publicaciones que marcaste como favoritas.',
    pasos: [
      'Desde cualquier publicación, haz clic en el ícono de marcador (🔖).',
      'Ve a "Guardados" (el ícono de marcador en el menú) para verlas todas.',
      'Haz clic en cualquier publicación guardada para abrirla.',
      'Para eliminar una de guardados, vuelve a hacer clic en el marcador.',
    ],
    palabrasClave: ['guardado', 'favorito', 'marcador', 'bookmark'],
  },
  {
    id: 'notificaciones',
    icono: '🔔',
    titulo: 'Notificaciones',
    descripcion: 'Las notificaciones te informan de nuevas publicaciones, comentarios o actualizaciones relevantes.',
    pasos: [
      'Haz clic en el ícono de campana 🔔 en la parte superior derecha.',
      'Las notificaciones no leídas aparecen resaltadas en azul.',
      'Haz clic en una notificación para ir directamente al contenido.',
      'Usa "Marcar leídas" para limpiar todas las notificaciones de una vez.',
    ],
    consejo: 'Las notificaciones se actualizan automáticamente cada minuto.',
    palabrasClave: ['notificacion', 'alerta', 'campana', 'aviso'],
  },
  {
    id: 'supervision',
    icono: '👥',
    titulo: 'Supervisión',
    descripcion: 'El módulo de supervisión permite a los administradores de área revisar los registros del equipo.',
    pasos: [
      'Ve a "Supervisión" en el menú (solo visible si tienes permisos).',
      'Verás los registros de los miembros de tu área.',
      'Puedes filtrar por estado, fecha o empleado.',
      'Haz clic en un registro para revisarlo y agregar observaciones.',
    ],
    consejo: 'Solo los jefes de área y administradores tienen acceso a esta sección.',
    palabrasClave: ['supervision', 'admin', 'area', 'equipo', 'revisar'],
  },
  {
    id: 'sesion',
    icono: '🔐',
    titulo: 'Cerrar Sesión',
    descripcion: 'Para salir del sistema de forma segura.',
    pasos: [
      'Haz clic en el botón rojo de salida (→) en la parte superior derecha.',
      'Serás redirigido al portal de intranet.',
    ],
    consejo: 'Siempre cierra sesión al terminar, especialmente en equipos compartidos.',
    palabrasClave: ['sesion', 'cerrar', 'salir', 'logout'],
  },
]

const seccionesFiltradas = computed(() => {
  if (!busqueda.value.trim()) return secciones

  const q = busqueda.value.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '')

  return secciones.filter((s) => {
    const campos = [s.titulo, s.descripcion, ...(s.pasos || []), ...(s.palabrasClave)]
    return campos.some((campo) =>
      campo.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').includes(q)
    )
  })
})

const togglePanel = () => {
  mostrarPanel.value = !mostrarPanel.value
}

const cerrarPanel = () => {
  mostrarPanel.value = false
}

const toggleSeccion = (id: string) => {
  if (seccionesAbiertas.value.has(id)) {
    seccionesAbiertas.value.delete(id)
  } else {
    seccionesAbiertas.value.add(id)
  }
}

const filtrarAyuda = () => {
  // Si hay búsqueda, abrir todas las secciones que coincidan
  if (busqueda.value.trim()) {
    seccionesFiltradas.value.forEach((s) => seccionesAbiertas.value.add(s.id))
  }
}

const cerrarAlClickAfuera = () => {
  mostrarPanel.value = false
}

onMounted(() => {
  window.addEventListener('click', cerrarAlClickAfuera)
})

onUnmounted(() => {
  window.removeEventListener('click', cerrarAlClickAfuera)
})
</script>

<style scoped>
/* ── Wrapper ─────────────────────────────────────── */
.help-wrapper {
  position: relative;
  display: inline-flex;
  align-items: center;
}

/* ── Botón circular ──────────────────────────────── */
.help-btn {
  width: 40px;
  height: 40px;
  border-radius: 999px;
  border: 1px solid #dbe3ee;
  background: #ffffff;
  color: #025b7d;
  font-size: 1rem;
  font-weight: 800;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  transition: background 0.2s ease, border-color 0.2s ease, transform 0.2s ease, box-shadow 0.2s ease;
  position: relative;
  z-index: 1;
}

.help-btn:hover {
  background: rgba(7, 126, 157, 0.08);
  border-color: rgba(7, 126, 157, 0.25);
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(7, 126, 157, 0.15);
}

.help-icon {
  font-style: normal;
  font-size: 1.1rem;
  font-weight: 900;
  line-height: 1;
  font-family: Georgia, serif;
}

/* ── Panel ───────────────────────────────────────── */
.help-panel {
  position: absolute;
  top: calc(100% + 12px);
  right: 0;
  z-index: 200;
  width: min(380px, calc(100vw - 32px));
  max-height: 520px;
  display: flex;
  flex-direction: column;
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 18px;
  box-shadow: 0 24px 60px rgba(15, 23, 42, 0.18);
  overflow: hidden;
}

/* ── Header ──────────────────────────────────────── */
.help-panel-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 14px 16px;
  border-bottom: 1px solid #eef2f7;
  background: linear-gradient(135deg, #077e9d 0%, #025b7d 100%);
  color: white;
}

.help-panel-title {
  display: flex;
  align-items: center;
  gap: 8px;
}

.help-panel-emoji {
  font-size: 1.1rem;
}

.help-panel-title strong {
  font-size: 0.97rem;
  font-weight: 700;
  letter-spacing: 0.01em;
}

.help-close-btn {
  background: rgba(255,255,255,0.15);
  border: none;
  border-radius: 6px;
  color: white;
  font-size: 0.85rem;
  width: 26px;
  height: 26px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: background 0.15s;
}

.help-close-btn:hover {
  background: rgba(255,255,255,0.3);
}

/* ── Búsqueda ────────────────────────────────────── */
.help-search-wrapper {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 14px;
  border-bottom: 1px solid #f1f5f9;
  background: #f8fafc;
}

.help-search-icon {
  font-size: 0.9rem;
  flex-shrink: 0;
}

.help-search {
  flex: 1;
  border: none;
  background: transparent;
  font-size: 0.88rem;
  color: #374151;
  outline: none;
}

.help-search::placeholder {
  color: #94a3b8;
}

/* ── Contenido scrollable ────────────────────────── */
.help-content {
  flex: 1;
  overflow-y: auto;
  padding: 8px 0;
}

.help-content::-webkit-scrollbar {
  width: 5px;
}
.help-content::-webkit-scrollbar-track {
  background: #f1f5f9;
}
.help-content::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 4px;
}

/* ── Estado vacío ────────────────────────────────── */
.help-empty {
  padding: 28px 20px;
  text-align: center;
  color: #64748b;
}

.help-empty span {
  display: block;
  font-size: 2rem;
  margin-bottom: 8px;
}

.help-empty p {
  font-size: 0.88rem;
  margin: 0;
}

/* ── Sección ─────────────────────────────────────── */
.help-section {
  border-bottom: 1px solid #f1f5f9;
}

.help-section:last-child {
  border-bottom: none;
}

.help-section-toggle {
  display: flex;
  align-items: center;
  gap: 10px;
  width: 100%;
  padding: 12px 16px;
  background: transparent;
  border: none;
  text-align: left;
  cursor: pointer;
  transition: background 0.15s;
}

.help-section-toggle:hover {
  background: #f8fafc;
}

.help-section-icon {
  font-size: 1.05rem;
  flex-shrink: 0;
}

.help-section-title {
  flex: 1;
  font-size: 0.9rem;
  font-weight: 600;
  color: #1e293b;
}

.help-chevron {
  font-size: 1.3rem;
  color: #94a3b8;
  transition: transform 0.25s ease;
  line-height: 1;
}

.help-chevron.rotated {
  transform: rotate(90deg);
}

/* ── Cuerpo de sección ───────────────────────────── */
.help-section-body {
  padding: 4px 16px 14px 16px;
  overflow: hidden;
}

.help-section-desc {
  font-size: 0.84rem;
  color: #475569;
  margin: 0 0 10px 0;
  line-height: 1.5;
}

/* ── Pasos ───────────────────────────────────────── */
.help-steps {
  list-style: none;
  margin: 0 0 10px 0;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.help-step {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  font-size: 0.83rem;
  color: #374151;
  line-height: 1.45;
}

.help-step-num {
  flex-shrink: 0;
  width: 20px;
  height: 20px;
  background: #077e9d;
  color: white;
  border-radius: 999px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 0.72rem;
  font-weight: 700;
  margin-top: 1px;
}

/* ── Consejo ─────────────────────────────────────── */
.help-tip {
  display: flex;
  gap: 8px;
  align-items: flex-start;
  background: #fef9ec;
  border: 1px solid #fde68a;
  border-radius: 8px;
  padding: 8px 10px;
  font-size: 0.8rem;
  color: #92400e;
  line-height: 1.45;
  margin-top: 8px;
}

.help-tip span:first-child {
  flex-shrink: 0;
}

/* ── Footer ──────────────────────────────────────── */
.help-panel-footer {
  padding: 10px 16px;
  border-top: 1px solid #eef2f7;
  background: #f8fafc;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  font-size: 0.78rem;
  color: #64748b;
}

.help-contact-link {
  color: #077e9d;
  font-weight: 700;
  text-decoration: none;
}

.help-contact-link:hover {
  text-decoration: underline;
}

/* ── Transiciones ────────────────────────────────── */
.help-panel-enter-active,
.help-panel-leave-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
}

.help-panel-enter-from,
.help-panel-leave-to {
  opacity: 0;
  transform: translateY(-8px) scale(0.97);
}

.help-collapse-enter-active,
.help-collapse-leave-active {
  transition: max-height 0.25s ease, opacity 0.2s ease;
  max-height: 400px;
  overflow: hidden;
}

.help-collapse-enter-from,
.help-collapse-leave-to {
  max-height: 0;
  opacity: 0;
}

/* ── Mobile ──────────────────────────────────────── */
@media (max-width: 768px) {
  .help-panel {
    right: -8px;
    max-height: 70vh;
  }
}
</style>
