<template>
    <Teleport to="body">
        <template v-if="mostrar">
            <!-- Overlay -->
            <div :class="['oc-overlay', { 'oc-overlay--open': abierto }]" @click="$emit('update:abierto', false)" />

            <!-- Botón flotante -->
            <button class="fab-revision" :class="{ 'fab-revision--pulsing': !abierto }"
                @click="$emit('update:abierto', true)" aria-label="Ver revisiones pendientes"
                title="Revisiones pendientes">
                <i class="bi bi-clipboard-check"></i>
                <span class="fab-badge">{{ notificaciones.length }}</span>
            </button>

            <!-- Panel offcanvas -->
            <aside :class="['oc-revision', { 'oc-revision--open': abierto }]" role="dialog" aria-modal="true"
                aria-label="Revisiones pendientes">
                <div class="oc-header">
                    <div class="oc-header-left">
                        <i class="bi bi-exclamation-circle-fill oc-header-icon"></i>
                        <span class="oc-title">Revisión pendiente</span>
                        <span class="oc-count">{{ notificaciones.length }}</span>
                    </div>
                    <button class="oc-close" @click="$emit('update:abierto', false)"
                        aria-label="Cerrar panel de revisiones">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>

                <div class="oc-body">
                    <div v-for="notificacion in notificaciones" :key="notificacion.id" class="oc-item">
                        <div class="oc-item-inner">
                            <div class="oc-item-icon-wrap">
                                <i class="bi bi-exclamation-triangle-fill oc-item-icon"></i>
                            </div>
                            <div class="oc-item-content">
                                <p class="oc-item-msg">{{ notificacion.mensaje }}</p>
                                <button v-if="notificacion.actualizacion_id" type="button" class="oc-item-btn"
                                    @click="$emit('corregir', notificacion)">
                                    <i class="bi bi-pencil-square"></i>
                                    Corregir registro
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </aside>
        </template>
    </Teleport>
</template>

<script setup lang="ts">
import { onBeforeUnmount, onMounted } from 'vue'
import type { BlogNotification } from '../../api/notificaciones'

/**
 * Panel de "revisiones pendientes" (botón flotante + panel lateral vía
 * Teleport). Antes vivía embebido dentro de List.vue junto con toda su
 * lógica de filtros/tabla; ahora es un componente de presentación:
 * recibe la lista de notificaciones y el estado abierto/cerrado, y le
 * avisa al padre cuándo cerrarlo o cuándo el usuario quiere corregir
 * un registro. El padre sigue siendo dueño de cargar las notificaciones
 * y de abrir el modal de edición en modo corrección.
 */
defineProps<{
    /** Si el conjunto completo (botón + panel) debe existir en el DOM. */
    mostrar: boolean
    /** Si el panel está actualmente desplegado. */
    abierto: boolean
    notificaciones: BlogNotification[]
}>()

const emit = defineEmits<{
    (e: 'update:abierto', valor: boolean): void
    (e: 'corregir', notificacion: BlogNotification): void
}>()

// Cierra el panel con Escape (solo mientras este componente está montado).
const onKeydown = (event: KeyboardEvent) => {
    if (event.key === 'Escape') {
        emit('update:abierto', false)
    }
}

onMounted(() => document.addEventListener('keydown', onKeydown))
onBeforeUnmount(() => document.removeEventListener('keydown', onKeydown))
</script>

<!-- Sin "scoped": el contenido se renderiza vía Teleport a <body>, fuera
     del árbol de este componente, así que los estilos scoped no le
     llegarían (ver nota en el resto de componentes de este proyecto). -->
<style>
.oc-overlay {
    position: fixed;
    inset: 0;
    background: transparent;
    z-index: 1040;
    pointer-events: none;
    transition: background 0.25s ease;
}

.oc-overlay--open {
    background: rgba(0, 0, 0, 0.35);
    pointer-events: all;
}

