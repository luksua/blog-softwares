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

/**
 * Pieza visual única del "detalle de registro" (hero + resumen + contenido).
 * La usan: home/ListVersion.vue (lectura pública, layout="con-indice"),
 * register/ListVersion.vue (mis-registros / supervisión, layout="stack")
 * y register/VistaPreviaRegistro.vue (vista previa en vivo, layout="stack").
 *
 * Antes cada vista tenía su propia copia de este bloque con su propio CSS,
 * ligeramente distinto entre sí (de ahí bugs como el resumen cortado y que
 * no se vieran iguales). Ahora es un único componente: un cambio de estilo
 * se hace acá y se refleja en las tres vistas.
 */
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

// Se expone para que el padre (home) pueda asignarle ids a los headings
// y observarlos con IntersectionObserver para el índice activo.
const contenidoRef = ref<HTMLElement | null>(null)
defineExpose({ contenidoRef })
</script>

<style scoped>
/*
 * Estilos homologados con home/ListVersion.vue.
 * Se conservan los nombres drv-* para no romper la API del componente.
 */

/* ─── Tarjeta principal ─── */

.drv-card {
    width: 100%;
    max-width: 100%;
    overflow: visible;
    background: #ffffff;
    padding: 0;
}

/* ─── Hero ─── */
.drv-hero {
    position: relative;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    width: 100%;
    min-height: 300px;
    overflow: hidden;
    background-color: var(--secondary, #025b7d);
}

.drv-hero.sin-imagen {
    min-height: 200px;
    background: linear-gradient(135deg,
            var(--primary, #077e9d) 0%,
            var(--secondary, #025b7d) 100%);
}

.drv-hero-image,
.drv-hero-overlay {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
}

.drv-hero-image {
    z-index: 1;
    object-fit: cover;
}

.drv-hero-overlay {
    z-index: 2;
    background: linear-gradient(to top,
            rgba(15, 23, 42, 0.9) 0%,
            rgba(15, 23, 42, 0.4) 35%,
            rgba(15, 23, 42, 0) 100%);
}

.drv-hero-btn-pos {
    position: absolute;
    top: 20px;
    left: 15%;
    z-index: 4;
}

/* Permite que el botón entregado por slot conserve el aspecto de ListVersion. */
.drv-hero-btn-pos :deep(button),
.drv-hero-btn-pos :deep(a) {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    color: var(--primary, #077e9d);
    font-size: 0.95rem;
    font-weight: 600;
    cursor: pointer;
    background: rgba(255, 255, 255, 0.85);
    border: 0;
    border-radius: 10px;
    transition: var(--transition, all 0.3s cubic-bezier(0.4, 0, 0.2, 1));
}

.drv-hero-btn-pos :deep(button:hover),
.drv-hero-btn-pos :deep(a:hover) {
    background: #ffffff;
    transform: translateX(-4px);
}

.drv-hero-content {
    position: relative;
    z-index: 3;
    display: flex;
    flex: 1;
    flex-direction: column;
    justify-content: flex-end;
    height: 100%;
    padding: 30px 15% 24px;
}

.drv-hero-titulo {
    margin: 0 0 16px;
    color: #ffffff;
    font-size: 2.2rem;
    font-weight: 700;
    line-height: 1.2;
    text-shadow: 0 2px 8px rgba(0, 0, 0, 0.4);
}

.drv-hero-bottom-info {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    justify-content: space-between;
    gap: 16px;
}

.drv-hero-meta-left {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 16px;
}

.drv-hero-meta-right {
    display: flex;
    align-items: center;
}

.drv-version-badge {
    display: inline-block;
    padding: 6px 14px;
    color: #ffffff;
    font-family: 'Courier New', monospace;
    font-size: 0.85rem;
    font-weight: 600;
    background: linear-gradient(135deg,
            var(--primary, #077e9d) 0%,
            var(--secondary, #025b7d) 100%);
    border-radius: 30px;
    box-shadow: var(--shadow-sm, 0 2px 8px rgba(0, 0, 0, 0.08));
}

.drv-fecha-texto {
    color: rgba(255, 255, 255, 0.9);
    font-size: 0.95rem;
    font-weight: 500;
    text-shadow: 0 1px 4px rgba(0, 0, 0, 0.5);
}

.drv-tags-container {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
}

.drv-tag-gris {
    padding: 5px 12px;
    color: #4a5568;
    font-size: 0.75rem;
    font-weight: 600;
    background: #f4f5f7;
    border-radius: 20px;
    transition: var(--transition, all 0.3s cubic-bezier(0.4, 0, 0.2, 1));
}

.drv-tag-gris:hover {
    color: #ffffff;
    background: var(--primary, #077e9d);
}

/* ─── Badge de estado ─── */
.drv-estado-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    border-radius: 30px;
    font-size: 0.85rem;
    font-weight: 700;
    text-transform: capitalize;
    box-shadow: var(--shadow-sm, 0 2px 8px rgba(0, 0, 0, 0.08));
}

.drv-estado-dot {
    display: inline-block;
    width: 8px;
    height: 8px;
    border-radius: 50%;
}

.estado-publicado {
    color: #2e7d32;
    background: linear-gradient(135deg, #e8f5e9, #c8e6c9);
}

.estado-publicado .drv-estado-dot {
    background: #4caf50;
    box-shadow: 0 0 0 2px rgba(76, 175, 80, 0.2);
}

.estado-borrador {
    color: #f57c00;
    background: linear-gradient(135deg, #fff8e1, #ffecb3);
}

.estado-borrador .drv-estado-dot {
    background: #ff9800;
    box-shadow: 0 0 0 2px rgba(255, 152, 0, 0.2);
}

.estado-revision {
    color: #c62828;
    background: linear-gradient(135deg, #fce4ec, #f8bbd0);
}

.estado-revision .drv-estado-dot {
    background: #f44336;
    box-shadow: 0 0 0 2px rgba(244, 67, 54, 0.2);
}

.estado-inactivo {
    color: #424242;
    background: linear-gradient(135deg, #e0e0e0, #bdbdbd);
}

.estado-inactivo .drv-estado-dot {
    background: #757575;
}

.estado-default {
    color: #1976d2;
    background: linear-gradient(135deg, #e3f2fd, #bbdefb);
}

.estado-default .drv-estado-dot {
    background: #2196f3;
}

/* ─── Metadatos adicionales ─── */
.drv-meta-extra {
    padding: 24px 24px 0;
    max-width: 800px;
    margin: 20px auto 12px auto;
}

/* ─── Layout apilado ─── */
.drv-resumen-container {
    padding: 24px;
    margin: 24px;
    background: #f8fafc;
    border-radius: 12px;
    max-width: 800px;
    max-width: 800px;
    margin: 40px auto 32px auto;
}

.drv-resumen-titulo {
    margin: 0 0 8px;
    color: #0f172a;
    font-size: 0.85rem;
    font-weight: 700;
    letter-spacing: 0.06em;
    text-transform: uppercase;
}

.drv-resumen-texto {
    margin: 0;
    color: #334155;
    font-size: 1.1rem;
    line-height: 1.6;
}

.drv-contenido-container {
    padding: 0 24px 24px;
    max-width: 800px;
    margin: 40px auto 32px auto;
}

.drv-contenido-header {
    padding-bottom: 12px;
    margin-bottom: 20px;
    border-bottom: 2px solid #e2e8f0;
}

.drv-contenido-header h3 {
    margin: 0;
    color: #0f172a;
    font-size: 1.15rem;
    font-weight: 700;
}

.drv-contenido-vacio {
    padding: 8px 0;
    color: #94a3b8;
    font-size: 0.9rem;
    font-style: italic;
}

/* ─── EditorJS ─── */
.drv-editorjs-editor {
    color: #334155;
    font-family: system-ui, -apple-system, sans-serif;
    font-size: 1.05rem;
    line-height: 1.8;
}

:deep(.drv-editorjs-editor p) {
    margin-bottom: 1.2rem;
}

:deep(.drv-editorjs-editor h1),
:deep(.drv-editorjs-editor h2),
:deep(.drv-editorjs-editor h3),
:deep(.drv-editorjs-editor h4),
:deep(.drv-editorjs-editor h5),
:deep(.drv-editorjs-editor h6) {
    margin-top: 2rem;
    margin-bottom: 1rem;
    color: #0f172a;
    scroll-margin-top: 90px;
}

:deep(.drv-editorjs-editor ul),
:deep(.drv-editorjs-editor ol) {
    padding-left: 2rem;
    margin-bottom: 1.2rem;
}

:deep(.drv-editorjs-editor li) {
    margin-bottom: 0.4rem;
}

:deep(.drv-editorjs-editor .editorjs-checklist) {
    padding-left: 0;
    list-style: none;
}

:deep(.drv-editorjs-editor .editorjs-checklist__item) {
    margin-bottom: 0.45rem;
}

:deep(.drv-editorjs-editor .editorjs-checklist__row) {
    display: flex;
    align-items: flex-start;
    gap: 0.65rem;
}

:deep(.drv-editorjs-editor .editorjs-checklist__checkbox) {
    width: 1rem;
    height: 1rem;
    margin-top: 0.38rem;
    accent-color: #077e9d;
}

:deep(.drv-editorjs-editor .editorjs-checklist__item.is-checked .editorjs-checklist__content) {
    color: #64748b;
    text-decoration: line-through;
}

:deep(.drv-editorjs-editor img) {
    display: block;
    max-width: 100%;
    height: auto;
    margin: 24px auto;
    border-radius: 12px;
    box-shadow: var(--shadow-sm, 0 2px 8px rgba(0, 0, 0, 0.08));
}

:deep(.drv-editorjs-editor blockquote) {
    padding-left: 20px;
    margin: 20px 0;
    color: #4a5568;
    font-style: italic;
    border-left: 4px solid var(--primary, #077e9d);
}

:deep(.drv-editorjs-editor pre) {
    padding: 16px;
    margin: 20px 0;
    overflow-x: auto;
    color: #e2e8f0;
    background: #1e293b;
    border-radius: 12px;
}

:deep(.drv-editorjs-editor code) {
    padding: 2px 6px;
    color: #d32f2f;
    font-size: 0.9em;
    background: #f1f5f9;
    border-radius: 6px;
}

/* ─── Layout con índice: índice | contenido | resumen ─── */
.drv-contenido-wrapper {
    position: relative;
    display: grid;
    grid-template-columns: 220px minmax(0, 1fr) 260px;
    gap: 40px;
    width: 100%;
    max-width: 1400px;
    padding: 40px 5%;
    margin: 0 auto;
}

.drv-indice-sidebar,
.drv-indice-resumen {
    position: sticky;
    top: 24px;
    align-self: flex-start;
    display: flex;
    flex-direction: column;
    max-height: calc(100vh - 60px);
    padding: 20px;
    overflow-y: auto;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
}

.drv-indice-sidebar {
    order: 1;
}

/* El contenido del índice llega por slot; :deep() hace que el estilo scoped
   también alcance esas clases creadas por el componente padre. */
.drv-indice-sidebar :deep(.indice-header) {
    display: flex;
    align-items: center;
    flex-shrink: 0;
    gap: 10px;
    padding-bottom: 12px;
    margin-bottom: 16px;
    border-bottom: 2px solid #e2e8f0;
}

.drv-indice-sidebar :deep(.indice-icon) {
    color: var(--primary, #077e9d);
    font-size: 1rem;
}

.drv-indice-sidebar :deep(.indice-titulo) {
    margin: 0;
    color: #0f172a;
    font-size: 0.85rem;
    font-weight: 700;
    letter-spacing: 0.06em;
    text-transform: uppercase;
}

.drv-indice-sidebar :deep(.indice-nav) {
    flex: 1;
    overflow-y: auto;
    scrollbar-color: #cbd5e1 transparent;
    scrollbar-width: thin;
}

.drv-indice-sidebar :deep(.indice-nav)::-webkit-scrollbar {
    width: 4px;
}

.drv-indice-sidebar :deep(.indice-nav)::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 4px;
}

.drv-indice-sidebar :deep(.indice-lista) {
    display: flex;
    flex-direction: column;
    gap: 2px;
    padding: 0;
    margin: 0;
    list-style: none;
}

.drv-indice-sidebar :deep(.indice-item) {
    width: 100%;
}

.drv-indice-sidebar :deep(.indice-link) {
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
    transition: var(--transition, all 0.3s cubic-bezier(0.4, 0, 0.2, 1));
}

.drv-indice-sidebar :deep(.indice-link:hover) {
    color: var(--primary, #077e9d);
    background: #e2e8f0;
    border-left-color: var(--primary, #077e9d);
}

.drv-indice-sidebar :deep(.indice-link.activo) {
    color: var(--primary, #077e9d);
    font-weight: 600;
    background: rgba(7, 126, 157, 0.08);
    border-left-color: var(--primary, #077e9d);
}

.drv-indice-sidebar :deep(.indice-bullet) {
    flex-shrink: 0;
    margin-top: 1px;
    color: var(--primary, #077e9d);
}

.drv-indice-sidebar :deep(.indice-nivel-fijo),
.drv-indice-sidebar :deep(.indice-nivel-1),
.drv-indice-sidebar :deep(.indice-nivel-2) {
    font-weight: 600;
}

.drv-indice-sidebar :deep(.indice-nivel-3) {
    padding-left: 22px;
    font-size: 0.79rem;
}

.drv-indice-sidebar :deep(.indice-nivel-4) {
    padding-left: 34px;
    color: #94a3b8;
    font-size: 0.77rem;
}

.drv-indice-sidebar :deep(.indice-nivel-5),
.drv-indice-sidebar :deep(.indice-nivel-6) {
    padding-left: 46px;
    color: #94a3b8;
    font-size: 0.75rem;
}

.drv-indice-sidebar :deep(.indice-vacio) {
    padding: 8px 10px;
    color: #94a3b8;
    font-size: 0.8rem;
    font-style: italic;
}

.drv-contenido-columna {
    order: 2;
    min-width: 0;
    max-width: 100%;
    margin: 0;
}

.drv-indice-resumen {
    order: 3;
}

.drv-indice-resumen .drv-resumen-titulo {
    flex-shrink: 0;
    padding-bottom: 12px;
    margin-bottom: 16px;
    font-size: 0.85rem;
    border-bottom: 2px solid #e2e8f0;
}

.drv-indice-resumen .drv-resumen-container--lateral {
    padding: 10px;
    margin: 0;
    background: transparent;
    border-radius: 0;
}

.drv-indice-resumen .drv-resumen-texto {
    font-size: 0.95rem;
    line-height: 1.7;
}

.drv-contenido-columna .drv-contenido-container {
    max-width: 100%;
    padding: 0;
    margin: 0;
}

.drv-resumen-anchor {
    display: block;
    height: 1px;
    scroll-margin-top: 90px;
}

/* ─── Responsive ─── */
@media (max-width: 1200px) {
    .drv-contenido-wrapper {
        grid-template-columns: 200px minmax(0, 1fr);
    }

    .drv-indice-resumen {
        display: none;
    }
}

@media (max-width: 991px) {
    .drv-contenido-wrapper {
        grid-template-columns: 1fr;
        gap: 24px;
    }

    .drv-indice-sidebar {
        position: relative;
        top: 0;
        max-height: 300px;
    }

    .drv-contenido-columna {
        max-width: 100%;
    }
}

@media (max-width: 768px) {
    .drv-contenido-wrapper {
        padding: 24px 16px;
    }

    .drv-hero {
        min-height: 250px;
    }

    .drv-hero.sin-imagen {
        min-height: 200px;
    }

    .drv-hero-content {
        padding: 60px 20px 20px;
    }

    .drv-hero-titulo {
        font-size: 1.8rem;
    }

    .drv-hero-btn-pos {
        top: 16px;
        left: 20px;
    }

    .drv-resumen-container {
        padding: 16px;
    }
}

@media (max-width: 480px) {
    .drv-hero-titulo {
        font-size: 1.5rem;
    }

    .drv-hero-bottom-info {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
    }

    .drv-resumen-container {
        margin: 16px;
    }

    .drv-contenido-container {
        padding: 0 16px 16px;
    }

    .drv-meta-extra {
        padding: 16px 16px 0;
    }
}
</style>
