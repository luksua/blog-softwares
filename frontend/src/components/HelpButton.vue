<template>
  <!-- Botón de Ayuda -->
  <button class="border rounded-circle help-btn" type="button" title="Ayuda" aria-label="Abrir ayuda"
    @click.stop="abrirModal">
    <span class="help-icon">?</span>
  </button>

  <!-- Modal Bootstrap -->
  <Teleport to="body">
    <div v-if="mostrarPanel" class="modal fade show d-block help-bs-modal" tabindex="-1" role="dialog" aria-modal="true"
      aria-labelledby="helpModalTitle" @click.self="cerrarPanel">
      <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-xl">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
          <!-- Header -->
          <div class="modal-header help-modal-header">
            <h5 id="helpModalTitle" class="modal-title d-flex align-items-center gap-2">
              <span class="header-icon"><i class="bi bi-lightbulb-fill"></i></span>
              <span>Centro de Ayuda</span>
            </h5>
            <button type="button" class="btn-close btn-close-white" aria-label="Cerrar" @click="cerrarPanel"></button>
          </div>

          <!-- Buscador -->
          <div class="search-section">
            <div class="input-group">
              <span class="input-group-text bg-white border-end-0">
                <i class="bi bi-search"></i>
              </span>
              <input v-model="busqueda" type="text" class="form-control border-start-0 ps-0"
                placeholder="Buscar en la ayuda..." @input="filtrarAyuda" />
              <button v-if="busqueda" class="btn btn-link text-muted" type="button"
                @click="busqueda = ''; filtrarAyuda()">
                <i class="bi bi-x-lg"></i>
              </button>
            </div>
          </div>

          <!-- Contenido de dos columnas -->
          <div class="modal-body p-0">
            <div v-if="seccionesFiltradas.length === 0" class="text-center text-muted py-5 px-4">
              <div class="empty-state">
                <i class="bi bi-search fs-1"></i>
                <p class="mt-3 mb-0">
                  No se encontraron resultados para "<strong>{{ busqueda }}</strong>"
                </p>
              </div>
            </div>

            <div v-else class="help-layout">
              <!-- Panel izquierdo: Lista de títulos -->
              <div class="help-sidebar">
                <div class="sidebar-header">
                  <i class="bi bi-list-ul"></i>
                  <span>Temas de ayuda</span>
                </div>
                <div class="sidebar-items">
                  <button v-for="seccion in seccionesFiltradas" :key="seccion.id" class="sidebar-item"
                    :class="{ active: seccionActiva?.id === seccion.id }" @click="seleccionarSeccion(seccion)">
                    <i :class="getSeccionIcon(seccion.id)"></i>
                    <span class="sidebar-item-title">{{ seccion.titulo }}</span>
                  </button>
                </div>
              </div>

              <!-- Panel derecho: Contenido detallado -->
              <div class="help-content" v-if="seccionActiva">
                <div class="content-header">
                  <div class="content-title-wrapper">
                    <i :class="getSeccionIcon(seccionActiva.id)" class="content-icon"></i>
                    <h3 class="content-title">{{ seccionActiva.titulo }}</h3>
                  </div>
                  <span class="content-badge">Guía rápida</span>
                </div>

                <div class="content-body">
                  <p class="content-description">{{ seccionActiva.descripcion }}</p>

                  <div v-if="seccionActiva.pasos && seccionActiva.pasos.length" class="steps-section">
                    <div class="steps-header">
                      <i class="bi bi-check-circle-fill"></i>
                      <span>Pasos a seguir</span>
                    </div>
                    <ol class="help-steps">
                      <li v-for="(paso, idx) in seccionActiva.pasos" :key="idx" class="help-step"
                        :class="{ 'help-step-with-icon': esPasoConDetalle(paso) }">
                        <!-- Si el paso es texto normal -->
                        <template v-if="!esPasoConDetalle(paso)">
                          <span class="step-number">{{ idx + 1 }}</span>

                          <span class="step-text">
                            {{ getPasoTexto(paso) }}
                          </span>
                        </template>

                        <!-- Si el paso es objeto con icono -->
                        <template v-else>
                          <span class="step-icon">
                            <i :class="['bi', getPasoIcono(paso)]"></i>
                          </span>

                          <div class="step-content">
                            <strong class="step-title">
                              {{ getPasoTexto(paso) }}
                            </strong>

                            <p v-if="getPasoDescripcion(paso)" class="step-description">
                              {{ getPasoDescripcion(paso) }}
                            </p>
                          </div>
                        </template>
                      </li>
                    </ol>
                  </div>
                  <div v-if="seccionActiva.consejo" class="tip-section">
                    <div class="tip-header">
                      <i class="bi bi-lightbulb-fill"></i>
                      <span>Consejo útil</span>
                    </div>
                    <p class="tip-text">{{ seccionActiva.consejo }}</p>
                  </div>
                </div>

                <div class="content-footer">
                  <button class="btn-footer" @click="cerrarPanel">
                    <i class="bi bi-check-lg"></i>
                    Entendido
                  </button>
                </div>
              </div>

              <!-- Estado de selección inicial -->
              <div v-else class="help-content help-content-empty">
                <div class="empty-selection">
                  <i class="bi bi-hand-index-thumb-fill"></i>
                  <p>Selecciona un tema del menú lateral para ver la ayuda</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div v-if="mostrarPanel" class="modal-backdrop fade show help-bs-backdrop" @click="cerrarPanel"></div>
  </Teleport>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'

