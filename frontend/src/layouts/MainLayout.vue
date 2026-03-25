<template>
    <div class="layout">

        <!-- SIDEBAR -->
        <aside :class="['sidebar', { expanded: isExpanded }]">
            <div class="toggle" @click="toggleSidebar">
                ☰
            </div>

            <ul>
                <router-link to="/" custom v-slot="{ navigate }">
                    <li @click="navigate" style="cursor: pointer;">
                        <i>🏠</i>
                        <span v-if="isExpanded">Inicio</span>
                    </li>
                </router-link>

                <router-link to="/nuevo" custom v-slot="{ navigate }">
                    <li @click="navigate" style="cursor: pointer;">
                        <i>➕</i>
                        <span v-if="isExpanded">Nueva Versión</span>
                    </li>
                </router-link>
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