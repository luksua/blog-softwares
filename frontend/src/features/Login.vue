<!-- src/views/Login.vue -->
<template>
    <div class="login-pantalla d-flex justify-content-center align-items-center vh-100 bg-light">
        <div class="card shadow-lg p-4 border-0" style="width: 100%; max-width: 420px; border-radius: 16px;">

            <div class="text-center mb-4">
                <div class="bg-primary text-white d-inline-flex justify-content-center align-items-center rounded-circle mb-3"
                    style="width: 64px; height: 64px; font-size: 24px;">
                    <i class="bi bi-person-lock"></i>
                </div>
                <h3 class="fw-bold text-dark mb-1">Iniciar Sesión</h3>
                <p class="text-muted small">Acceso al sistema de actualizaciones</p>
            </div>

            <div class="alert alert-info border-0 small mb-4" style="border-radius: 12px;">
                <i class="bi bi-info-circle-fill me-2"></i>
                <strong>Simulador:</strong> Elige cómo quieres ingresar.
            </div>

            <form @submit.prevent="simularLogin">
                <!-- Selector de Rol para pruebas -->
                <div class="mb-3">
                    <label class="form-label fw-semibold text-secondary">Simular entrada como:</label>
                    <select v-model="form.rolSimulado" class="form-select form-select-lg bg-light" required>
                        <option value="empleado">Usuario / Empleado</option>
                        <option value="admin">Administrador</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold text-secondary">Correo Electrónico</label>
                    <input v-model="form.email" type="email" class="form-control form-control-lg bg-light" required />
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold text-secondary">Contraseña</label>
                    <input v-model="form.password" type="password" class="form-control form-control-lg bg-light"
                        placeholder="••••••••" required />
                </div>

                <button type="submit" class="btn btn-primary btn-lg w-100 fw-bold shadow-sm" :disabled="cargando">
                    <span v-if="cargando" class="spinner-border spinner-border-sm me-2" role="status"
                        aria-hidden="true"></span>
                    {{ cargando ? 'Verificando credenciales...' : 'Ingresar al sistema' }}
                </button>
            </form>

        </div>
    </div>
</template>

<script setup lang="ts">
import { reactive, ref, watch } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()
const cargando = ref(false)

const form = reactive({
    rolSimulado: 'empleado', // Por defecto entra como empleado
    email: 'empleado@empresa.com',
    password: '123'
})

// Cambia el correo automático solo para darte feedback visual al probar
watch(() => form.rolSimulado, (nuevoRol) => {
    form.email = nuevoRol === 'admin' ? 'admin@empresa.com' : 'empleado@empresa.com'
})

const simularLogin = () => {
    cargando.value = true

    setTimeout(() => {
        // 1. Creamos el token (igual para todos)
        const fakeToken = 'simulated-jwt-token-123456789'

        // 2. Creamos el usuario según el rol seleccionado
        const fakeUser = {
            id: form.rolSimulado === 'admin' ? 1 : 105,
            nombre: form.rolSimulado === 'admin' ? 'Administrador' : 'Juan Empleado',
            email: form.email,
            rol: form.rolSimulado
        }

        localStorage.setItem('auth_token', fakeToken)
        localStorage.setItem('user_data', JSON.stringify(fakeUser))

        // 3. REDIRECCIÓN INTELIGENTE:
        // Si es admin, va a su panel. Si es empleado, va al inicio (donde ve las actualizaciones y las puede guardar)
        if (fakeUser.rol === 'admin') {
            router.push({ name: 'inicioAdmin' })
        } else {
            router.push({ name: 'inicio' })
        }

        cargando.value = false
    }, 1000)
}
</script>

<style scoped>
.login-pantalla {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    z-index: 9999;
}