const mostrarPanel = ref(false)
const busqueda = ref('')
const seccionActiva = ref<SeccionAyuda | null>(null)

type PasoAyuda = string | {
  texto: string
  descripcion?: string
  icono?: string
}

interface SeccionAyuda {
  id: string
  titulo: string
  descripcion: string
  icono?: string
  pasos?: PasoAyuda[]
  consejo?: string
  palabrasClave: string[]
}

const esPasoConDetalle = (
  paso: PasoAyuda
): paso is { texto: string; descripcion?: string; icono?: string } => {
  return typeof paso !== 'string'
}

const getPasoTexto = (paso: PasoAyuda) => {
  return typeof paso === 'string' ? paso : paso.texto
}

const getPasoDescripcion = (paso: PasoAyuda) => {
  return typeof paso === 'string' ? '' : paso.descripcion || ''
}

const getPasoIcono = (paso: PasoAyuda) => {
  return typeof paso === 'string'
    ? 'bi-check-circle-fill'
    : paso.icono || 'bi-info-circle-fill'
}

const secciones: SeccionAyuda[] = [
  {
    id: 'blog',
    titulo: 'Cómo leer el Blog',
    descripcion: 'El Blog muestra las actualizaciones y novedades de la organización. Aquí encontrarás toda la información relevante sobre cambios, mejoras y anuncios importantes.',
    pasos: [
      'Haz clic en "Blog" en el menú superior.',
      'Desplázate para ver las publicaciones más recientes.',
      'Haz clic en una publicación para ver el detalle completo.',
      'Usa el botón de marcador en la parte inferior izquierda para guardar publicaciones de interés.',
    ],
    consejo: 'Las publicaciones más recientes aparecen primero. Usa los filtros de categoría para encontrar temas específicos.',
    palabrasClave: ['blog', 'publicacion', 'noticia', 'leer', 'articulo'],
  },
  {
    id: 'mapa-iconos',
    titulo: 'Categoria Iconos',
    descripcion: 'Cada tipo de documento o actualización tiene un icono asociado para identificarlo visualmente, aqui puedes encontrar lo que significa cada.',
    icono: 'bi-tags-fill',
    pasos: [
      {
        texto: 'Manual de usuario',
        descripcion: 'Representa documentación dirigida al usuario final.',
        icono: 'bi-person-lines-fill',
      },
      {
        texto: 'Manual técnico',
        descripcion: 'Representa documentación técnica o de soporte.',
        icono: 'bi-tools',
      },
      {
        texto: 'Instalador',
        descripcion: 'Representa archivos o procesos de instalación.',
        icono: 'bi-box-arrow-down',
      },
      {
        texto: 'Actualización del sistema',
        descripcion: 'Representa cambios o actualizaciones generales del sistema.',
        icono: 'bi-arrow-repeat',
      },
      {
        texto: 'Nueva funcionalidad',
        descripcion: 'Representa una función nueva agregada al sistema.',
        icono: 'bi-stars',
      },
      {
        texto: 'Mejora',
        descripcion: 'Representa una mejora sobre una función existente.',
        icono: 'bi-arrow-up-circle-fill',
      },
      {
        texto: 'Corrección de errores',
        descripcion: 'Representa solución de bugs o fallos.',
        icono: 'bi-bug-fill',
      },
      {
        texto: 'Parche de seguridad',
        descripcion: 'Representa ajustes relacionados con seguridad.',
        icono: 'bi-shield-fill-check',
      },
      {
        texto: 'Guía de instalación',
        descripcion: 'Representa instrucciones para instalar correctamente.',
        icono: 'bi-journal-arrow-down',
      },
      {
        texto: 'Guía rápida',
        descripcion: 'Representa una explicación breve o resumida.',
        icono: 'bi-lightning-charge-fill',
      },
      {
        texto: 'Documentación',
        descripcion: 'Representa documentos informativos generales.',
        icono: 'bi-file-earmark-text-fill',
      },
      {
        texto: 'Notas de versión',
        descripcion: 'Representa cambios incluidos en una versión.',
        icono: 'bi-card-list',
      },
      {
        texto: 'General',
        descripcion: 'Se usa cuando no hay una categoría específica.',
        icono: 'bi-info-circle-fill',
      },
    ],
    palabrasClave: ['mapa', 'iconos', 'bootstrap', 'categorias'],
  },
  {
    id: 'registros',
    titulo: 'Mis Registros',
    descripcion: 'En esta sección puedes crear y gestionar tus propias actualizaciones o reportes. Es tu espacio personal para compartir información con tu equipo.',
    pasos: [
      'Ve a "Mis registros" desde el menú superior.',
      'Haz clic en "+ Nuevo registro" para crear uno nuevo.',
      'Completa el formulario con título, contenido y categoría.',
      'Guarda el borrador o envíalo directamente a revisión.',
    ],
    consejo: 'Puedes guardar como borrador para continuar editando después sin perder el trabajo. El sistema guarda automáticamente cada 30 segundos.',
    palabrasClave: ['registro', 'crear', 'nuevo', 'formulario', 'borrador', 'actualización'],
  },
  {
    id: 'guardados',
    titulo: 'Publicaciones Guardadas',
    descripcion: 'Aquí encuentras las publicaciones que marcaste como favoritas para acceder rápidamente a ellas cuando las necesites.',
    pasos: [
      'Desde cualquier publicación, haz clic en el ícono de marcador en la parte inferior izquierda.',
      'Ve a "Guardados" (el ícono de marcador en el menú) para verlas todas.',
      'Haz clic en cualquier publicación guardada para abrirla.',
      'Para eliminar una de guardados, vuelve a hacer clic en el marcador.',
    ],
    palabrasClave: ['guardado', 'favorito', 'marcador', 'bookmark'],
  },
  {
    id: 'notificaciones',
    titulo: 'Notificaciones',
    descripcion: 'Las notificaciones te informan de nuevas publicaciones, comentarios o actualizaciones relevantes para que no te pierdas nada importante.',
    pasos: [
      'Haz clic en el ícono de campana en la parte superior derecha.',
      'Las notificaciones no leídas aparecen resaltadas en color.',
      'Haz clic en una notificación para ir directamente al contenido.',
      'Usa "Marcar todas como leídas" para limpiar las notificaciones.',
    ],
    consejo: 'Las notificaciones se actualizan automáticamente en tiempo real. No necesitas recargar la página.',
    palabrasClave: ['notificacion', 'alerta', 'campana', 'aviso'],
  },
  {
    id: 'supervision',
    titulo: 'Supervisión',
    descripcion: 'El módulo de supervisión permite a los administradores de área revisar los registros del equipo y dar feedback.',
    pasos: [
      'Ve a "Supervisión" en el menú (solo visible si tienes permisos).',
      'Verás los registros de los miembros de tu área.',
      'Puedes filtrar por estado, fecha o empleado.',
      'Haz clic en un registro para revisarlo y agregar observaciones.',
    ],
    consejo: 'Solo los jefes de área y administradores tienen acceso a esta sección. Si crees que deberías tener acceso, contacta al administrador.',
    palabrasClave: ['supervision', 'admin', 'area', 'equipo', 'revisar'],
  },
  {
    id: 'sesion',
    titulo: 'Cerrar Sesión',
    descripcion: 'Para salir del sistema de forma segura y proteger tu información.',
    pasos: [
      'Haz clic en el botón rojo de salida (→) en la parte superior derecha.',
      'Confirma que deseas cerrar sesión.',
      'Serás redirigido al portal de intranet.',
    ],
    consejo: 'Siempre cierra sesión al terminar, especialmente en equipos compartidos o públicos.',
    palabrasClave: ['sesion', 'cerrar', 'salir', 'logout'],
  },
]

