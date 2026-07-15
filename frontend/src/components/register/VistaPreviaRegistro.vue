<template>
  <div ref="vistaPreviaRef" class="vista-previa-container">
    <div class="vista-previa-aviso">
      <i class="bi bi-eye-fill" aria-hidden="true"></i>
      Así se vería este registro publicado. Los cambios no se han guardado todavía.
    </div>

    <!-- Selector de modo de vista previa -->
    <div class="vista-previa-selector" role="group" aria-label="Ancho de vista previa">
      <button
        v-for="modo in MODOS_VISTA"
        :key="modo.id"
        type="button"
        class="vista-previa-modo-btn"
        :class="{ activo: modoActivo === modo.id }"
        @click="cambiarModo(modo.id)"
      >
        <i :class="modo.icono" aria-hidden="true"></i>
        <span>{{ modo.etiqueta }}</span>
      </button>
    </div>

    <div
      class="vista-previa-escala-wrapper"
      :style="{ height: alturaEscalada + 'px' }"
    >
      <div
        ref="lienzoRef"
        class="vista-previa-lienzo"
        :style="{
          width: anchoLienzoActual + 'px',
          transform: `scale(${factorEscala})`,
        }"
      >
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
    </div>
  </div>
</template>
<script setup lang="ts">
import {
  computed,
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

interface ModoVista {
  id: 'desktop' | 'tablet' | 'movil'
  etiqueta: string
  icono: string
  ancho: number
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
const lienzoRef = ref<HTMLElement | null>(null)

const detalleVisualRef =
  ref<DetalleRegistroVisualExpuesto | null>(null)

const headings = ref<Heading[]>([])
const headingActivo = ref('resumen')

/*
 * Anchos de referencia para cada modo. El ancho "desktop" debe
 * coincidir con el que usaste antes como ANCHO_LIENZO_DESKTOP;
 * ajústalos si tu diseño ideal usa otras referencias.
 */
const MODOS_VISTA: ModoVista[] = [
  { id: 'desktop', etiqueta: 'Escritorio', icono: 'bi bi-display', ancho: 1200 },
  { id: 'tablet', etiqueta: 'Tablet', icono: 'bi bi-tablet', ancho: 768 },
  { id: 'movil', etiqueta: 'Móvil', icono: 'bi bi-phone', ancho: 375 },
]

const modoActivo = ref<ModoVista['id']>('desktop')

const anchoLienzoActual = computed(() => {
  return (
    MODOS_VISTA.find(modo => modo.id === modoActivo.value)?.ancho ??
    MODOS_VISTA[0].ancho
  )
})

const factorEscala = ref(1)
const alturaEscalada = ref(0)

let observer: IntersectionObserver | null = null
let resizeObserver: ResizeObserver | null = null
let temporizadorIndice: ReturnType<typeof setTimeout> | null = null

/**
 * Obtiene el contenedor HTML expuesto por DetalleRegistroVisual.
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

const recalcularEscala = () => {
  const contenedor = vistaPreviaRef.value
  const lienzo = lienzoRef.value

  if (!contenedor || !lienzo) {
    return
  }

  const anchoDisponible = contenedor.clientWidth

  if (anchoDisponible <= 0) {
    return
  }

  const nuevoFactor = anchoDisponible / anchoLienzoActual.value
  factorEscala.value = nuevoFactor

  alturaEscalada.value = lienzo.scrollHeight * nuevoFactor
}

const cambiarModo = async (id: ModoVista['id']) => {
  modoActivo.value = id

  // El ancho del lienzo cambió: esperamos a que el DOM
  // se reajuste antes de recalcular escala y altura.
  await nextTick()
  recalcularEscala()
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

  await nextTick()
  recalcularEscala()
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

  resizeObserver = new ResizeObserver(() => {
    recalcularEscala()
  })

  if (vistaPreviaRef.value) {
    resizeObserver.observe(vistaPreviaRef.value)
  }

  recalcularEscala()
})

onBeforeUnmount(() => {
  observer?.disconnect()
  resizeObserver?.disconnect()

  if (temporizadorIndice) {
    clearTimeout(temporizadorIndice)
  }
})
</script>

<style scoped>
.vista-previa-selector {
  position: sticky;
  top: 52px;
  z-index: 19;
  display: flex;
  flex-shrink: 0;
  gap: 6px;
  padding: 6px;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
}

.vista-previa-modo-btn {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 6px 12px;
  color: #64748b;
  font-size: 0.8rem;
  font-weight: 600;
  cursor: pointer;
  background: transparent;
  border: none;
  border-radius: 8px;
  transition:
    color 0.2s ease,
    background-color 0.2s ease;
}

.vista-previa-modo-btn:hover {
  color: var(--primary);
  background: #e2e8f0;
}

.vista-previa-modo-btn.activo {
  color: #fff;
  background: var(--primary);
}

.vista-previa-escala-wrapper {
  position: relative;
  width: 100%;
  overflow: hidden;
}

.vista-previa-lienzo {
  transform-origin: top left;
}
</style>