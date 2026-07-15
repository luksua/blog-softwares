<template>
  <div ref="vistaPreviaRef" class="vista-previa-container">
    <div class="vista-previa-aviso">
      <i class="bi bi-eye-fill" aria-hidden="true"></i>
      Así se vería este registro publicado. Los cambios no se han guardado todavía.
    </div>

    <DetalleRegistroVisual
      ref="detalleVisualRef"
      :titulo="titulo"
      :version="version"
      :resumen="resumen"
      :area-nombre="areaNombre"
      :categorias="categorias"
      :imagen-url="imagenUrl"
      :fecha-texto="fechaTexto"
      :contenido-html="contenidoHtml"
      layout="con-indice"
      contenido-vacio-texto="Todavía no hay contenido escrito en el editor."
    >
      <template #indice>
        <div class="indice-header">
          <span class="indice-icon" aria-hidden="true">☰</span>
          <h2 class="indice-titulo">Índice</h2>
        </div>

        <nav class="indice-nav" aria-label="Índice de la vista previa">
          <ul class="indice-lista">
            <li v-if="resumen" class="indice-item">
              <a
                href="#resumen"
                class="indice-link indice-nivel-fijo"
                :class="{ activo: headingActivo === 'resumen' }"
                @click.prevent="scrollToElement('resumen')"
              >
                <span class="indice-bullet" aria-hidden="true">•</span>
                <span>Resumen</span>
              </a>
            </li>

            <li
              v-for="heading in headings"
              :key="heading.id"
              class="indice-item"
            >
              <a
                :href="`#${heading.id}`"
                class="indice-link"
                :class="[
                  `indice-nivel-${heading.level}`,
                  { activo: headingActivo === heading.id }
                ]"
                @click.prevent="scrollToElement(heading.id)"
              >
                <span class="indice-bullet" aria-hidden="true">•</span>
                <span>{{ heading.text }}</span>
              </a>
            </li>

            <li v-if="headings.length === 0" class="indice-vacio">
              Sin secciones
            </li>
          </ul>
        </nav>
      </template>
    </DetalleRegistroVisual>
  </div>
</template>

<script setup lang="ts">
import {
  nextTick,
  onBeforeUnmount,
  onMounted,
  ref,
  watch,
} from 'vue'

import DetalleRegistroVisual from '../shared/DetalleRegistroVisual.vue'

interface Heading {
  id: string
  text: string
  level: number
}

interface DetalleRegistroVisualExpuesto {
  contenidoRef?:
    | HTMLElement
    | {
        value: HTMLElement | null
      }
    | null
}

const props = withDefaults(defineProps<{
  titulo?: string
  version?: string
  resumen?: string
  areaNombre?: string
  categorias?: string[]
  imagenUrl?: string | null
  fechaTexto?: string
  contenidoHtml?: string
}>(), {
  titulo: '',
  version: '',
  resumen: '',
  areaNombre: '',
  categorias: () => [],
  imagenUrl: null,
  fechaTexto: '',
  contenidoHtml: '',
})

const vistaPreviaRef = ref<HTMLElement | null>(null)

const detalleVisualRef =
  ref<DetalleRegistroVisualExpuesto | null>(null)

const headings = ref<Heading[]>([])
const headingActivo = ref('resumen')

let observer: IntersectionObserver | null = null
let temporizadorIndice: ReturnType<typeof setTimeout> | null = null

/**
 * Obtiene el contenedor HTML expuesto por DetalleRegistroVisual.
 * Se contemplan ambas posibilidades porque Vue puede desenvolver
 * automáticamente el ref expuesto por el componente hijo.
 */
const obtenerContenidoElement = (): HTMLElement | null => {
  const contenidoExpuesto =
    detalleVisualRef.value?.contenidoRef

  if (!contenidoExpuesto) {
    return null
  }

  if (contenidoExpuesto instanceof HTMLElement) {
    return contenidoExpuesto
  }

  return contenidoExpuesto.value ?? null
}

const normalizarId = (texto: string): string => {
  return texto
    .toLowerCase()
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '')
}

const generarIndice = async () => {
  await nextTick()

  const contenidoElement = obtenerContenidoElement()

  if (!contenidoElement) {
    headings.value = []
    return
  }

  const encabezados = contenidoElement.querySelectorAll<HTMLElement>(
    'h1, h2, h3, h4, h5, h6'
  )

  const idsUtilizados = new Map<string, number>()

  headings.value = Array.from(encabezados).map(
    (encabezado, index) => {
      const text =
        encabezado.textContent?.trim() ||
        `Sección ${index + 1}`

      const level = Number(
        encabezado.tagName.replace('H', '')
      )

      const slugBase =
        normalizarId(text) || `seccion-${index + 1}`

      const cantidad =
        (idsUtilizados.get(slugBase) ?? 0) + 1

      idsUtilizados.set(slugBase, cantidad)

      const slugFinal =
        cantidad > 1
          ? `${slugBase}-${cantidad}`
          : slugBase

      const id = `preview-heading-${slugFinal}`

      encabezado.id = id
      encabezado.style.scrollMarginTop = '90px'

      return {
        id,
        text,
        level,
      }
    }
  )

  iniciarObserver()
}

