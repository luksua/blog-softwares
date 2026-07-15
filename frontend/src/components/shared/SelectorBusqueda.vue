<template>
    <div ref="wrapperRef" class="categoria-select-wrapper" :class="{ open: abierto }">
        <div class="categoria-select-trigger" @click="abierto = !abierto">
            <div class="select-placeholder" v-if="!opcionSeleccionada">
                <i class="bi" :class="iconoPlaceholder"></i>
                <span>{{ placeholder }}</span>
            </div>

            <div class="select-selected" v-else>
                <span class="selected-tag-single">
                    <i v-if="opcionSeleccionada.icono" class="bi" :class="opcionSeleccionada.icono"></i>
                    {{ opcionSeleccionada.nombre }}
                </span>
            </div>

            <i class="bi" :class="abierto ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
        </div>

        <div v-if="abierto" class="categoria-select-dropdown">
            <div class="dropdown-search" v-if="opciones.length > umbralBusqueda">
                <i class="bi bi-search"></i>
                <input type="text" v-model="busqueda" :placeholder="textoBusqueda" @click.stop />
            </div>

            <div class="dropdown-options">
                <button v-if="mostrarOpcionTodas" type="button" class="dropdown-option"
                    :class="{ selected: !hayValorSeleccionado }" @click="seleccionar('')">
                    <span class="option-name">
                        <i v-if="iconoPlaceholder" class="bi" :class="iconoPlaceholder" style="margin-right: 8px;"></i>
                        {{ textoOpcionTodas }}
                    </span>
                    <span class="option-check">
                        <i v-if="!hayValorSeleccionado" class="bi bi-check-lg"></i>
                    </span>
                </button>

                <button type="button" v-for="opcion in opcionesFiltradas" :key="opcion.id" class="dropdown-option"
                    :class="{ selected: esSeleccionada(opcion.id) }" @click="seleccionar(opcion.id)">
                    <span class="option-name">
                        <i v-if="opcion.icono" class="bi" :class="opcion.icono" style="margin-right: 8px;"></i>
                        <span v-html="resaltarCoincidencia(opcion.nombre)"></span>
                    </span>
                    <span class="option-check">
                        <i v-if="esSeleccionada(opcion.id)" class="bi bi-check-lg"></i>
                    </span>
                </button>

                <div v-if="opcionesFiltradas.length === 0" class="dropdown-empty">
                    {{ textoVacio }}
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'

export interface OpcionSelector {
    id: number | string
    nombre: string
    /** Clase de bootstrap-icons opcional (p. ej. "bi-tag") */
    icono?: string
}

/**
 * Selector desplegable con búsqueda, usado para filtros de Área y
 * Categoría. Antes esta misma pieza (trigger + dropdown + búsqueda +
 * opción "Todas") estaba copiada 4 veces dentro de List.vue (versión
 * móvil y desktop, para área y categoría), cada una con su propio CSS
 * repetido. Ahora vive una sola vez acá, con su propio v-model.
 */
const props = withDefaults(defineProps<{
    /** ID seleccionado. Usa '' o null para "sin selección". */
    modelValue: number | string | null
    opciones: OpcionSelector[]
    placeholder: string
    iconoPlaceholder?: string
    textoBusqueda?: string
    textoVacio?: string
    /** Muestra un primer item para volver a "sin selección" (p. ej. "Todas las categorías"). */
    mostrarOpcionTodas?: boolean
    textoOpcionTodas?: string
    /** A partir de cuántas opciones se muestra el buscador. */
    umbralBusqueda?: number
}>(), {
    iconoPlaceholder: 'bi-tag',
    textoBusqueda: 'Buscar...',
    textoVacio: 'No se encontraron opciones',
    mostrarOpcionTodas: false,
    textoOpcionTodas: 'Todas',
    umbralBusqueda: 5,
})

const emit = defineEmits<{
    (e: 'update:modelValue', valor: number | string | null): void
}>()

const abierto = ref(false)
const busqueda = ref('')
const wrapperRef = ref<HTMLElement | null>(null)

const hayValorSeleccionado = computed(() => {
    return props.modelValue !== null && props.modelValue !== ''
})

const esSeleccionada = (id: number | string) => {
    if (!hayValorSeleccionado.value) return false
    return String(props.modelValue) === String(id)
}

const opcionSeleccionada = computed(() => {
    if (!hayValorSeleccionado.value) return null
    return props.opciones.find((o) => esSeleccionada(o.id)) || null
})

/**
 * Quita tildes/diacríticos para comparar sin acentos (p. ej. "programacion"
 * debe encontrar y resaltar "Programación"). Al pasar por NFD, cada letra
 * acentuada (á, é, í, ó, ú, ñ...) se separa en su letra base + una marca
 * combinante aparte, así que al remover esas marcas el resultado conserva
 * la misma cantidad de caracteres que el texto original, letra por letra.
 */
const normalizarTexto = (texto: string) =>
    texto.normalize('NFD').replace(/[\u0300-\u036f]/g, '')

