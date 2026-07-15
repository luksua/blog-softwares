<template>
    <div class="page-hero" :class="{ 'page-hero--ancho-completo': anchoCompleto }">
        <div>
            <span class="eyebrow">{{ eyebrow }}</span>
            <h2>{{ titulo }}</h2>
            <p v-if="$slots.default || descripcion">
                <slot>{{ descripcion }}</slot>
            </p>
        </div>

        <div v-if="$slots.acciones" class="hero-actions">
            <slot name="acciones" />
        </div>
    </div>
</template>

<script setup lang="ts">
withDefaults(
    defineProps<{
        eyebrow: string
        titulo: string
        descripcion?: string
        /** Ocupa todo el ancho del contenedor padre en vez de su propio max-width.
         *  Úsalo cuando la página ya tiene su propio contenedor con max-width (p. ej. Dashboard). */
        anchoCompleto?: boolean
    }>(),
    {
        anchoCompleto: false,
    }
)
</script>

<style scoped>
/* ============================================================
   Hero de cabecera de página
   ------------------------------------------------------------
   Header reutilizado en "Mis registros", "Supervisión",
   "Dashboard", "Actualizaciones" y "Guardados". Antes vivía
   copiado en cada componente con pequeñas variaciones de
   markup (por eso no se veían iguales); ahora es un único
   componente con slots para descripción y acciones.
   ============================================================ */
.page-hero {
    display: flex;
    justify-content: space-between;
    gap: 24px;
    align-items: center;
    max-width: 1500px;
    margin: 0 auto 20px;
    padding: 28px;
    border-radius: 24px;
    background:
        radial-gradient(circle at top right, rgba(252, 187, 28, 0.24), transparent 32%),
        linear-gradient(135deg, #073b4c 0%, var(--secondary) 100%);
    color: white;
    box-shadow: 0 14px 32px rgba(2, 91, 125, 0.22);
}

.page-hero--ancho-completo {
    max-width: none;
}

.eyebrow {
    display: inline-flex;
    padding: 6px 12px;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.14);
    color: #fff7d6;
    font-size: 0.8rem;
    font-weight: 800;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.page-hero h2 {
    margin: 8px 0 6px;
    font-weight: 800;
    font-size: clamp(1.5rem, 3vw, 2.25rem);
}

.page-hero p {
    max-width: 760px;
    margin: 0;
    color: rgba(255, 255, 255, 0.86);
    font-size: 1rem;
}

.hero-actions {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-shrink: 0;
}

@media (max-width: 900px) {
    .page-hero {
        align-items: flex-start;
        flex-direction: column;
    }
}

@media (max-width: 768px) {
    .page-hero {
        padding: 22px;
    }

    .hero-actions {
        width: 100%;
        justify-content: space-between;
    }
}

@media (max-width: 480px) {
    .page-hero h2 {
        font-size: 1.45rem;
    }
}
</style>
