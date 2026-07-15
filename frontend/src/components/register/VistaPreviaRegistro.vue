<template>
  <div class="vista-previa-container">
    <div class="vista-previa-aviso">
      <i class="bi bi-eye-fill"></i>
      Así se vería este registro publicado. Los cambios no se han guardado todavía.
    </div>

    <DetalleRegistroVisual
      :titulo="titulo"
      :version="version"
      :resumen="resumen"
      :area-nombre="areaNombre"
      :categorias="categorias"
      :imagen-url="imagenUrl"
      :fecha-texto="fechaTexto"
      :contenido-html="contenidoHtml"
      layout="stack"
      contenido-vacio-texto="Todavía no hay contenido escrito en el editor."
    />
  </div>
</template>

<script setup lang="ts">
import DetalleRegistroVisual from '../shared/DetalleRegistroVisual.vue'

withDefaults(defineProps<{
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
</script>

<style scoped>
.vista-previa-container {
  display: flex;
  flex-direction: column;
  gap: 12px;
  height: 100%;
  min-height: 0;
  overflow-y: auto;
  overflow-x: hidden;
}

.vista-previa-aviso {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 14px;
  color: #075985;
  font-size: 0.82rem;
  font-weight: 600;
  background: #f0f9ff;
  border: 1px solid #bae6fd;
  border-radius: 10px;
  flex-shrink: 0;
}
</style>