const opcionesFiltradas = computed(() => {
    if (!busqueda.value.trim()) return props.opciones

    const texto = normalizarTexto(busqueda.value.toLowerCase().trim())
    return props.opciones.filter((o) => normalizarTexto(o.nombre.toLowerCase()).includes(texto))
})

const escaparHtml = (texto: string) => {
    return texto.replace(/[&<>"']/g, (caracter) => (
        { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[caracter] as string
    ))
}

/** Envuelve en <mark> la parte del nombre que coincide con la búsqueda actual, ignorando acentos. */
const resaltarCoincidencia = (nombre: string) => {
    const query = busqueda.value.trim()
    if (!query) return escaparHtml(nombre)

    const nombreNormalizado = normalizarTexto(nombre.toLowerCase())
    const queryNormalizado = normalizarTexto(query.toLowerCase())

    // Salvaguarda: si por algún carácter especial la normalización no conserva
    // la longitud original, evitamos desalinear los índices y no resaltamos.
    if (nombreNormalizado.length !== nombre.length || !queryNormalizado) {
        return escaparHtml(nombre)
    }

    let resultado = ''
    let cursor = 0
    let desde = 0

    while (true) {
        const indice = nombreNormalizado.indexOf(queryNormalizado, desde)
        if (indice === -1) break

        resultado += escaparHtml(nombre.slice(cursor, indice))
        resultado += `<mark class="coincidencia">${escaparHtml(nombre.slice(indice, indice + queryNormalizado.length))}</mark>`

        cursor = indice + queryNormalizado.length
        desde = cursor
    }

    resultado += escaparHtml(nombre.slice(cursor))
    return resultado
}

const seleccionar = (id: number | string) => {
    emit('update:modelValue', id)
    abierto.value = false
    busqueda.value = ''
}

const cerrarAlClickFuera = (event: MouseEvent) => {
    if (wrapperRef.value && !wrapperRef.value.contains(event.target as Node)) {
        abierto.value = false
        busqueda.value = ''
    }
}

onMounted(() => document.addEventListener('click', cerrarAlClickFuera))
onBeforeUnmount(() => document.removeEventListener('click', cerrarAlClickFuera))
</script>

<style scoped>
.categoria-select-wrapper {
    position: relative;
    margin-bottom: 0;
}

.categoria-select-trigger {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 12px;
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s ease;
    min-height: 42px;
}

.categoria-select-trigger:hover {
    border-color: var(--primary);
}

.categoria-select-wrapper.open .categoria-select-trigger {
    border-color: var(--primary);
    box-shadow: 0 0 0 2px rgba(7, 126, 157, 0.1);
}

.select-placeholder {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #94a3b8;
    font-size: 0.85rem;
}

.select-placeholder i {
    font-size: 0.9rem;
}

.select-selected {
    flex: 1;
}

.selected-tag-single {
    background: rgba(7, 126, 157, 0.1);
    color: var(--primary);
    padding: 2px 10px;
    border-radius: 12px;
    font-size: 0.8rem;
    font-weight: 500;
}

.categoria-select-trigger .bi-chevron-down,
.categoria-select-trigger .bi-chevron-up {
    color: #94a3b8;
    transition: transform 0.2s ease;
}

.categoria-select-dropdown {
    position: absolute;
    top: calc(100% + 4px);
    left: 0;
    right: 0;
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    z-index: 1000;
    overflow: hidden;
}

.dropdown-search {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 12px;
    border-bottom: 1px solid #e2e8f0;
}

.dropdown-search i {
    color: #94a3b8;
    font-size: 0.85rem;
}

.dropdown-search input {
    flex: 1;
    border: none;
    outline: none;
    font-size: 0.85rem;
    padding: 6px 0;
}

.dropdown-search input::placeholder {
    color: #cbd5e1;
}

.dropdown-options {
    max-height: 200px;
    overflow-y: auto;
}

.dropdown-option {
    display: flex;
    align-items: center;
    justify-content: space-between;
    width: 100%;
    padding: 10px 12px;
    background: transparent;
    border: none;
    cursor: pointer;
    transition: all 0.15s ease;
    text-align: left;
}

.dropdown-option:hover:not(.disabled) {
    background: #f8fafc;
}

.dropdown-option.selected {
    background: rgba(7, 126, 157, 0.08);
}

.option-name {
    font-size: 0.85rem;
    color: #334155;
}

.option-name .coincidencia {
    background: rgba(7, 126, 157, 0.18);
    color: var(--primary);
    border-radius: 3px;
    padding: 0 1px;
    font-weight: 700;
}

.dropdown-option.selected .option-name {
    color: var(--primary);
    font-weight: 500;
}

.option-check i {
    color: var(--primary);
    font-size: 0.9rem;
}

.dropdown-empty {
    padding: 20px;
    text-align: center;
    color: #94a3b8;
    font-size: 0.8rem;
}
</style>