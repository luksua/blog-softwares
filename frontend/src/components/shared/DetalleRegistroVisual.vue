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
/* ─── Tarjeta y hero ─── */
.drv-card {
    overflow: visible;
    background: #ffffff;
    border-radius: 12px;
    box-shadow: var(--shadow-md, 0 4px 20px rgba(0, 0, 0, 0.06));
}

.drv-hero {
    position: relative;
    display: flex;
    min-height: 260px;
    overflow: hidden;
    background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
}

.drv-hero.sin-imagen {
    background:
        radial-gradient(circle at 15% 10%, rgba(252, 187, 28, 0.28), transparent 30%),
        linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
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
    background: linear-gradient(to top, rgba(15, 23, 42, 0.92) 0%, rgba(15, 23, 42, 0.48) 42%, rgba(15, 23, 42, 0.06) 100%);
}

.drv-hero-btn-pos {
    position: absolute;
    top: 20px;
    left: 24px;
    z-index: 4;
}

.drv-hero-content {
    position: relative;
    z-index: 3;
    display: flex;
    flex: 1;
    flex-direction: column;
    justify-content: flex-end;
    gap: 14px;
    padding: 72px 24px 28px;
}

.drv-hero-titulo {
    max-width: 980px;
    margin: 0;
    color: #ffffff;
    font-size: clamp(1.5rem, 2.6vw, 2.4rem);
    font-weight: 760;
    line-height: 1.15;
    text-wrap: balance;
    text-shadow: 0 2px 8px rgba(0, 0, 0, 0.42);
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
    flex-wrap: wrap;
}

.drv-version-badge {
    padding: 6px 14px;
    color: #ffffff;
    font-family: 'Courier New', monospace;
    font-size: 0.82rem;
    font-weight: 700;
    background: rgba(255, 255, 255, 0.18);
    border: 1px solid rgba(255, 255, 255, 0.3);
    border-radius: 999px;
}

.drv-fecha-texto {
    color: rgba(255, 255, 255, 0.92);
    font-size: 0.9rem;
    font-weight: 600;
    text-shadow: 0 1px 4px rgba(0, 0, 0, 0.46);
}

.drv-tags-container {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 8px;
}

.drv-tag-gris {
    padding: 5px 12px;
    color: #4a5568;
    font-size: 0.75rem;
    font-weight: 700;
    background: #f4f5f7;
    border-radius: 999px;
    transition: var(--transition, all 0.15s ease);
}

.drv-tag-gris:hover {
    color: #ffffff;
    background: var(--primary);
}

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
    width: 8px;
    height: 8px;
    border-radius: 50%;
    display: inline-block;
}

.estado-publicado {
    background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%);
    color: #2e7d32;
}

.estado-publicado .drv-estado-dot {
    background: #4caf50;
    box-shadow: 0 0 0 2px rgba(76, 175, 80, 0.2);
}

