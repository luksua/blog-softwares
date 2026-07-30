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
import '../../styles/notification.css'

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

const volver = () => {
    router.back()
}

onMounted(() => {
    cargarNotificaciones()
})
</script>
