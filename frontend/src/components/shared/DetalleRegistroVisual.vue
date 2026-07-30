<template>
    <article class="drv-card">
        <!-- ─── HERO ─── -->
        <header class="drv-hero" :class="{ 'sin-imagen': !imagenUrl }">
            <div v-if="$slots['back-button']" class="drv-hero-btn-pos">
                <slot name="back-button" />
            </div>

            <img v-if="imagenUrl" :src="imagenUrl" :alt="titulo || 'Imagen destacada'" class="drv-hero-image" />
            <div class="drv-hero-overlay" aria-hidden="true"></div>

            <div class="drv-hero-content">
                <h1 class="drv-hero-titulo">{{ titulo || 'Sin título' }}</h1>

                <div class="drv-hero-bottom-info">
                    <div class="drv-hero-meta-left">
                        <span class="drv-version-badge">v{{ version || '0.0' }}</span>
                        <span v-if="fechaTexto" class="drv-fecha-texto">{{ fechaTexto }}</span>
                    </div>

                    <div class="drv-hero-meta-right">
                        <span v-if="estado" :class="['drv-estado-badge', estadoClase]">
                            <span class="drv-estado-dot"></span>
                            {{ estado }}
                        </span>

                        <div v-else-if="mostrarTags" class="drv-tags-container">
                            <span class="drv-tag-gris">{{ areaNombre || 'Sin área' }}</span>
                            <span v-for="cat in categorias" :key="cat" class="drv-tag-gris">{{ cat }}</span>
                            <span v-if="categorias.length === 0 && !areaNombre" class="drv-tag-gris">Sin
                                categorías</span>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- ─── EXTRA (p.ej. metadatos de supervisión) ─── -->
        <div v-if="$slots['meta-extra']" class="drv-meta-extra">
            <slot name="meta-extra" />
        </div>

        <!-- ─── LAYOUT SIMPLE: resumen y contenido apilados ─── -->
        <template v-if="layout === 'stack'">
            <section v-if="resumen" class="drv-resumen-container">
                <h2 class="drv-resumen-titulo">Resumen</h2>
                <p class="drv-resumen-texto">{{ resumen }}</p>
            </section>

            <section class="drv-contenido-container">
                <div v-if="mostrarHeaderContenido" class="drv-contenido-header">
                    <h3>{{ tituloContenido }}</h3>
                </div>
                <div v-if="contenidoHtml" ref="contenidoRef" class="drv-editorjs-editor" v-html="contenidoHtml"></div>
                <p v-else class="drv-contenido-vacio">{{ contenidoVacioTexto }}</p>
            </section>
        </template>

        <!-- ─── LAYOUT CON ÍNDICE: sidebar + contenido + resumen lateral (home) ─── -->
        <template v-else>
            <div class="drv-contenido-wrapper">
                <aside class="drv-indice-sidebar" aria-label="Índice del documento">
                    <slot name="indice" />
                </aside>

                <main class="drv-contenido-columna">
                    <span v-if="resumen" id="resumen" class="drv-resumen-anchor" aria-hidden="true"></span>

                    <section class="drv-contenido-container" aria-label="Contenido completo">
                        <div v-if="mostrarHeaderContenido" class="drv-contenido-header">
                            <h3>{{ tituloContenido }}</h3>
                        </div>
                        <div v-if="contenidoHtml" ref="contenidoRef" class="drv-editorjs-editor" v-html="contenidoHtml">
                        </div>
                        <p v-else class="drv-contenido-vacio">{{ contenidoVacioTexto }}</p>
                    </section>
                </main>

                <aside v-if="resumen" class="drv-indice-resumen" aria-label="Resumen del documento">
                    <h2 class="drv-resumen-titulo">Resumen</h2>
                    <section class="drv-resumen-container drv-resumen-container--lateral">
                        <p class="drv-resumen-texto">{{ resumen }}</p>
                    </section>
                </aside>
            </div>
        </template>
    </article>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import '../../styles/detalleregistro.css'

const props = withDefaults(defineProps<{
    titulo?: string
    version?: string
    fechaTexto?: string
    imagenUrl?: string | null
    resumen?: string
    contenidoHtml?: string
    areaNombre?: string
    categorias?: string[]
    /** Si viene, se muestra un badge de estado en vez de las etiquetas de área/categorías (supervisión). */
    estado?: string
    estadoClase?: string
    layout?: 'stack' | 'con-indice'
    mostrarHeaderContenido?: boolean
    tituloContenido?: string
    contenidoVacioTexto?: string
}>(), {
    titulo: '',
    version: '',
    fechaTexto: '',
    imagenUrl: null,
    resumen: '',
    contenidoHtml: '',
    areaNombre: '',
    categorias: () => [],
    estado: '',
    estadoClase: '',
    layout: 'stack',
    mostrarHeaderContenido: false,
    tituloContenido: 'Contenido completo',
    contenidoVacioTexto: 'Todavía no hay contenido escrito.',
})

const mostrarTags = computed(() => !props.estado)

const contenidoRef = ref<HTMLElement | null>(null)
defineExpose({ contenidoRef })
</script>