const getSeccionIcon = (id: string) => {
  const iconMap: Record<string, string> = {
    blog: 'bi bi-newspaper',
    registros: 'bi bi-pencil-square',
    guardados: 'bi bi-bookmark-star-fill',
    notificaciones: 'bi bi-bell-fill',
    supervision: 'bi bi-eyeglasses',
    sesion: 'bi bi-box-arrow-right',
    'mapa-iconos': 'bi bi-tags-fill',
  }

  return iconMap[id] || 'bi bi-question-circle'
}

// const tieneNovedad = (id: string) => {
//   const seccion = secciones.find(s => s.id === id)
//   return seccion?.novedad || false
// }

const normalizarTexto = (texto: string) => {
  return texto
    .toLowerCase()
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
}

const getTextoPasoParaBusqueda = (paso: PasoAyuda) => {
  if (typeof paso === 'string') return paso

  return [
    paso.texto,
    paso.descripcion || '',
    paso.icono || '',
  ].join(' ')
}

const seccionesFiltradas = computed(() => {
  if (!busqueda.value.trim()) return secciones

  const q = normalizarTexto(busqueda.value)

  return secciones.filter((s) => {
    const campos = [
      s.titulo,
      s.descripcion,
      s.consejo || '',
      ...(s.pasos || []).map(getTextoPasoParaBusqueda),
      ...s.palabrasClave,
    ]

    return campos.some((campo) =>
      normalizarTexto(campo).includes(q)
    )
  })
})