.fab-revision {
    position: fixed;
    bottom: 28px;
    right: 28px;
    z-index: 1050;
    width: 54px;
    height: 54px;
    border-radius: 50%;
    background: linear-gradient(135deg, #077E9D 0%, #025B7D 100%);
    border: none;
    cursor: pointer;
    color: white;
    font-size: 1.3rem;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 20px rgba(7, 126, 157, 0.4);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    outline: none;
}

.fab-revision:hover {
    transform: translateY(-3px) scale(1.05);
    box-shadow: 0 8px 28px rgba(7, 126, 157, 0.5);
}

.fab-revision:active {
    transform: scale(0.97);
}

.fab-revision .bi {
    line-height: 1;
    display: block;
}

.fab-badge {
    position: absolute;
    top: -5px;
    right: -5px;
    background: #FCBB1C;
    color: #7a5a00;
    font-size: 10px;
    font-weight: 700;
    min-width: 20px;
    height: 20px;
    border-radius: 10px;
    padding: 0 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 2px solid white;
    line-height: 1;
}

.fab-revision--pulsing {
    animation: fab-pulse 2.5s infinite;
}

@keyframes fab-pulse {

    0%,
    100% {
        box-shadow: 0 4px 20px rgba(7, 126, 157, 0.4);
    }

    50% {
        box-shadow: 0 4px 20px rgba(7, 126, 157, 0.4), 0 0 0 8px rgba(7, 126, 157, 0.12);
    }
}

.oc-revision {
    position: fixed;
    top: 0;
    right: 0;
    width: 390px;
    max-width: calc(100vw - 40px);
    height: 100%;
    background: #ffffff;
    z-index: 1055;
    transform: translateX(100%);
    transition: transform 0.32s cubic-bezier(0.4, 0, 0.2, 1);
    display: flex;
    flex-direction: column;
    box-shadow: -6px 0 32px rgba(0, 0, 0, 0.14);
}

.oc-revision--open {
    transform: translateX(0);
}

.oc-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 15px 18px;
    border-bottom: 1px solid #e1e7f0;
    background: #fffef7;
    border-left: 4px solid #FCBB1C;
    flex-shrink: 0;
}

.oc-header-left {
    display: flex;
    align-items: center;
    gap: 8px;
}

.oc-header-icon {
    color: #FCBB1C;
    font-size: 1rem;
    line-height: 1;
}

.oc-title {
    font-size: 0.88rem;
    font-weight: 700;
    color: #856404;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.oc-count {
    background: #FCBB1C;
    color: #7a5a00;
    font-size: 0.68rem;
    font-weight: 700;
    padding: 2px 8px;
    border-radius: 10px;
    line-height: 1.4;
}

.oc-close {
    width: 32px;
    height: 32px;
    border: 1px solid #e1e7f0;
    background: #f9fafb;
    border-radius: 8px;
    cursor: pointer;
    color: #6b7280;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.85rem;
    transition: background 0.15s, color 0.15s;
    flex-shrink: 0;
}

.oc-close:hover {
    background: #f3f4f6;
    color: #374151;
}

.oc-body {
    flex: 1;
    overflow-y: auto;
    padding: 14px 16px;
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.oc-body::-webkit-scrollbar {
    width: 4px;
}

.oc-body::-webkit-scrollbar-track {
    background: #f9fafb;
}

.oc-body::-webkit-scrollbar-thumb {
    background: #d1d5db;
    border-radius: 4px;
}

.oc-item {
    background: #ffffff;
    border-radius: 12px;
    border: 1px solid #f0e0b0;
    border-left: 3px solid #FCBB1C;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.oc-item:hover {
    transform: translateX(4px);
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.07);
}

.oc-item-inner {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    padding: 12px 14px;
}

.oc-item-icon-wrap {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    background: #fef8e3;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    margin-top: 1px;
}

.oc-item-icon {
    color: #f59e0b;
    font-size: 0.9rem;
    line-height: 1;
}

.oc-item-content {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 7px;
}

.oc-item-msg {
    margin: 0;
    font-size: 0.88rem;
    color: #374151;
    line-height: 1.5;
    word-break: break-word;
}

.oc-item-btn {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: 0.8rem;
    font-weight: 600;
    color: #077E9D;
    background: none;
    border: none;
    cursor: pointer;
    padding: 0;
    transition: color 0.15s;
    text-align: left;
}

.contenedor-lista {
  max-width: 1400px;
  margin: 0 auto;
  padding: 0 16px;
}


.oc-item-btn:hover {
    color: #025B7D;
    text-decoration: underline;
}

.oc-item-btn .bi {
    font-size: 0.82rem;
    line-height: 1;
}

@media (max-width: 480px) {
    .oc-revision {
        width: 100%;
        max-width: 100%;
    }

    .fab-revision {
        bottom: 20px;
        right: 20px;
    }
}
</style>