<template>
  <div class="tarjeta-movil" @click="$emit('ver', item.id)">
    <div class="tarjeta-header">
      <h3 class="tarjeta-titulo">{{ item.actualizacion_titulo }}</h3>
      <span :class="['estado-badge-movil', mapearClaseEstado(item.actualizacion_estado)]">
        {{ item.actualizacion_estado }}
      </span>
    </div>

    <div class="tarjeta-info">
      <div class="info-row">
        <i class="bi bi-tag"></i>
        <span class="info-label">Versión:</span>
        <span class="info-value">v{{ item.actualizacion_version }}</span>
      </div>

      <div class="info-row">
        <i class="bi bi-folder"></i>
        <span class="info-label">Área:</span>
        <span class="info-value">{{ obtenerNombreArea(item) }}</span>
      </div>

      <div class="info-row">
        <i class="bi bi-grid"></i>
        <span class="info-label">Categorías:</span>
        <div class="categorias-movil">
          <span
            v-for="(cat, idx) in obtenerListaCategorias(item)"
            :key="idx"
            class="cat-badge-movil"
            :style="{ backgroundColor: getCategoriaColor(cat) + '15', color: getCategoriaColor(cat) }"
          >
            {{ cat }}
          </span>
          <span v-if="obtenerListaCategorias(item).length === 0" class="cat-vacia">Sin categoría</span>
        </div>
      </div>

      <div class="info-row">
        <i class="bi bi-calendar"></i>
        <span class="info-label">Fecha:</span>
        <span class="info-value">
          {{ formatearFecha(item.actualizacion_fecha_publicacion || item.actualizacion_fecha_creacion) }}
        </span>
      </div>

      <div v-if="esVistaSupervision" class="info-row">
        <i class="bi bi-person"></i>
        <span class="info-label">Empleado:</span>
        <span class="info-value">{{ obtenerNombreAutor(item) }}</span>
      </div>
    </div>

    <div class="tarjeta-acciones" @click.stop>
      <button class="accion-movil ver" @click="$emit('ver', item.id)">
        <i class="bi bi-eye"></i>
        <span>Ver</span>
      </button>

      <template v-if="esVistaSupervision">
        <button v-if="item.actualizacion_estado !== 'revision'" class="accion-movil revision"
          @click="$emit('revisar', item)">
          <i class="bi bi-clipboard-check"></i>
          <span>Revisar</span>
        </button>
      </template>

      <template v-else>
        <button class="accion-movil editar" @click="$emit('editar', item)" data-bs-toggle="modal"
          data-bs-target="#modalEditarRegistro">
          <i :class="item.actualizacion_estado === 'revision' ? 'bi bi-send-check' : 'bi bi-pencil-square'"></i>
          <span>{{ item.actualizacion_estado === 'revision' ? 'Corregir' : 'Editar' }}</span>
        </button>

        <button v-if="item.actualizacion_estado !== 'inactivo'" class="accion-movil archivar"
          @click="$emit('archivar', item)">
          <i class="bi bi-archive"></i>
          <span>Archivar</span>
        </button>

        <button v-else class="accion-movil activar" @click="$emit('activar', item)">
          <i class="bi bi-box-arrow-in-up"></i>
          <span>Activar</span>
        </button>
      </template>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { Version } from '../../types/version'
import {
  formatearFecha,
  mapearClaseEstado,
  obtenerNombreArea,
  obtenerListaCategorias,
  getCategoriaColor,
  obtenerNombreAutor,
} from '../../utils/formatoRegistro' 

/**
 * Tarjeta de un registro para la vista móvil de "Mis registros" /
 * "Supervisión". Componente de presentación puro: no conoce filtros,
 * paginación ni cómo se cargan los datos — solo recibe un registro y
 * avisa al padre (List.vue) qué acción se pidió.
 */
defineProps<{
  item: Version
  esVistaSupervision: boolean
}>()

defineEmits<{
  (e: 'ver', id: number): void
  (e: 'editar', item: Version): void
  (e: 'archivar', item: Version): void
  (e: 'activar', item: Version): void
  (e: 'revisar', item: Version): void
}>()
</script>

<style scoped>
.tarjeta-movil {
  background: white;
  border-radius: 16px;
  padding: 14px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
  transition: all 0.2s ease;
  border: 1px solid #e2e8f0;
}

.tarjeta-movil:active {
  transform: scale(0.99);
}

.tarjeta-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 8px;
  margin-bottom: 12px;
  padding-bottom: 10px;
  border-bottom: 1px solid #f1f5f9;
}

.tarjeta-titulo {
  font-size: 0.95rem;
  font-weight: 700;
  color: #1e293b;
  margin: 0;
  line-height: 1.3;
  flex: 1;
  overflow: hidden;
  text-overflow: ellipsis;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
}

.estado-badge-movil {
  display: inline-block;
  padding: 3px 8px;
  border-radius: 20px;
  font-size: 0.65rem;
  font-weight: 600;
  text-transform: capitalize;
  white-space: nowrap;
}

.tarjeta-info {
  display: flex;
  flex-direction: column;
  gap: 8px;
  margin-bottom: 12px;
}

.info-row {
  display: flex;
  align-items: flex-start;
  gap: 6px;
  font-size: 0.75rem;
}

.info-row i {
  color: var(--primary);
  font-size: 0.7rem;
  margin-top: 2px;
  min-width: 16px;
}

.info-label {
  color: #64748b;
  font-weight: 500;
  min-width: 65px;
}

.info-value {
  color: #334155;
  flex: 1;
  word-break: break-word;
}

.categorias-movil {
  flex: 1;
  display: flex;
  flex-wrap: wrap;
  gap: 4px;
}

.cat-badge-movil {
  display: inline-block;
  padding: 2px 6px;
  border-radius: 12px;
  font-size: 0.6rem;
  font-weight: 500;
  white-space: nowrap;
}

.cat-vacia {
  font-size: 0.65rem;
  color: #94a3b8;
  font-style: italic;
}

.tarjeta-acciones {
  display: flex;
  gap: 8px;
  padding-top: 10px;
  border-top: 1px solid #f1f5f9;
}

.accion-movil {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 4px;
  padding: 8px 6px;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  background: white;
  font-size: 0.7rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
}

.accion-movil i {
  font-size: 0.7rem;
}

.accion-movil:active {
  transform: scale(0.97);
}

.accion-movil.ver {
  color: var(--primary);
  border-color: rgba(7, 126, 157, 0.3);
}

.accion-movil.revision {
  color: var(--warning);
  border-color: rgba(252, 187, 28, 0.3);
}

.accion-movil.editar {
  color: #10b981;
  border-color: rgba(16, 185, 129, 0.3);
}

.accion-movil.archivar {
  color: #ef4444;
  border-color: rgba(239, 68, 68, 0.3);
}

.accion-movil.activar {
  color: #3b82f6;
  border-color: rgba(59, 130, 246, 0.3);
}
</style>