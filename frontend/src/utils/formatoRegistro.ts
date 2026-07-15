import type { Version } from '../types/version'

/**
 * Funciones de formato/presentación para un registro (Version) del
 * blog. Viven acá porque tanto la tarjeta de la vista móvil como la
 * tabla de la vista desktop (dos componentes distintos) necesitan
 * exactamente la misma lógica para mostrar estado, área, categorías,
 * fechas y autor.
 */

export const formatearFecha = (fechaString?: string | null): string => {
  if (!fechaString) return 'Sin fecha'
  const opciones: Intl.DateTimeFormatOptions = { year: 'numeric', month: 'short', day: '2-digit' }
  const fechaStr = new Date(fechaString).toLocaleDateString('es-ES', opciones)
  return fechaStr.charAt(0).toUpperCase() + fechaStr.slice(1)
}

export const mapearClaseEstado = (estado: string): string => {
  const estadoMin = (estado || '').toLowerCase()
  if (estadoMin === 'publicado') return 'estado-green'
  if (estadoMin === 'borrador') return 'estado-yellow'
  if (estadoMin === 'revision') return 'estado-blue'
  if (estadoMin === 'inactivo') return 'estado-dark-gray'
  return 'estado-gray'
}

export const obtenerNombreArea = (item: Version): string =>
  (item as any).area_servicio?.area_servicio_nombre || 'Sin área'

export const obtenerListaCategorias = (item: Version): string[] => {
  if (Array.isArray((item as any).categorias) && (item as any).categorias.length > 0) {
    return (item as any).categorias
      .map((categoria: any) => categoria.categoria_actualizacion_nombre)
      .filter(Boolean)
  }

  if ((item as any).categoria?.categoria_actualizacion_nombre) {
    return [(item as any).categoria.categoria_actualizacion_nombre]
  }

  return []
}

const COLOR_MAP: Record<string, string> = {
  inicio: '#077E9D',
  noticias: '#FCBB1C',
  actualizaciones: '#025B7D',
  documentos: '#4F46E5',
  tutoriales: '#10B981',
  eventos: '#F59E0B',
  avisos: '#EF4444',
  novedades: '#8B5CF6',
  seguridad: '#DC2626',
  capacitacion: '#14B8A6',
  proyectos: '#6366F1',
}

export const getCategoriaColor = (nombre: string): string => {
  const lowerNombre = nombre.toLowerCase()
  for (const [key, color] of Object.entries(COLOR_MAP)) {
    if (lowerNombre.includes(key)) return color
  }
  return '#077E9D'
}

const ICONO_MAP: Record<string, string> = {
  'manual de usuario': 'bi-person-lines-fill',
  'manual tecnico': 'bi-tools',
  instalador: 'bi-box-arrow-down',
  'actualizacion del sistema': 'bi-arrow-repeat',
  'nueva funcionalidad': 'bi-stars',
  mejora: 'bi-arrow-up-circle-fill',
  'correccion de errores': 'bi-bug-fill',
  'parche de seguridad': 'bi-shield-fill-check',
  'guia de instalacion': 'bi-journal-arrow-down',
  'guia rapida': 'bi-lightning-charge-fill',
  documentacion: 'bi-file-earmark-text-fill',
  'notas de version': 'bi-card-list',
  general: 'bi-info-circle-fill',
}

export const getCategoriaIcon = (nombre: string): string => {
  const lowerNombre = nombre.toLowerCase()
  for (const [key, icon] of Object.entries(ICONO_MAP)) {
    if (lowerNombre.includes(key)) return icon
  }
  return 'bi bi-tag-fill'
}

export const obtenerNombreAutor = (item: any): string => {
  const autor = item.usuario_autor || item.autor || item.usuario
  if (!autor) return 'Empleado del área'

  const nombres = [autor.usuario_nombre1, autor.usuario_nombre2, autor.usuario_apellido1, autor.usuario_apellido2]
    .filter(Boolean)
    .join(' ')
    .trim()

  return nombres || autor.usuario_nombre || autor.usuario_usuario || autor.usuario_login || 'Empleado del área'
}