const seleccionarSeccion = (seccion: SeccionAyuda) => {
  seccionActiva.value = seccion
}

const filtrarAyuda = () => {
  if (busqueda.value.trim() && seccionesFiltradas.value.length > 0) {
    // Si hay búsqueda, seleccionamos el primer resultado automáticamente
    if (!seccionActiva.value || !seccionesFiltradas.value.some(s => s.id === seccionActiva.value?.id)) {
      seccionActiva.value = seccionesFiltradas.value[0]
    }
  } else if (!busqueda.value.trim() && seccionActiva.value) {
    // Si se limpia la búsqueda, mantener la selección actual si existe
    const aunExiste = secciones.some(s => s.id === seccionActiva.value?.id)
    if (!aunExiste) {
      seccionActiva.value = secciones[0]
    }
  }
}

watch(mostrarPanel, (abierto) => {
  document.body.style.overflow = abierto ? 'hidden' : ''
  if (abierto && !seccionActiva.value && secciones.length > 0) {
    seccionActiva.value = secciones[0]
  }
  if (!abierto) {
    busqueda.value = ''
  }
})

onMounted(() => {
  window.addEventListener('keydown', cerrarConEscape)
})

onUnmounted(() => {
  window.removeEventListener('keydown', cerrarConEscape)
  document.body.style.overflow = ''
})

const abrirModal = () => {
  mostrarPanel.value = true
}

const cerrarPanel = () => {
  mostrarPanel.value = false
}

const cerrarConEscape = (event: KeyboardEvent) => {
  if (event.key === 'Escape') {
    cerrarPanel()
  }
}

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
.help-modal-overlay {
  position: fixed;
  inset: 0;
  z-index: 3000;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 24px;
  background: rgba(15, 23, 42, 0.55);
  backdrop-filter: blur(3px);
}

.help-modal {
  width: min(720px, 100%);
  max-height: min(720px, calc(100vh - 48px));
  display: flex;
  flex-direction: column;
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 20px;
  box-shadow: 0 30px 80px rgba(15, 23, 42, 0.35);
  overflow: hidden;
}

