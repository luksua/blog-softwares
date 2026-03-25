<!-- Home View -->

<template>
  <div class="contenedor-lista">
    <div v-if="cargando" class="estado-mensaje">
      <p>Cargando actualizaciones...</p>
    </div>

    <div v-else-if="error" class="estado-mensaje error">
      <p>{{ error }}</p>
      <button @click="obtenerActualizaciones">Reintentar</button>
    </div>

    <div v-else-if="actualizaciones.length === 0" class="estado-mensaje vacio">
      <div class="icono-vacio">📄</div>
      <h3>No hay actualizaciones para mostrar</h3>
      <p>Aún no se ha registrado ninguna actualización en el sistema.</p>
      <!-- <button class="btn-crear">Crear nueva actualización</button> -->
    </div>

    <div v-else class="grid-tarjetas">
      <div v-for="item in actualizaciones" :key="item.id" class="tarjeta">
        
        <div class="tarjeta-imagen">
          <img 
            v-if="item.imagen_destacada" 
            :src="item.imagen_destacada" 
            alt="Miniatura del blog" 
          />
          <div v-else class="sin-imagen">Sin imagen</div>
          
          <span :class="['etiqueta-estado', item.estado.toLowerCase()]">
            {{ item.estado }}
          </span>
        </div>

        <div class="tarjeta-cuerpo">
          <h3>{{ item.titulo }}</h3>
          <p class="fecha">{{ formatearFecha(item.fecha_publicacion) }}</p>
          <p class="resumen">{{ item.resumen }}</p>
        </div>

        <div class="tarjeta-pie">
          <span class="galeria-info">
            📸 {{ item.imagenes ? item.imagenes.length : 0 }} imágenes adjuntas
          </span>
          <button class="btn-editar">Ver / Editar</button>
        </div>

      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
// Importamos tu instancia de axios configurada
import api from '../../api/api';
import type { Version } from '../../types/version'
// 1. Definimos las interfaces para TypeScript


// 2. Estados reactivos
const actualizaciones = ref<Version[]>([]);
const cargando = ref(true);
const error = ref('');

// 3. Función para obtener los datos
const obtenerActualizaciones = async () => {
  cargando.value = true;
  error.value = '';
  
  try {
    // Hacemos el GET a la misma ruta
    const respuesta = await api.get('/admin/actualizaciones');
    actualizaciones.value = respuesta.data;
  } catch (err) {
    console.error('Error al cargar la lista:', err);
    error.value = 'No se pudo conectar con el servidor para obtener los datos.';
  } finally {
    cargando.value = false;
  }
};

// 4. Utilidad para que la fecha se vea bonita (ej. 25 de Marzo de 2026)
const formatearFecha = (fechaString: string) => {
  if (!fechaString) return 'Sin fecha';
  const opciones: Intl.DateTimeFormatOptions = { year: 'numeric', month: 'long', day: 'numeric' };
  return new Date(fechaString).toLocaleDateString('es-ES', opciones);
};

// 5. Ejecutar la función automáticamente al cargar el componente
onMounted(() => {
  obtenerActualizaciones();
});
</script>

<style scoped>
.contenedor-lista {
  max-width: 1000px;
  margin: 0 auto;
  padding: 20px;
  font-family: sans-serif;
}

.cabecera {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.estado-mensaje {
  text-align: center;
  padding: 40px;
  background-color: #f9f9f9;
  border-radius: 8px;
  color: #666;
}

.estado-mensaje.error {
  color: #d32f2f;
  background-color: #ffebee;
}

.grid-tarjetas {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 20px;
}

.tarjeta {
  border: 1px solid #e0e0e0;
  border-radius: 8px;
  overflow: hidden;
  background-color: white;
  transition: transform 0.2s, box-shadow 0.2s;
  display: flex;
  flex-direction: column;
}

.tarjeta:hover {
  transform: translateY(-5px);
  box-shadow: 0 8px 15px rgba(0,0,0,0.1);
}

.tarjeta-imagen {
  position: relative;
  height: 180px;
  background-color: #eee;
}

.tarjeta-imagen img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.sin-imagen {
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #999;
}

.etiqueta-estado {
  position: absolute;
  top: 10px;
  right: 10px;
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 0.8rem;
  font-weight: bold;
  color: white;
  text-transform: capitalize;
}

.etiqueta-estado.publicado { background-color: #4caf50; }
.etiqueta-estado.borrador { background-color: #ff9800; }
.etiqueta-estado.revision { background-color: #2196f3; }

.tarjeta-cuerpo {
  padding: 15px;
  flex-grow: 1;
}

.tarjeta-cuerpo h3 {
  margin: 0 0 10px 0;
  font-size: 1.2rem;
  color: #333;
}

.fecha {
  font-size: 0.85rem;
  color: #888;
  margin-bottom: 10px;
}

.resumen {
  font-size: 0.95rem;
  color: #555;
  line-height: 1.4;
}

.tarjeta-pie {
  padding: 15px;
  border-top: 1px solid #eee;
  display: flex;
  justify-content: space-between;
  align-items: center;
  background-color: #fafafa;
}

.galeria-info {
  font-size: 0.85rem;
  color: #666;
}

.btn-editar {
  background-color: #2196f3;
  color: white;
  border: none;
  padding: 6px 12px;
  border-radius: 4px;
  cursor: pointer;
  font-size: 0.9rem;
}

.btn-editar:hover {
  background-color: #1976d2;
}
</style>