.estado-borrador {
    background: linear-gradient(135deg, #fff8e1 0%, #ffecb3 100%);
    color: #f57c00;
}

.estado-borrador .drv-estado-dot {
    background: #ff9800;
    box-shadow: 0 0 0 2px rgba(255, 152, 0, 0.2);
}

.estado-revision {
    background: linear-gradient(135deg, #fce4ec 0%, #f8bbd0 100%);
    color: #c62828;
}

.estado-revision .drv-estado-dot {
    background: #f44336;
    box-shadow: 0 0 0 2px rgba(244, 67, 54, 0.2);
}

.estado-inactivo {
    background: linear-gradient(135deg, #e0e0e0 0%, #bdbdbd 100%);
    color: #424242;
}

.estado-inactivo .drv-estado-dot {
    background: #757575;
}

.estado-default {
    background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
    color: #1976d2;
}

.estado-default .drv-estado-dot {
    background: #2196f3;
}

/* ─── Metadatos extra (p.ej. tarjetas de supervisión) ─── */
.drv-meta-extra {
    padding: 24px 24px 0;
}

/* ─── Resumen (misma apariencia apilado o lateral) ─── */
.drv-resumen-container {
    padding: 24px;
    margin: 24px;
    background: #f8fafc;
    border-radius: 12px;
    border-left: 4px solid var(--primary);
}

.drv-resumen-container--lateral {
    padding: 0;
    margin: 0;
    background: transparent;
    border-left: none;
}

.drv-resumen-titulo {
    margin: 0 0 8px;
    color: #0f172a;
    font-size: 0.78rem;
    font-weight: 800;
    letter-spacing: 0.05em;
    text-transform: uppercase;
}

.drv-resumen-texto {
    margin: 0;
    color: #334155;
    font-size: 1rem;
    line-height: 1.7;
}

/* ─── Contenido ─── */
.drv-contenido-container {
    padding: 0 24px 24px;
}

.drv-contenido-header {
    margin-bottom: 20px;
    padding-bottom: 12px;
    border-bottom: 2px solid #e2e8f0;
}

.drv-contenido-header h3 {
    margin: 0;
    font-size: 1.15rem;
    font-weight: 700;
    color: #0f172a;
}

.drv-contenido-vacio {
    padding: 8px 0;
    color: #94a3b8;
    font-size: 0.9rem;
    font-style: italic;
}

.drv-editorjs-editor {
    color: #334155;
    font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    font-size: 1rem;
    line-height: 1.75;
}

:deep(.drv-editorjs-editor p) {
    margin-bottom: 1.1rem;
}

:deep(.drv-editorjs-editor h1),
:deep(.drv-editorjs-editor h2),
:deep(.drv-editorjs-editor h3),
:deep(.drv-editorjs-editor h4),
:deep(.drv-editorjs-editor h5),
:deep(.drv-editorjs-editor h6) {
    margin-top: 1.8rem;
    margin-bottom: 0.9rem;
    color: #0f172a;
}

:deep(.drv-editorjs-editor ul),
:deep(.drv-editorjs-editor ol) {
    margin-bottom: 1.1rem;
    padding-left: 1.8rem;
}

:deep(.drv-editorjs-editor li) {
    margin-bottom: 0.4rem;
}

:deep(.drv-editorjs-editor .editorjs-checklist) {
    list-style: none;
    padding-left: 0;
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
    margin: 20px auto;
    border-radius: 12px;
    box-shadow: var(--shadow-sm, 0 2px 8px rgba(0, 0, 0, 0.08));
}

:deep(.drv-editorjs-editor blockquote) {
    padding-left: 18px;
    margin: 18px 0;
    color: #4a5568;
    font-style: italic;
    border-left: 4px solid var(--primary);
}

:deep(.drv-editorjs-editor pre) {
    padding: 14px;
    margin: 18px 0;
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

/* ─── Layout con índice (home) ─── */
.drv-contenido-wrapper {
    display: grid;
    grid-template-columns: minmax(180px, 220px) minmax(0, 1fr) minmax(240px, 300px);
    gap: clamp(24px, 3vw, 40px);
    padding: 32px 24px;
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

.drv-indice-resumen .drv-resumen-titulo {
    padding-bottom: 12px;
    border-bottom: 2px solid #e2e8f0;
    font-size: 0.85rem;
}

.drv-contenido-columna {
    min-width: 0;
    max-width: 100%;
}

.drv-contenido-columna .drv-contenido-container {
    padding: 0;
    max-width: 100%;
}

.drv-resumen-anchor {
    display: block;
    height: 1px;
    scroll-margin-top: 90px;
}

@media (max-width: 1280px) and (min-width: 992px) {
    .drv-contenido-wrapper {
        grid-template-columns: minmax(170px, 220px) minmax(0, 1fr);
    }

    .drv-indice-sidebar {
        grid-column: 1;
        grid-row: 1 / span 2;
    }

    .drv-indice-resumen {
        grid-column: 2;
        grid-row: 1;
        position: relative;
        top: auto;
        max-height: none;
        overflow: visible;
    }

    .drv-contenido-columna {
        grid-column: 2;
        grid-row: 2;
    }
}

@media (max-width: 991px) {
    .drv-contenido-wrapper {
        grid-template-columns: 1fr;
        gap: 20px;
    }

    .drv-indice-sidebar {
        position: relative;
        top: 0;
        order: 1;
        max-height: 300px;
    }

    .drv-indice-resumen {
        position: relative;
        top: 0;
        order: 2;
        max-height: none;
        overflow: visible;
    }

    .drv-contenido-columna {
        order: 3;
    }
}

@media (max-width: 767px) {

    .drv-indice-sidebar,
    .drv-indice-resumen {
        display: none;
    }

    .drv-contenido-wrapper {
        padding: 24px 0;
    }
}

/* ─── Responsive general ─── */
@media (max-width: 480px) {
    .drv-hero-content {
        padding: 60px 16px 20px;
    }

    .drv-hero-btn-pos {
        left: 16px;
        top: 16px;
    }

    .drv-resumen-container {
        margin: 16px;
        padding: 16px;
    }

    .drv-contenido-container {
        padding: 0 16px 16px;
    }

    .drv-meta-extra {
        padding: 16px 16px 0;
    }
}
</style>