.form-control:focus,
.form-select:focus {
    border-color: var(--primary, #077E9D);
    box-shadow: 0 0 0 0.25rem rgba(7, 126, 157, 0.25);
}
</style>

<!-- V2 -->
 <!-- src/features/Login.vue -->
<!-- <template>
    <div class="login-pantalla d-flex justify-content-center align-items-center vh-100 bg-light">
        <div class="card shadow-lg p-4 border-0" style="width: 100%; max-width: 420px; border-radius: 16px;">

            <div class="text-center mb-4">
                <div class="bg-primary text-white d-inline-flex justify-content-center align-items-center rounded-circle mb-3"
                    style="width: 64px; height: 64px; font-size: 24px;">
                    <i class="bi bi-person-lock"></i>
                </div>

                <h3 class="fw-bold text-dark mb-1">Iniciar Sesión</h3>
                <p class="text-muted small">Acceso al sistema de actualizaciones</p>
            </div>

            <div class="alert alert-info border-0 small mb-4" style="border-radius: 12px;">
                <i class="bi bi-info-circle-fill me-2"></i>
                <strong>Simulador:</strong> Selecciona el usuario que Laravel debe buscar en la BD.
            </div>

            <form @submit.prevent="simularLogin">
                <div class="mb-3">
                    <label class="form-label fw-semibold text-secondary">
                        Simular entrada como:
                    </label>

                    <select v-model="form.tipoUsuario" class="form-select form-select-lg bg-light" required>
                        <option value="empleado">Usuario / Empleado</option>
                        <option value="admin">Administrador</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold text-secondary">
                        Usuario de la BD
                    </label>

                    <input
                        v-model="form.usuario"
                        type="text"
                        class="form-control form-control-lg bg-light"
                        required
                    />
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold text-secondary">
                        Piso
                    </label>

                    <input
                        v-model="form.piso"
                        type="text"
                        class="form-control form-control-lg bg-light"
                        required
                    />
                </div>

                <button type="submit" class="btn btn-primary btn-lg w-100 fw-bold shadow-sm" :disabled="cargando">
                    <span
                        v-if="cargando"
                        class="spinner-border spinner-border-sm me-2"
                        role="status"
                        aria-hidden="true"
                    ></span>

                    {{ cargando ? 'Iniciando sesión...' : 'Ingresar al sistema' }}
                </button>
            </form>

        </div>
    </div>
</template>

<script setup lang="ts">
import { reactive, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import api from '../api/api'

const router = useRouter()
const cargando = ref(false)

const form = reactive({
    tipoUsuario: 'empleado',
    usuario: 'BMANDRES',
    piso: 'q',
})

/**
 * Ajusta estos usuarios de prueba según existan en tu BD.
 */
watch(() => form.tipoUsuario, (nuevoTipo) => {
    if (nuevoTipo === 'admin') {
        form.usuario = 'ADMIN'
    } else {
        form.usuario = 'BMANDRES'
    }
})

const simularLogin = async () => {
    cargando.value = true

    try {
        await api.get('/sanctum/csrf-cookie')

        const response = await api.post('/api/simulador-login', {
            user: form.usuario,
            piso: form.piso,
        })

        const user = response.data.user

        localStorage.setItem('user_data', JSON.stringify(user))
        localStorage.setItem('auth_token', 'laravel-session')

        const esAdmin =
            user.grupo === 'admin' ||
            user.grupo === 'ADMIN' ||
            user.permisos?.includes('admin') ||
            user.permisos?.includes('actualizaciones.admin')

        if (esAdmin) {
            router.push({ name: 'inicioAdmin' })
        } else {
            router.push({ name: 'inicio' })
        }

    } catch (error) {
        console.error(error)
    } finally {
        cargando.value = false
    }
}
</script>

<style scoped>
.login-pantalla {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    z-index: 9999;
}

.form-control:focus,
.form-select:focus {
    border-color: var(--primary, #077E9D);
    box-shadow: 0 0 0 0.25rem rgba(7, 126, 157, 0.25);
}
</style> -->