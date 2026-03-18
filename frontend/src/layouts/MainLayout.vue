<template>
    <div>
        <!-- Navbar -->
        <nav class="navbar navbar-dark bg-dark px-3">
            <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarMenu"
                aria-controls="sidebarMenu">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="justify-content-start">
                <a class="navbar-brand" href="#">
                    <img src="../assets/Asotrauma.png" alt="Logo" width="140" class="d-inline-block align-text-top">
                </a>
            </div>
            <div class="justify-content-end">
                <button>login</button>
            </div>
            <!-- <span class="navbar-brand justify-content mb-0 h1">Mi App</span> -->
        </nav>

        <!-- Sidebar izquierdo -->
        <div class="offcanvas offcanvas-start" tabindex="-1" id="sidebarMenu" aria-labelledby="sidebarMenuLabel">
            <div class="offcanvas-header">
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>

            <div class="offcanvas-body">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link" href="#">Registrar</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#"></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">Configuración</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">Cerrar sesión</a>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Contenido -->
        <main class="container py-4">
            <h1>aaaa</h1>
        </main>
    </div>
</template>

<script setup lang="ts"></script>