<template>
    <div class="layout">

        <!-- SIDEBAR -->
        <aside :class="['sidebar', { expanded: isExpanded }]">
            <div class="toggle" @click="toggleSidebar">
                ☰
            </div>

            <ul>
                <li>
                    <i>🏠</i>
                    <span v-if="isExpanded">Inicio</span>
                </li>

                <li>
                    <i>📦</i>
                    <span v-if="isExpanded">Versiones</span>
                </li>

                <li>
                    <i>➕</i>
                    <span v-if="isExpanded">Nueva</span>
                </li>
            </ul>
        </aside>

        <!-- MAIN -->
        <div class="main">

            <!-- NAVBAR -->
            <header class="navbar navbar-image">
                <img src="../assets/Asotrauma3.png" alt="logo" class="logo" />
                <button class="btn-primary">
                    <span class="icon">→</span>
                    Iniciar Sesión
                </button>
            </header>

            <!-- CONTENIDO -->
            <main class="content">
                <router-view />
            </main>

            <!-- FOOTER -->
            <footer class="footer">
                © 2026 - Sistema de Versiones
            </footer>

        </div>

    </div>
</template>

<script setup lang="ts">
import { ref } from "vue";

const isExpanded = ref(false);

const toggleSidebar = () => {
    isExpanded.value = !isExpanded.value;
};
</script>