/* Modal Header */
.help-modal-header {
  background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
  color: white;
  border-bottom: none;
  padding: 20px 24px;
}

.header-icon {
  font-size: 1.2rem;
}

/* Sección de búsqueda */
.search-section {
  padding: 16px 24px;
  background: #f8fafc;
  border-bottom: 1px solid #e2e8f0;
}

.search-section .input-group {
  box-shadow: var(--shadow-sm);
  border-radius: 12px;
  overflow: hidden;
}

.search-section .input-group-text {
  border: 1px solid #e2e8f0;
  border-right: none;
  color: #94a3b8;
}

.search-section .form-control {
  border: 1px solid #e2e8f0;
  border-left: none;
  font-size: 0.9rem;
}

.search-section .form-control:focus {
  box-shadow: none;
  border-color: #e2e8f0;
}

.search-section .btn-link {
  border: 1px solid #e2e8f0;
  border-left: none;
  border-radius: 0 12px 12px 0;
  text-decoration: none;
}

/* Layout de dos columnas */
.help-layout {
  display: flex;
  min-height: 500px;
  max-height: 550px;
}

/* Sidebar izquierdo */
.help-sidebar {
  width: 280px;
  background: #ffffff;
  border-right: 1px solid #e2e8f0;
  display: flex;
  flex-direction: column;
  flex-shrink: 0;
}

.sidebar-header {
  padding: 16px 20px;
  background: #f8fafc;
  border-bottom: 1px solid #e2e8f0;
  font-weight: 600;
  font-size: 0.85rem;
  color: var(--primary);
  display: flex;
  align-items: center;
  gap: 8px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.sidebar-header i {
  font-size: 1rem;
}

.sidebar-items {
  flex: 1;
  overflow-y: auto;
  padding: 8px 0;
}

.sidebar-item {
  display: flex;
  align-items: center;
  gap: 12px;
  width: 100%;
  padding: 12px 20px;
  background: transparent;
  border: none;
  cursor: pointer;
  transition: var(--transition);
  text-align: left;
  color: #475569;
  position: relative;
}

.sidebar-item:hover {
  background: rgba(7, 126, 157, 0.05);
}

.sidebar-item.active {
  background: rgba(7, 126, 157, 0.1);
  color: var(--primary);
  border-left: 3px solid var(--primary);
}

.sidebar-item i {
  font-size: 1.1rem;
  width: 24px;
  flex-shrink: 0;
}

.sidebar-item-title {
  flex: 1;
  font-size: 0.85rem;
  font-weight: 500;
}

@keyframes pulse {

  0%,
  100% {
    opacity: 1;
  }

  50% {
    opacity: 0.4;
  }
}

/* Scrollbar sidebar */
.sidebar-items::-webkit-scrollbar {
  width: 4px;
}

.sidebar-items::-webkit-scrollbar-track {
  background: #f1f5f9;
}

.sidebar-items::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 4px;
}

/* Contenido derecho */
.help-content {
  flex: 1;
  display: flex;
  flex-direction: column;
  background: #ffffff;
  overflow-y: auto;
}

.help-content-empty {
  display: flex;
  align-items: center;
  justify-content: center;
}

.empty-selection {
  text-align: center;
  color: #94a3b8;
}

.empty-selection i {
  font-size: 3rem;
  margin-bottom: 16px;
  opacity: 0.5;
}

.empty-selection p {
  font-size: 0.9rem;
  margin: 0;
}

.content-header {
  padding: 20px 24px 16px;
  border-bottom: 1px solid #e2e8f0;
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 12px;
}

.content-title-wrapper {
  display: flex;
  align-items: center;
  gap: 12px;
}

.content-icon {
  font-size: 1.4rem;
  color: var(--primary);
}

.content-title {
  font-size: 1.25rem;
  font-weight: 700;
  margin: 0;
  color: #1e293b;
}

.content-badge {
  background: rgba(7, 126, 157, 0.1);
  color: var(--primary);
  padding: 4px 12px;
  border-radius: 20px;
  font-size: 0.7rem;
  font-weight: 600;
}

.content-body {
  flex: 1;
  padding: 20px 24px;
}

.content-description {
  color: #475569;
  line-height: 1.6;
  margin-bottom: 24px;
  font-size: 0.9rem;
}

