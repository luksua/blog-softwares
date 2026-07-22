```vue
<template>
    <div class="notifications-page">

        <!-- HEADER -->
        <header class="notifications-header">
            <button type="button" class="back-button" @click="volver">
                <i class="bi bi-arrow-left"></i>
            </button>

            <div class="header-title">
                <h1>Notificaciones</h1>
                <span>
                    {{ notificacionesNoLeidas }}
                    {{ notificacionesNoLeidas === 1 ? 'sin leer' : 'sin leer' }}
                </span>
            </div>

            <button v-if="notificacionesNoLeidas > 0" type="button" class="mark-all-button"
                title="Marcar todas como leídas" @click="marcarTodasComoLeidas">
                <i class="bi bi-check2-all"></i>
            </button>

            <div v-else class="header-spacer"></div>
        </header>

        <!-- CONTENIDO -->
        <main class="notifications-content">

            <!-- CARGANDO -->
            <div v-if="cargandoNotificaciones" class="notification-state">
                <div class="loading-spinner"></div>
                <span>Cargando notificaciones...</span>
            </div>

            <!-- SIN NOTIFICACIONES -->
            <div v-else-if="notificaciones.length === 0" class="notification-state empty-state">
                <div class="empty-icon">
                    <i class="bi bi-bell-slash"></i>
                </div>

                <h2>No tienes notificaciones</h2>

                <p>
                    Cuando recibas una nueva notificación aparecerá aquí.
                </p>
            </div>

            <!-- LISTA -->
            <div v-else class="notifications-list">

                <button v-for="notificacion in notificaciones" :key="notificacion.id" type="button" :class="[
                    'notification-card',
                    {
                        unread: !notificacion.leida_en
                    }
                ]" @click="abrirNotificacion(notificacion)">

                    <!-- ICONO -->
                    <div :class="[
                        'notification-icon',
                        {
                            unread: !notificacion.leida_en
                        }
                    ]">
                        <i class="bi bi-bell-fill"></i>
                    </div>

                    <!-- INFORMACIÓN -->
                    <div class="notification-info">

                        <div class="notification-top">
                            <h3>
                                {{ notificacion.titulo }}
                            </h3>

                            <span v-if="!notificacion.leida_en" class="unread-dot"></span>
                        </div>

                        <p>
                            {{ notificacion.mensaje }}
                        </p>

                        <span class="notification-date">
                            <i class="bi bi-clock"></i>
                            {{ formatearFechaNotificacion(notificacion.created_at) }}
                        </span>

                    </div>

                    <!-- FLECHA -->
                    <i class="bi bi-chevron-right notification-arrow"></i>

                </button>

                <!-- CARGAR MÁS -->
                <button v-if="hayMasNotificaciones" type="button" class="load-more-button"
                    :disabled="cargandoMasNotificaciones" @click="cargarMasNotificaciones">
                    <span v-if="cargandoMasNotificaciones">
                        <span class="small-spinner"></span>
                        Cargando...
                    </span>

                    <span v-else>
                        <i class="bi bi-arrow-down-circle"></i>
                        Ver más notificaciones
                    </span>
                </button>

            </div>

        </main>

    </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'

import {
    listarNotificaciones,
    marcarNotificacionLeida,
    marcarTodasNotificacionesLeidas,
    type BlogNotification,
} from '../../api/notificaciones'

const router = useRouter()

const PAGE_SIZE_NOTIFICACIONES = 10

const notificaciones = ref<BlogNotification[]>([])

const notificacionesNoLeidas = ref(0)

const cargandoNotificaciones = ref(false)

const cargandoMasNotificaciones = ref(false)

const paginaNotificaciones = ref(1)

const hayMasNotificaciones = ref(false)


/**
 * Cargar primera página de notificaciones
 */
const cargarNotificaciones = async () => {
    cargandoNotificaciones.value = true

    try {
        const response = await listarNotificaciones(
            PAGE_SIZE_NOTIFICACIONES,
            1
        )

        notificaciones.value = response?.data || []

        notificacionesNoLeidas.value = Number(
            response?.meta?.no_leidas ?? 0
        )

        paginaNotificaciones.value = Number(
            response?.meta?.current_page ?? 1
        )

        hayMasNotificaciones.value =
            Number(response?.meta?.current_page ?? 1) <
            Number(response?.meta?.last_page ?? 1)

    } catch (error) {
        console.error(
            'Error al cargar notificaciones:',
            error
        )
    } finally {
        cargandoNotificaciones.value = false
    }
}


/**
 * Cargar más notificaciones
 */
const cargarMasNotificaciones = async () => {
    if (
        cargandoMasNotificaciones.value ||
        !hayMasNotificaciones.value
    ) {
        return
    }

    cargandoMasNotificaciones.value = true

    try {
        const siguientePagina =
            paginaNotificaciones.value + 1

        const response = await listarNotificaciones(
            PAGE_SIZE_NOTIFICACIONES,
            siguientePagina
        )

        notificaciones.value = [
            ...notificaciones.value,
            ...(response?.data || [])
        ]

        paginaNotificaciones.value = Number(
            response?.meta?.current_page ??
            siguientePagina
        )

        hayMasNotificaciones.value =
            Number(
                response?.meta?.current_page ??
                siguientePagina
            ) <
            Number(
                response?.meta?.last_page ?? 1
            )

    } catch (error) {
        console.error(
            'Error al cargar más notificaciones:',
            error
        )
    } finally {
        cargandoMasNotificaciones.value = false
    }
}


/**
 * Abrir una notificación
 */
const abrirNotificacion = async (
    notificacion: BlogNotification
) => {
    try {

        // Marcar como leída
        if (!notificacion.leida_en) {

            await marcarNotificacionLeida(
                notificacion.id
            )

            notificacion.leida_en =
                new Date().toISOString()

            notificacionesNoLeidas.value =
                Math.max(
                    0,
                    notificacionesNoLeidas.value - 1
                )
        }


        // Si existe una ruta sugerida
        const rutaSugerida =
            notificacion.data?.ruta_sugerida

        if (
            rutaSugerida?.name &&
            router.hasRoute(rutaSugerida.name)
        ) {
            await router.push(rutaSugerida)
            return
        }


        // Si tiene una actualización relacionada
        if (notificacion.actualizacion_id) {

            await router.push({
                name: 'mis-registros-show',
                params: {
                    id: notificacion.actualizacion_id,
                },
            })

            return
        }

    } catch (error) {

        console.error(
            'Error al abrir notificación:',
            error
        )

    }
}


/**
 * Marcar todas como leídas
 */
const marcarTodasComoLeidas = async () => {

    if (notificacionesNoLeidas.value === 0) {
        return
    }

    try {

        await marcarTodasNotificacionesLeidas()

        notificaciones.value =
            notificaciones.value.map(
                (notificacion) => ({
                    ...notificacion,
                    leida_en:
                        notificacion.leida_en ||
                        new Date().toISOString(),
                })
            )

        notificacionesNoLeidas.value = 0

    } catch (error) {

        console.error(
            'Error al marcar notificaciones como leídas:',
            error
        )

    }
}


/**
 * Formatear fecha
 */
const formatearFechaNotificacion = (
    fecha?: string | null
) => {

    if (!fecha) {
        return ''
    }

    return new Intl.DateTimeFormat(
        'es-CO',
        {
            day: '2-digit',
            month: 'short',
            hour: '2-digit',
            minute: '2-digit',
        }
    ).format(
        new Date(fecha)
    )
}


/**
 * Volver
 */
const volver = () => {
    router.back()
}


onMounted(() => {
    cargarNotificaciones()
})
</script>

<style scoped>
.notifications-page {
    min-height: 100%;
    background: #f5f7fa;
}


/* =========================
   HEADER
========================= */

.notifications-header {
    display: flex;
    align-items: center;
    gap: 12px;

    padding: 16px;

    background: #ffffff;

    border-bottom: 1px solid #e5e7eb;

    position: sticky;
    top: 0;

    z-index: 10;
}


.back-button,
.mark-all-button {
    width: 42px;
    height: 42px;

    display: flex;
    align-items: center;
    justify-content: center;

    border: 1px solid #dbe3ee;

    border-radius: 12px;

    background: #ffffff;

    color: #025b7d;

    cursor: pointer;

    transition:
        background 0.2s ease,
        transform 0.2s ease;
}


.back-button:hover,
.mark-all-button:hover {
    background: rgba(7, 126, 157, 0.08);

    transform: translateY(-1px);
}


.back-button i,
.mark-all-button i {
    font-size: 1.2rem;
}


.header-title {
    flex: 1;
}


.header-title h1 {
    margin: 0;

    color: #0f172a;

    font-size: 1.15rem;

    font-weight: 800;
}


.header-title span {
    display: block;

    margin-top: 3px;

    color: #64748b;

    font-size: 0.75rem;
}


.header-spacer {
    width: 42px;
}


/* =========================
   CONTENIDO
========================= */

.notifications-content {
    padding: 16px;
}


/* =========================
   LISTA
========================= */

.notifications-list {
    display: flex;

    flex-direction: column;

    gap: 10px;
}


/* =========================
   TARJETA
========================= */

.notification-card {
    width: 100%;

    display: flex;

    align-items: flex-start;

    gap: 12px;

    padding: 15px;

    border: 1px solid #e2e8f0;

    border-radius: 16px;

    background: #ffffff;

    text-align: left;

    cursor: pointer;

    box-shadow:
        0 4px 12px rgba(15, 23, 42, 0.04);

    transition:
        transform 0.2s ease,
        box-shadow 0.2s ease,
        background 0.2s ease;
}


.notification-card:hover {
    transform: translateY(-1px);

    box-shadow:
        0 8px 20px rgba(15, 23, 42, 0.08);
}


.notification-card.unread {
    background: #eef9fc;

    border-color:
        rgba(7, 126, 157, 0.18);
}


/* =========================
   ICONO
========================= */

.notification-icon {
    flex-shrink: 0;

    width: 42px;
    height: 42px;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 12px;

    background: #e2e8f0;

    color: #64748b;
}


.notification-icon.unread {
    background: rgba(7, 126, 157, 0.12);

    color: #077e9d;
}


.notification-icon i {
    font-size: 1rem;
}


/* =========================
   INFORMACIÓN
========================= */

.notification-info {
    flex: 1;

    min-width: 0;
}


.notification-top {
    display: flex;

    align-items: flex-start;

    gap: 8px;
}


.notification-top h3 {
    flex: 1;

    margin: 0;

    color: #0f172a;

    font-size: 0.9rem;

    font-weight: 800;

    line-height: 1.3;
}


.notification-info p {
    margin: 5px 0 8px;

    color: #475569;

    font-size: 0.8rem;

    line-height: 1.4;
}


.notification-date {
    display: flex;

    align-items: center;

    gap: 5px;

    color: #94a3b8;

    font-size: 0.7rem;
}


.notification-date i {
    font-size: 0.7rem;
}


/* =========================
   PUNTO NO LEÍDO
========================= */

.unread-dot {
    flex-shrink: 0;

    width: 8px;
    height: 8px;

    margin-top: 4px;

    border-radius: 50%;

    background: #dc2626;
}


/* =========================
   FLECHA
========================= */

.notification-arrow {
    flex-shrink: 0;

    margin-top: 12px;

    color: #94a3b8;

    font-size: 0.85rem;
}


/* =========================
   CARGAR MÁS
========================= */

.load-more-button {
    width: 100%;

    display: flex;

    align-items: center;

    justify-content: center;

    gap: 8px;

    margin-top: 6px;

    padding: 13px 16px;

    border: 1px solid rgba(7, 126, 157, 0.25);

    border-radius: 12px;

    background: #ffffff;

    color: #077e9d;

    font-size: 0.85rem;

    font-weight: 700;

    cursor: pointer;
}


.load-more-button:hover {
    background:
        rgba(7, 126, 157, 0.06);
}


.load-more-button:disabled {
    opacity: 0.6;

    cursor: not-allowed;
}


/* =========================
   ESTADOS
========================= */

.notification-state {
    min-height: 60vh;

    display: flex;

    flex-direction: column;

    align-items: center;

    justify-content: center;

    text-align: center;

    color: #64748b;
}


.empty-icon {
    width: 70px;
    height: 70px;

    display: flex;

    align-items: center;

    justify-content: center;

    margin-bottom: 16px;

    border-radius: 50%;

    background:
        rgba(7, 126, 157, 0.08);

    color: #077e9d;
}


.empty-icon i {
    font-size: 1.8rem;
}


.empty-state h2 {
    margin: 0;

    color: #0f172a;

    font-size: 1.05rem;
}


.empty-state p {
    max-width: 280px;

    margin: 8px 0 0;

    font-size: 0.8rem;

    line-height: 1.5;
}


/* =========================
   SPINNERS
========================= */

.loading-spinner,
.small-spinner {
    border: 3px solid rgba(7, 126, 157, 0.15);

    border-top-color: #077e9d;

    border-radius: 50%;

    animation:
        spin 0.8s linear infinite;
}


.loading-spinner {
    width: 36px;
    height: 36px;

    margin-bottom: 12px;
}


.small-spinner {
    display: inline-block;

    width: 14px;
    height: 14px;

    border-width: 2px;
}


@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}


/* =========================
   RESPONSIVE
========================= */

@media (min-width: 769px) {
    .notifications-page {
        max-width: 700px;

        margin: 0 auto;
    }
}


@media (max-width: 360px) {

    .notifications-content {
        padding: 12px;
    }

    .notification-card {
        padding: 12px;

        gap: 9px;
    }

    .notification-icon {
        width: 38px;
        height: 38px;
    }

    .notification-top h3 {
        font-size: 0.82rem;
    }

    .notification-info p {
        font-size: 0.75rem;
    }

}
</style>
