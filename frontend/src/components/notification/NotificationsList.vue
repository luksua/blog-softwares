<template>
  <section class="notification-panel" @click.stop>
    <header class="notification-panel-header">
      <div>
        <strong>Notificaciones</strong>
        <small>{{ noLeidas }} sin leer</small>
      </div>

      <button
        v-if="noLeidas > 0"
        class="notification-link-button"
        type="button"
        @click="$emit('marcar-todas')"
      >
        Marcar leídas
      </button>
    </header>

    <div v-if="cargando" class="notification-empty">
      Cargando...
    </div>

    <div v-else-if="notificaciones.length === 0" class="notification-empty">
      No tienes notificaciones.
    </div>

    <template v-else>
      <button
        v-for="notificacion in notificaciones"
        :key="notificacion.id"
        :class="['notification-item', { unread: !notificacion.leida_en }]"
        type="button"
        @click="$emit('abrir', notificacion)"
      >
        <span class="notification-title">
          {{ notificacion.titulo }}
        </span>

        <span v-if="notificacion.mensaje" class="notification-message">
          {{ notificacion.mensaje }}
        </span>

        <small>
          {{ formatearFecha(notificacion.created_at) }}
        </small>
      </button>
    </template>
  </section>
</template>

<script setup lang="ts">
import type { BlogNotification } from '../../api/notificaciones'

defineProps<{
  notificaciones: BlogNotification[]
  cargando: boolean
  noLeidas: number
}>()

defineEmits<{
  abrir: [notificacion: BlogNotification]
  'marcar-todas': []
}>()

const formatearFecha = (fecha?: string | null) => {
  if (!fecha) return ''

  return new Intl.DateTimeFormat('es-CO', {
    day: '2-digit',
    month: 'short',
    hour: '2-digit',
    minute: '2-digit',
  }).format(new Date(fecha))
}
</script>

<style scoped>
.notification-panel {
  position: absolute;
  top: calc(100% + 12px);
  right: 0;
  z-index: 120;
  width: min(360px, calc(100vw - 32px));
  max-height: 440px;
  overflow-y: auto;
  border: 1px solid #e2e8f0;
  border-radius: 18px;
  background: #ffffff;
  box-shadow: 0 24px 60px rgba(15, 23, 42, 0.18);
}

.notification-panel-header {
  position: sticky;
  top: 0;
  z-index: 1;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 14px 16px;
  border-bottom: 1px solid #eef2f7;
  background: #ffffff;
}

.notification-panel-header strong,
.notification-panel-header small {
  display: block;
}

.notification-panel-header strong {
  color: #0f172a;
  font-size: 0.98rem;
}

.notification-panel-header small {
  margin-top: 2px;
  color: #64748b;
  font-size: 0.78rem;
}

.notification-link-button {
  border: none;
  background: transparent;
  color: #077e9d;
  font-size: 0.78rem;
  font-weight: 800;
  cursor: pointer;
  white-space: nowrap;
}

.notification-empty {
  padding: 22px 16px;
  color: #64748b;
  text-align: center;
  font-size: 0.9rem;
}

.notification-item {
  display: block;
  width: 100%;
  padding: 14px 16px;
  border: none;
  border-bottom: 1px solid #f1f5f9;
  background: #ffffff;
  text-align: left;
  cursor: pointer;
  transition: background 0.2s ease;
}

.notification-item:hover {
  background: #f8fafc;
}

.notification-item.unread {
  background: #eef9fc;
}

.notification-item.unread:hover {
  background: #e3f4f8;
}

.notification-title {
  display: block;
  color: #0f172a;
  font-size: 0.9rem;
  font-weight: 800;
  line-height: 1.25;
}

.notification-message {
  display: block;
  margin-top: 4px;
  color: #475569;
  font-size: 0.82rem;
  line-height: 1.35;
}

.notification-item small {
  display: block;
  margin-top: 7px;
  color: #94a3b8;
  font-size: 0.72rem;
}
</style>