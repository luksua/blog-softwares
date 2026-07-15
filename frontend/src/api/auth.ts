import api, { ensureCsrfCookie } from './api'

export interface AuthCache {
  usuario: any
  permisos: string[]
  es_admin: boolean
  puede_supervisar_area: boolean
}

// Cache a nivel de módulo: viven mientras dure la sesión del SPA (se resetean
// con una recarga completa de página, p.ej. al hacer logout).
let authVerificado: AuthCache | null = null
let intentoMeRealizado = false
let promesaEnCurso: Promise<AuthCache | null> | null = null

function normalizarPermisos(valor: any): string[] {
  if (!Array.isArray(valor)) {
    return []
  }

  return valor
    .map((item: any) => {
      if (typeof item === 'string') return item
      if (item?.permiso_nombre) return item.permiso_nombre
      if (item?.nombre) return item.nombre
      return ''
    })
    .filter(Boolean)
}

async function solicitarAuth(): Promise<AuthCache | null> {
  try {
    await ensureCsrfCookie()

    const response = await api.get('/me')

    const data = response.data
    const usuario = data.usuario || data.user || data
    const permisos = normalizarPermisos(data.permisos || usuario?.permisos || [])

    const grupo = String(usuario.usuario_grupo || '').toUpperCase()
    const esAdmin = data.es_admin ?? grupo === 'ADMIN'
    const puedeSupervisarArea =
      data.puede_supervisar_area ||
      data.tipo_usuario === 'admin' ||
      usuario.tipo_usuario === 'admin' ||
      permisos.includes('blog.supervisar_area')

    authVerificado = {
      usuario,
      permisos,
      es_admin: esAdmin,
      puede_supervisar_area: puedeSupervisarArea,
    }

    localStorage.setItem('user_data', JSON.stringify(usuario))

    return authVerificado
  } catch (error) {
    console.error(error)

    authVerificado = null
    localStorage.removeItem('user_data')
    localStorage.removeItem('auth_token')

    return null
  }
}

/**
 * Devuelve los datos de sesión del usuario autenticado, reutilizando el
 * resultado de una llamada previa a /me en vez de repetirla en cada
 * componente (router guard, MainLayout, páginas de admin, etc.).
 *
 * - Si ya hay un resultado cacheado, lo retorna de inmediato.
 * - Si hay una petición en curso, espera esa misma promesa (evita
 *   condiciones de carrera cuando varios componentes se montan a la vez).
 * - Solo intenta /me una vez por sesión de SPA; tras un fallo, retorna
 *   null hasta que se recargue la página (p. ej. tras un logout).
 */
export async function cargarAuth(): Promise<AuthCache | null> {
  if (authVerificado) {
    return authVerificado
  }

  if (intentoMeRealizado) {
    return promesaEnCurso ?? null
  }

  intentoMeRealizado = true
  promesaEnCurso = solicitarAuth()

  return promesaEnCurso
}

/**
 * Limpia la caché de autenticación en memoria (uso típico: logout).
 */
export function limpiarAuthCache(): void {
  authVerificado = null
  intentoMeRealizado = false
  promesaEnCurso = null
}