/* Sección de pasos */
.steps-section {
  margin-bottom: 24px;
}

.steps-header {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 16px;
  padding-bottom: 8px;
  border-bottom: 2px solid rgba(7, 126, 157, 0.2);
}

.steps-header i {
  color: var(--primary);
  font-size: 1rem;
}

.steps-header span {
  font-weight: 600;
  font-size: 0.85rem;
  color: var(--primary);
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.help-steps {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.help-step {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  font-size: 0.88rem;
  color: #334155;
  line-height: 1.5;
}

.step-number {
  flex-shrink: 0;
  width: 24px;
  height: 24px;
  background: linear-gradient(135deg, var(--primary), var(--secondary));
  color: white;
  border-radius: 50%;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 0.7rem;
  font-weight: 700;
}

.step-text {
  flex: 1;
}

/* Sección de consejos */
.tip-section {
  background: rgba(252, 187, 28, 0.1);
  border-left: 3px solid var(--warning);
  border-radius: 8px;
  padding: 16px;
  margin-top: 16px;
}

.tip-header {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 8px;
}

.tip-header i {
  color: var(--warning);
  font-size: 1rem;
}

.tip-header span {
  font-weight: 600;
  font-size: 0.8rem;
  color: #b45309;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.tip-text {
  margin: 0;
  font-size: 0.85rem;
  color: #92400e;
  line-height: 1.5;
}

/* Footer del contenido */
.content-footer {
  padding: 16px 24px;
  border-top: 1px solid #e2e8f0;
  display: flex;
  justify-content: flex-end;
}

.btn-footer {
  background: linear-gradient(135deg, var(--primary), var(--secondary));
  color: white;
  border: none;
  padding: 8px 20px;
  border-radius: 10px;
  font-size: 0.85rem;
  font-weight: 600;
  cursor: pointer;
  transition: var(--transition);
  display: flex;
  align-items: center;
  gap: 8px;
}

.btn-footer:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow-sm);
}

/* Estado vacío */
.empty-state {
  padding: 40px;
  text-align: center;
  color: #94a3b8;
}

.empty-state i {
  font-size: 2.5rem;
  opacity: 0.5;
}

/* Scrollbar del contenido */
.help-content::-webkit-scrollbar {
  width: 6px;
}

.help-content::-webkit-scrollbar-track {
  background: #f1f5f9;
}

.help-content::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 4px;
}

.help-content::-webkit-scrollbar-thumb:hover {
  background: var(--primary);
}

/* Responsive */
@media (max-width: 768px) {
  .help-layout {
    flex-direction: column;
    max-height: none;
  }

  .help-sidebar {
    width: 100%;
    max-height: 200px;
    border-right: none;
    border-bottom: 1px solid #e2e8f0;
  }

  .sidebar-items {
    display: flex;
    overflow-x: auto;
    padding: 8px 12px;
    gap: 8px;
  }

  .sidebar-item {
    width: auto;
    padding: 8px 16px;
    border-radius: 20px;
    white-space: nowrap;
  }

  .sidebar-item.active {
    border-left: none;
    background: rgba(7, 126, 157, 0.1);
  }

  .help-content {
    max-height: 400px;
  }

  .content-header {
    flex-direction: column;
    align-items: flex-start;
  }

  .help-icon {
    font-size: 1.3rem;
  }
}

@media (max-width: 480px) {
  .modal-dialog {
    margin: 8px;
  }

  .content-body {
    padding: 16px;
  }

  .content-header {
    padding: 16px;
  }

  .help-step {
    font-size: 0.8rem;
  }
}

.help-step-with-icon {
  align-items: flex-start;
}

.step-icon {
  flex-shrink: 0;
  width: 34px;
  height: 34px;
  border-radius: 10px;
  background: rgba(7, 126, 157, 0.1);
  color: var(--primary);
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.step-icon i {
  font-size: 1.1rem;
  line-height: 1;
}

.step-content {
  flex: 1;
  min-width: 0;
}

.step-title {
  display: block;
  color: #1e293b;
  font-size: 0.9rem;
  font-weight: 700;
}

.step-description {
  margin: 2px 0 0;
  color: #64748b;
  font-size: 0.82rem;
  line-height: 1.45;
}
</style>