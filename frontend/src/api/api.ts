import axios from 'axios'
import { toast } from 'vue-sonner'

export const BACKEND_URL = import.meta.env.VITE_BACKEND_URL || 'http://localhost:8000'

// Esta URL debe apuntar al menú/launcher de la intranet, no a /sso-login sin firma.
// La intranet es quien conoce el usuario real y genera user, ts y sig.
export const INTRANET_ENTRY_URL = import.meta.env.VITE_INTRANET_ENTRY_URL || ''

const api = axios.create({
  baseURL: `${BACKEND_URL}/api`,
  withCredentials: true,
  withXSRFToken: true,
  headers: {
    Accept: 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
  },
})

export async function ensureCsrfCookie() {
  await axios.get(`${BACKEND_URL}/sanctum/csrf-cookie`, {
    withCredentials: true,
    withXSRFToken: true,
    headers: {
      Accept: 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
    },
  })
}

api.interceptors.response.use(
  (response) => {
    if (response.data && response.data.message) {
      toast.success(response.data.message)
    }

    return response
  },
  (error) => {
    if (!error.response) {
      toast.error('Error de red. Verifica tu conexión.')
      return Promise.reject(error)
    }

    const status = error.response.status
    const data = error.response.data || {}

    switch (status) {
      case 401:
        localStorage.removeItem('user_data')
        break
      case 403:
        toast.warning('No tienes permisos para realizar esta acción.')
        break
      case 404:
        toast.error('El recurso solicitado no existe.')
        break
      case 419:
        toast.error('La sesión CSRF expiró. Recarga la página e intenta de nuevo.')
        break
      case 422:
        if (data.errors) {
          Object.values(data.errors).flat().forEach((mensaje: any) => {
            toast.error(mensaje)
          })
        } else {
          toast.error(data.message || 'Por favor, revisa los datos del formulario.')
        }
        break
      case 500:
        toast.error('Error interno del servidor. Intenta más tarde.')
        break
      default:
        toast.error(data.message || 'Ocurrió un error inesperado.')
    }

    return Promise.reject(error)
  }
)

export default api


// v2

// import axios from 'axios'
// import { toast } from 'vue-sonner';

// const api = axios.create({
//   baseURL: 'http://127.0.0.1:8000/api',
//   withCredentials: true,
// })

// // Interceptor de Peticiones (Request) ──
// api.interceptors.request.use(
//   (config) => {
//     // 1. Buscamos el token en el almacenamiento local
//     const token = localStorage.getItem('auth_token');
    
//     // 2. Si existe, lo adjuntamos a las cabeceras de la petición
//     if (token) {
//       config.headers.Authorization = `Bearer ${token}`;
//     }
    
//     return config;
//   },
//   (error) => {
//     return Promise.reject(error);
//   }
// );

// // ── Interceptor de Respuestas (Response) ──
// api.interceptors.response.use(
//   (response) => {
//     // Si Laravel envía un 'message' en un 200/201, lo mostramos como éxito
//     if (response.data && response.data.message) {
//       toast.success(response.data.message);
//     }
//     return response;
//   },
//   (error) => {
//     // Si no hay respuesta del servidor (ej. servidor caído)
//     if (!error.response) {
//       toast.error('Error de red. Verifica tu conexión.');
//       return Promise.reject(error);
//     }

//     const status = error.response.status;
//     const data = error.response.data;

//     // Manejo de errores comunes de Laravel
//     switch (status) {
//       case 401:
//         toast.error('Tu sesión ha expirado o es inválida. Por favor, inicia sesión de nuevo.');
        
//         // 👇 FIX: Limpiamos los rastros del usuario simulado
//         localStorage.removeItem('auth_token');
//         localStorage.removeItem('user_data');
        
//         // 👇 FIX: Redirigimos al login (Usamos window.location para evitar errores de importación circular con vue-router)
//         if (window.location.pathname !== '/login') {
//             window.location.href = '/login';
//         }
//         break;
//       case 403:
//         toast.warning('No tienes permisos para realizar esta acción.');
//         break;
//       case 404:
//         toast.error('El recurso solicitado no existe.');
//         break;
//       case 422:
//         if (data.errors) {
//           // Extraemos todos los arrays de errores, los aplanamos en una sola lista
//           // y disparamos una alerta por cada uno.
//           const mensajesErrores = Object.values(data.errors).flat();
//           mensajesErrores.forEach((mensaje: any) => {
//             toast.error(mensaje);
//           });
//         } else {
//           // Fallback por si es un 422 genérico
//           toast.error(data.message || 'Por favor, revisa los datos del formulario.');
//         }
//         break;
//       case 500:
//         toast.error('Error interno del servidor. Intenta más tarde.');
//         break;
//       default:
//         toast.error(data.message || 'Ocurrió un error inesperado.');
//     }

//     return Promise.reject(error);
//   }
// );

// export default api;