const iniciarObserver = () => {
  observer?.disconnect()

  const scrollContainer = vistaPreviaRef.value
  const contenidoElement = obtenerContenidoElement()

  if (!scrollContainer || !contenidoElement) {
    return
  }

  observer = new IntersectionObserver(
    entries => {
      const visibles = entries
        .filter(entry => entry.isIntersecting)
        .sort(
          (a, b) =>
            a.boundingClientRect.top -
            b.boundingClientRect.top
        )

      const primerVisible = visibles[0]

      if (primerVisible?.target.id) {
        headingActivo.value = primerVisible.target.id
      }
    },
    {
      root: scrollContainer,
      rootMargin: '-10% 0px -75% 0px',
      threshold: 0,
    }
  )

  const resumenElement =
    scrollContainer.querySelector<HTMLElement>('#resumen')

  if (resumenElement) {
    observer.observe(resumenElement)
  }

  contenidoElement
    .querySelectorAll<HTMLElement>(
      'h1, h2, h3, h4, h5, h6'
    )
    .forEach(element => {
      observer?.observe(element)
    })
}

const scrollToElement = (elementId: string) => {
  const scrollContainer = vistaPreviaRef.value

  if (!scrollContainer) {
    return
  }

  const element =
    scrollContainer.querySelector<HTMLElement>(
      `#${elementId}`
    )

  if (!element) {
    return
  }

  const containerRect =
    scrollContainer.getBoundingClientRect()

  const elementRect =
    element.getBoundingClientRect()

  const top =
    scrollContainer.scrollTop +
    elementRect.top -
    containerRect.top -
    24

  scrollContainer.scrollTo({
    top,
    behavior: 'smooth',
  })
}

const programarGeneracionIndice = () => {
  if (temporizadorIndice) {
    clearTimeout(temporizadorIndice)
  }

  /*
   * Evita reconstruir el índice en cada pulsación inmediata
   * cuando la vista previa está conectada a un editor en vivo.
   */
  temporizadorIndice = setTimeout(() => {
    void generarIndice()
  }, 80)
}

watch(
  () => [
    props.contenidoHtml,
    props.resumen,
  ],
  programarGeneracionIndice,
  {
    immediate: true,
    flush: 'post',
  }
)

onMounted(() => {
  programarGeneracionIndice()
})

onBeforeUnmount(() => {
  observer?.disconnect()

  if (temporizadorIndice) {
    clearTimeout(temporizadorIndice)
  }
})
</script>

<style scoped>
.vista-previa-container {
  display: flex;
  flex-direction: column;
  gap: 12px;
  height: 100%;
  min-height: 0;
  overflow-x: hidden;
  overflow-y: auto;
}

.vista-previa-aviso {
  position: sticky;
  top: 0;
  z-index: 20;
  display: flex;
  flex-shrink: 0;
  align-items: center;
  gap: 8px;
  padding: 10px 14px;
  color: #075985;
  font-size: 0.82rem;
  font-weight: 600;
  background: rgba(240, 249, 255, 0.96);
  border: 1px solid #bae6fd;
  border-radius: 10px;
  backdrop-filter: blur(8px);
}

/* Índice */

.indice-header {
  display: flex;
  flex-shrink: 0;
  align-items: center;
  gap: 10px;
  padding-bottom: 12px;
  margin-bottom: 16px;
  border-bottom: 2px solid #e2e8f0;
}

.indice-icon {
  color: var(--primary);
  font-size: 1rem;
}

.indice-titulo {
  margin: 0;
  color: #0f172a;
  font-size: 0.85rem;
  font-weight: 700;
  letter-spacing: 0.06em;
  text-transform: uppercase;
}

.indice-nav {
  flex: 1;
  overflow-y: auto;
  scrollbar-color: #cbd5e1 transparent;
  scrollbar-width: thin;
}

.indice-nav::-webkit-scrollbar {
  width: 4px;
}

.indice-nav::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 999px;
}

.indice-lista {
  display: flex;
  flex-direction: column;
  gap: 2px;
  padding: 0;
  margin: 0;
  list-style: none;
}

.indice-item {
  width: 100%;
}

.indice-link {
  display: flex;
  align-items: flex-start;
  gap: 8px;
  padding: 6px 10px;
  color: #64748b;
  font-size: 0.82rem;
  line-height: 1.4;
  text-decoration: none;
  border-left: 2px solid transparent;
  border-radius: 8px;
  transition:
    color 0.2s ease,
    background-color 0.2s ease,
    border-color 0.2s ease;
}

.indice-link:hover {
  color: var(--primary);
  background: #e2e8f0;
  border-left-color: var(--primary);
}

.indice-link.activo {
  color: var(--primary);
  font-weight: 600;
  background: rgba(7, 126, 157, 0.08);
  border-left-color: var(--primary);
}

.indice-bullet {
  flex-shrink: 0;
  margin-top: 1px;
  color: var(--primary);
}

.indice-nivel-fijo,
.indice-nivel-1,
.indice-nivel-2 {
  font-weight: 600;
}

.indice-nivel-3 {
  padding-left: 22px;
  font-size: 0.79rem;
}

.indice-nivel-4 {
  padding-left: 34px;
  color: #94a3b8;
  font-size: 0.77rem;
}

.indice-nivel-5,
.indice-nivel-6 {
  padding-left: 46px;
  color: #94a3b8;
  font-size: 0.75rem;
}

.indice-vacio {
  padding: 8px 10px;
  color: #94a3b8;
  font-size: 0.8rem;
  font-style: italic;
}

/*
 * Evita que el ancho natural del contenido fuerce
 * horizontalmente la vista previa.
 */
.vista-previa-container :deep(.drv-card) {
  width: 100%;
  min-width: 0;
}

.vista-previa-container :deep(.drv-contenido-columna) {
  min-width: 0;
}
</style>