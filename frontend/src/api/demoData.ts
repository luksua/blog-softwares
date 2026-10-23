import type { Category } from '../types/categorias'
import type { Area } from '../types/areas'
import type { BlogDashboardData } from '../types/dashboard'
import type { BlogNotification } from '../api/notificaciones'
import type { ObservacionPendiente } from '../api/observaciones'

const LOCAL_STORAGE_KEY_ACTUALIZACIONES = 'demo_blog_actualizaciones'
const LOCAL_STORAGE_KEY_BOOKMARKS = 'demo_blog_bookmarks'
const LOCAL_STORAGE_KEY_NOTIFICACIONES = 'demo_blog_notificaciones'
const LOCAL_STORAGE_KEY_OBSERVACIONES = 'demo_blog_observaciones'

export const DEMO_AREAS: Area[] = [
  { area_servicio_id: 1, area_servicio_nombre: 'Tecnología y Sistemas' },
  { area_servicio_id: 2, area_servicio_nombre: 'Gestión Clínica y Urgencias' },
  { area_servicio_id: 3, area_servicio_nombre: 'Facturación y Cartera' },
  { area_servicio_id: 4, area_servicio_nombre: 'Calidad y Seguridad del Paciente' },
  { area_servicio_id: 5, area_servicio_nombre: 'Talento Humano' },
  { area_servicio_id: 6, area_servicio_nombre: 'Farmacia e Insumos' },
]

export const DEMO_CATEGORIAS: Category[] = [
  { categoria_actualizacion_id: 1, categoria_actualizacion_nombre: 'Manual de Usuario', categoria_actualizacion_icono: 'bi-person-lines-fill' },
  { categoria_actualizacion_id: 2, categoria_actualizacion_nombre: 'Actualización del Sistema', categoria_actualizacion_icono: 'bi-arrow-repeat' },
  { categoria_actualizacion_id: 3, categoria_actualizacion_nombre: 'Nueva Funcionalidad', categoria_actualizacion_icono: 'bi-stars' },
  { categoria_actualizacion_id: 4, categoria_actualizacion_nombre: 'Parche de Seguridad', categoria_actualizacion_icono: 'bi-shield-fill-check' },
  { categoria_actualizacion_id: 5, categoria_actualizacion_nombre: 'Tutoriales y Guías', categoria_actualizacion_icono: 'bi-lightning-charge-fill' },
  { categoria_actualizacion_id: 6, categoria_actualizacion_nombre: 'Documentación Técnica', categoria_actualizacion_icono: 'bi-tools' },
]

export const DEMO_ESTADOS = [
  { id: 'borrador', nombre: 'Borrador' },
  { id: 'revision', nombre: 'Revisión' },
  { id: 'publicado', nombre: 'Publicado' },
  { id: 'programado', nombre: 'Programado' },
  { id: 'inactivo', nombre: 'Inactivo' },
]

function generarContenidoEditorJs(titulo: string, puntos: string[]) {
  const blocks = [
    {
      id: 'h1-intro',
      type: 'header',
      data: {
        text: `1. Descripción general de ${titulo}`,
        level: 2,
      },
    },
    {
      id: 'p-intro',
      type: 'paragraph',
      data: {
        text: 'Esta versión introduce mejoras clave en rendimiento, usabilidad y estabilidad para todos los colaboradores de la institución.',
      },
    },
    {
      id: 'h2-features',
      type: 'header',
      data: {
        text: '2. Novedades y Cambios Destacados',
        level: 2,
      },
    },
    {
      id: 'list-features',
      type: 'list',
      data: {
        style: 'unordered',
        items: puntos,
      },
    },
    {
      id: 'h2-instrucciones',
      type: 'header',
      data: {
        text: '3. Guía de Uso y Recomendaciones',
        level: 2,
      },
    },
    {
      id: 'p-instrucciones',
      type: 'paragraph',
      data: {
        text: 'Recuerda que ante cualquier duda o inconsistencia puedes contactar a la mesa de ayuda de Sistemas o remitir tus observaciones mediante este módulo.',
      },
    },
  ]

  const html = `
    <h2>1. Descripción general de ${titulo}</h2>
    <p>Esta versión introduce mejoras clave en rendimiento, usabilidad y estabilidad para todos los colaboradores de la institución.</p>
    <h2>2. Novedades y Cambios Destacados</h2>
    <ul>
      ${puntos.map((punto) => `<li>${punto}</li>`).join('')}
    </ul>
    <h2>3. Guía de Uso y Recomendaciones</h2>
    <p>Recuerda que ante cualquier duda o inconsistencia puedes contactar a la mesa de ayuda de Sistemas o remitir tus observaciones mediante este módulo.</p>
  `

  return {
    json: {
      time: Date.now(),
      blocks,
      version: '2.31.6',
    },
    html,
  }
}

const contenido1 = generarContenidoEditorJs('Historia Clínica Electrónica 2.4', [
  'Búsqueda predictiva e inteligente de códigos diagnósticos CIE-10.',
  'Validación instantánea de alergias y antecedentes al prescribir medicamentos.',
  'Nuevo panel de atajos de teclado (Ctrl + K) para agilizar el diligenciamiento.',
  'Firma digital optimizada con carga en segundo plano sin congelar la ventana.',
])

const contenido2 = generarContenidoEditorJs('Protocolo de Seguridad y 2FA', [
  'Autenticación obligatoria en 2 pasos mediante aplicación Authenticator.',
  'Detección automática de inicios de sesión inusuales desde direcciones externas.',
  'Cierre de sesión automático tras 15 minutos de inactividad médica.',
  'Cifrado reforzado de extremo a extremo en documentos clínicos sensibles.',
])

const contenido3 = generarContenidoEditorJs('Cierre de Facturación y Radicación RIPS', [
  'Generación de archivos RIPS JSON según la última resolución ministerial.',
  'Reporte de auditoría previa de inconsistencias de tarifas antes de radicar.',
  'Módulo de exportación directa a Excel con filtros dinámicos por aseguradora.',
])

const contenido4 = generarContenidoEditorJs('Dispensación Farmacéutica y Control de Stock', [
  'Descuento automático de inventario al despachar órdenes hospitalarias.',
  'Alertas tempranas de medicamentos con stock menor al 15% o próximos a vencer.',
  'Trazabilidad completa con número de lote y registro Invima en cada prescripción.',
])

const contenido5 = generarContenidoEditorJs('Portal Unificado de Solicitudes y Soporte', [
  'Nuevo catálogo interactivo de servicios tecnológicos con tiempos de respuesta SLA.',
  'Seguimiento en vivo del estado de los requerimientos y tickets asignados.',
  'Módulo de autogestión para restablecimiento de claves y desbloqueo de cuentas.',
])

const contenido6 = generarContenidoEditorJs('Mantenimiento Preventivo de Equipos Biomédicos', [
  'Cronograma digitalizado de inspecciones periódicas para el personal técnico.',
  'Checklist de calibración de monitores de signos vitales y bombas de infusión.',
  'Ficha técnica descargable con historial de reparaciones y repuestos.',
])

export const DEMO_ACTUALIZACIONES_INICIALES: any[] = [
  {
    id: 1,
    actualizacion_titulo: 'Módulo de Historia Clínica Electrónica 2.4: Nuevos Atajos y Validación Rápida',
    actualizacion_version: '2.4.0',
    actualizacion_resumen: 'Se implementaron atajos de teclado para la formulación rápida de medicamentos y la validación en tiempo real de alergias registradas del paciente.',
    actualizacion_imagen_destacada: 'https://images.unsplash.com/photo-1576091160399-112ba8d25d1d?w=1200&auto=format&fit=crop&q=80',
    actualizacion_estado: 'publicado',
    actualizacion_fecha_publicacion: '2026-10-21T09:00:00.000000Z',
    actualizacion_fecha_creacion: '2026-10-20T14:30:00.000000Z',
    actualizacion_area_servicio_id: 1,
    actualizacion_categoria_id: 3,
    actualizacion_categoria_ids: [1, 3],
    actualizacion_imagenes: [],
    actualizacion_lecturas: 124,
    area_servicio: DEMO_AREAS[0],
    categoria: DEMO_CATEGORIAS[2],
    categorias: [DEMO_CATEGORIAS[0], DEMO_CATEGORIAS[2]],
    actualizacion_contenido: contenido1.json,
    actualizacion_contenido_html: contenido1.html,
    actualizacion_usuario_id_autor: 1,
    usuario_autor: {
      usuario_id: 1,
      usuario_nombre1: 'Carlos',
      usuario_apellido1: 'Mendoza',
      usuario_usuario: 'carlos.m',
      area_servicio: DEMO_AREAS[0],
    },
  },
  {
    id: 2,
    actualizacion_titulo: 'Protocolo de Seguridad Institucional y Doble Factor de Autenticación (2FA)',
    actualizacion_version: '1.2.0',
    actualizacion_resumen: 'Actualización obligatoria para reforzar el acceso a la plataforma asistencial mediante código temporal o huella biométrica.',
    actualizacion_imagen_destacada: 'https://images.unsplash.com/photo-1563986768609-322da13575f3?w=1200&auto=format&fit=crop&q=80',
    actualizacion_estado: 'publicado',
    actualizacion_fecha_publicacion: '2026-10-18T10:15:00.000000Z',
    actualizacion_fecha_creacion: '2026-10-17T11:00:00.000000Z',
    actualizacion_area_servicio_id: 4,
    actualizacion_categoria_id: 4,
    actualizacion_categoria_ids: [4, 2],
    actualizacion_imagenes: [],
    actualizacion_lecturas: 98,
    area_servicio: DEMO_AREAS[3],
    categoria: DEMO_CATEGORIAS[3],
    categorias: [DEMO_CATEGORIAS[3], DEMO_CATEGORIAS[1]],
    actualizacion_contenido: contenido2.json,
    actualizacion_contenido_html: contenido2.html,
    actualizacion_usuario_id_autor: 1,
    usuario_autor: {
      usuario_id: 1,
      usuario_nombre1: 'Carlos',
      usuario_apellido1: 'Mendoza',
      usuario_usuario: 'carlos.m',
      area_servicio: DEMO_AREAS[0],
    },
  },
  {
    id: 3,
    actualizacion_titulo: 'Guía Rápida para el Cierre de Facturación y Radicación Masiva de RIPS',
    actualizacion_version: '3.1.2',
    actualizacion_resumen: 'Paso a paso para conciliar lotes de facturación con el nuevo validador del Ministerio y evitar glosas recurrentes.',
    actualizacion_imagen_destacada: 'https://images.unsplash.com/photo-1554224155-8d04cb21cd6c?w=1200&auto=format&fit=crop&q=80',
    actualizacion_estado: 'publicado',
    actualizacion_fecha_publicacion: '2026-10-14T08:00:00.000000Z',
    actualizacion_fecha_creacion: '2026-10-13T16:00:00.000000Z',
    actualizacion_area_servicio_id: 3,
    actualizacion_categoria_id: 5,
    actualizacion_categoria_ids: [1, 5],
    actualizacion_imagenes: [],
    actualizacion_lecturas: 85,
    area_servicio: DEMO_AREAS[2],
    categoria: DEMO_CATEGORIAS[4],
    categorias: [DEMO_CATEGORIAS[0], DEMO_CATEGORIAS[4]],
    actualizacion_contenido: contenido3.json,
    actualizacion_contenido_html: contenido3.html,
    actualizacion_usuario_id_autor: 2,
    usuario_autor: {
      usuario_id: 2,
      usuario_nombre1: 'Dra. Laura',
      usuario_apellido1: 'Gómez',
      usuario_usuario: 'laura.g',
      area_servicio: DEMO_AREAS[2],
    },
  },
  {
    id: 4,
    actualizacion_titulo: 'Integración con Sistema de Farmacia y Control de Stock en Tiempo Real',
    actualizacion_version: '2.0.1',
    actualizacion_resumen: 'Sincronización automática de inventario al despachar órdenes médicas desde hospitalización y quirófanos.',
    actualizacion_imagen_destacada: 'https://images.unsplash.com/photo-1587854692152-cbe660dbde88?w=1200&auto=format&fit=crop&q=80',
    actualizacion_estado: 'publicado',
    actualizacion_fecha_publicacion: '2026-10-08T15:30:00.000000Z',
    actualizacion_fecha_creacion: '2026-10-07T14:00:00.000000Z',
    actualizacion_area_servicio_id: 6,
    actualizacion_categoria_id: 3,
    actualizacion_categoria_ids: [3, 6],
    actualizacion_imagenes: [],
    actualizacion_lecturas: 67,
    area_servicio: DEMO_AREAS[5],
    categoria: DEMO_CATEGORIAS[2],
    categorias: [DEMO_CATEGORIAS[2], DEMO_CATEGORIAS[5]],
    actualizacion_contenido: contenido4.json,
    actualizacion_contenido_html: contenido4.html,
    actualizacion_usuario_id_autor: 3,
    usuario_autor: {
      usuario_id: 3,
      usuario_nombre1: 'Andrés',
      usuario_apellido1: 'Ruiz',
      usuario_usuario: 'andres.r',
      area_servicio: DEMO_AREAS[5],
    },
  },
  {
    id: 5,
    actualizacion_titulo: 'Rediseño del Portal de Solicitudes y Autoservicio de TI',
    actualizacion_version: '1.0.0-rc',
    actualizacion_resumen: 'Propuesta de interfaz unificada para tickets, cambio de contraseñas y solicitudes de soporte técnico.',
    actualizacion_imagen_destacada: 'https://images.unsplash.com/photo-1460925895917-afdab827c52f?w=1200&auto=format&fit=crop&q=80',
    actualizacion_estado: 'borrador',
    actualizacion_fecha_publicacion: '2026-10-23T11:00:00.000000Z',
    actualizacion_fecha_creacion: '2026-10-23T10:00:00.000000Z',
    actualizacion_area_servicio_id: 1,
    actualizacion_categoria_id: 3,
    actualizacion_categoria_ids: [3],
    actualizacion_imagenes: [],
    actualizacion_lecturas: 4,
    area_servicio: DEMO_AREAS[0],
    categoria: DEMO_CATEGORIAS[2],
    categorias: [DEMO_CATEGORIAS[2]],
    actualizacion_contenido: contenido5.json,
    actualizacion_contenido_html: contenido5.html,
    actualizacion_usuario_id_autor: 1,
    usuario_autor: {
      usuario_id: 1,
      usuario_nombre1: 'Carlos',
      usuario_apellido1: 'Mendoza',
      usuario_usuario: 'carlos.m',
      area_servicio: DEMO_AREAS[0],
    },
  },
  {
    id: 6,
    actualizacion_titulo: 'Guía de Mantenimiento Preventivo de Equipos Biomédicos de Alta Complejidad',
    actualizacion_version: '1.1.0',
    actualizacion_resumen: 'Documento en proceso de validación por jefatura de biomédica sobre calibración de monitores y bombas de infusión.',
    actualizacion_imagen_destacada: 'https://images.unsplash.com/photo-1516549655169-df83a0774514?w=1200&auto=format&fit=crop&q=80',
    actualizacion_estado: 'revision',
    actualizacion_fecha_publicacion: '2026-10-22T14:00:00.000000Z',
    actualizacion_fecha_creacion: '2026-10-22T08:30:00.000000Z',
    actualizacion_area_servicio_id: 4,
    actualizacion_categoria_id: 6,
    actualizacion_categoria_ids: [6],
    actualizacion_imagenes: [],
    actualizacion_lecturas: 12,
    area_servicio: DEMO_AREAS[3],
    categoria: DEMO_CATEGORIAS[5],
    categorias: [DEMO_CATEGORIAS[5]],
    actualizacion_contenido: contenido6.json,
    actualizacion_contenido_html: contenido6.html,
    actualizacion_usuario_id_autor: 1,
    revision_observacion: {
      id: 501,
      observacion: 'Favor actualizar los rangos de calibración de las bombas de infusión marca Terumo según el protocolo institucional 2026 antes de publicar.',
      created_at: '2026-10-22T11:20:00.000000Z',
    },
    usuario_autor: {
      usuario_id: 1,
      usuario_nombre1: 'Carlos',
      usuario_apellido1: 'Mendoza',
      usuario_usuario: 'carlos.m',
      area_servicio: DEMO_AREAS[0],
    },
  },
]

export const DEMO_NOTIFICACIONES_INICIALES: BlogNotification[] = [
  {
    id: 101,
    tipo: 'aprobacion',
    titulo: 'Publicación aprobada',
    mensaje: 'Tu actualización v2.4.0 (Historia Clínica) ha sido aprobada y publicada exitosamente.',
    data: { ruta_sugerida: { name: 'actualizaciones-show', params: { id: 1 } } },
    leida_en: null,
    created_at: '2026-10-22T16:00:00.000000Z',
    actualizacion_id: 1,
    actualizacion: {
      id: 1,
      actualizacion_titulo: 'Módulo de Historia Clínica Electrónica 2.4',
      actualizacion_estado: 'publicado',
      actualizacion_version: '2.4.0',
    },
  },
  {
    id: 102,
    tipo: 'observacion',
    titulo: 'Revisión pendiente',
    mensaje: 'Se solicitó revisión para la guía de mantenimiento de equipos biomédicos.',
    data: { ruta_sugerida: { name: 'supervision-show', params: { id: 6 } } },
    leida_en: null,
    created_at: '2026-10-22T11:20:00.000000Z',
    actualizacion_id: 6,
    actualizacion: {
      id: 6,
      actualizacion_titulo: 'Guía de Mantenimiento Preventivo de Equipos Biomédicos',
      actualizacion_estado: 'revision',
      actualizacion_version: '1.1.0',
    },
  },
  {
    id: 103,
    tipo: 'sistema',
    titulo: 'Parche de Seguridad Aplicado',
    mensaje: 'El protocolo de seguridad 2FA ya está activo en todas las estaciones asistenciales.',
    data: { ruta_sugerida: { name: 'actualizaciones-show', params: { id: 2 } } },
    leida_en: '2026-10-19T09:00:00.000000Z',
    created_at: '2026-10-18T10:15:00.000000Z',
    actualizacion_id: 2,
    actualizacion: {
      id: 2,
      actualizacion_titulo: 'Protocolo de Seguridad Institucional y 2FA',
      actualizacion_estado: 'publicado',
      actualizacion_version: '1.2.0',
    },
  },
]

export const DEMO_DASHBOARD_DATA: BlogDashboardData = {
  alcance: {
    tipo: 'global',
    areas: [1, 2, 3, 4, 5, 6],
    descripcion: 'Vista Global Institucional (Todas las áreas)',
  },
  resumen: {
    total_registros: 18,
    publicados: 12,
    revision: 3,
    borradores: 2,
    programados: 1,
    inactivos: 0,
  },
  registros_mas_leidos_area: [
    { id: 1, titulo: 'Módulo de Historia Clínica Electrónica 2.4', lecturas: 124, area_servicio_id: 1 },
    { id: 2, titulo: 'Protocolo de Seguridad Institucional y 2FA', lecturas: 98, area_servicio_id: 4 },
    { id: 3, titulo: 'Guía Rápida para el Cierre de Facturación y RIPS', lecturas: 85, area_servicio_id: 3 },
    { id: 4, titulo: 'Dispensación Farmacéutica y Control de Stock', lecturas: 67, area_servicio_id: 6 },
  ],
  usuarios_mas_registros: [
    { usuario_id: 1, usuario: 'Carlos Mendoza (Sistemas)', total: 8 },
    { usuario_id: 2, usuario: 'Dra. Laura Gómez (Facturación)', total: 4 },
    { usuario_id: 3, usuario: 'Andrés Ruiz (Farmacia)', total: 3 },
    { usuario_id: 4, usuario: 'Sandra Rojas (Calidad)', total: 3 },
  ],
  registros_por_estado: [
    { estado: 'Publicado', total: 12 },
    { estado: 'En Revisión', total: 3 },
    { estado: 'Borrador', total: 2 },
    { estado: 'Programado', total: 1 },
  ],
  registros_por_area: [
    { area_servicio_id: 1, area: 'Tecnología y Sistemas', total: 8, pendientes_revision: 0 },
    { area_servicio_id: 4, area: 'Calidad y Seguridad del Paciente', total: 4, pendientes_revision: 1 },
    { area_servicio_id: 3, area: 'Facturación y Cartera', total: 3, pendientes_revision: 1 },
    { area_servicio_id: 6, area: 'Farmacia e Insumos', total: 2, pendientes_revision: 1 },
    { area_servicio_id: 2, area: 'Gestión Clínica y Urgencias', total: 1, pendientes_revision: 0 },
  ],
  programados_proximos: [
    {
      id: 7,
      titulo: 'Lanzamiento de Aplicación Móvil de Turnos y Citas v1.0',
      fecha_publicacion: new Date(Date.now() + 18 * 60 * 60 * 1000).toISOString(),
      area: 'Tecnología y Sistemas',
    },
  ],
  empleados_mas_activos: [
    { usuario_id: 10, usuario: 'Dra. Claudia Morales', total_visualizaciones: 48, registros_vistos: 14, ultima_visualizacion: '2026-10-23T10:15:00.000Z' },
    { usuario_id: 11, usuario: 'Dr. Fernando Pardo', total_visualizaciones: 39, registros_vistos: 11, ultima_visualizacion: '2026-10-23T09:40:00.000Z' },
    { usuario_id: 12, usuario: 'Enf. Patricia Henao', total_visualizaciones: 34, registros_vistos: 10, ultima_visualizacion: '2026-10-22T17:20:00.000Z' },
    { usuario_id: 13, usuario: 'Ing. Rodrigo Salinas', total_visualizaciones: 29, registros_vistos: 8, ultima_visualizacion: '2026-10-22T14:10:00.000Z' },
  ],
  lecturas_disponibles: true,
  areas_mas_mencionadas: [
    { area_servicio_id: 1, area: 'Tecnología y Sistemas', total: 14, autores: 3 },
    { area_servicio_id: 2, area: 'Gestión Clínica y Urgencias', total: 9, autores: 4 },
    { area_servicio_id: 3, area: 'Facturación y Cartera', total: 6, autores: 2 },
    { area_servicio_id: 4, area: 'Calidad y Seguridad del Paciente', total: 5, autores: 2 },
  ],
}

// ── Helpers de persistencia en localStorage para que sea interactivo ──

export function obtenerActualizacionesDemo(): any[] {
  try {
    const guardado = localStorage.getItem(LOCAL_STORAGE_KEY_ACTUALIZACIONES)
    if (guardado) {
      const parsed = JSON.parse(guardado)
      if (Array.isArray(parsed) && parsed.length > 0) {
        let huboCambios = false
        parsed.forEach((item: any) => {
          if (!item.actualizacion_contenido) {
            item.actualizacion_contenido = generarContenidoEditorJs(
              item.actualizacion_titulo || 'Registro',
              ['Punto destacado de la actualización.', 'Detalles del registro generado.']
            ).json
            huboCambios = true
          }
        })
        if (huboCambios) {
          localStorage.setItem(LOCAL_STORAGE_KEY_ACTUALIZACIONES, JSON.stringify(parsed))
        }
        return parsed
      }
    }
  } catch (e) {
    console.error(e)
  }
  localStorage.setItem(LOCAL_STORAGE_KEY_ACTUALIZACIONES, JSON.stringify(DEMO_ACTUALIZACIONES_INICIALES))
  return DEMO_ACTUALIZACIONES_INICIALES
}

export function guardarActualizacionesDemo(lista: any[]) {
  try {
    localStorage.setItem(LOCAL_STORAGE_KEY_ACTUALIZACIONES, JSON.stringify(lista))
  } catch (e) {
    console.error(e)
  }
}

export function obtenerBookmarksDemo(): number[] {
  try {
    const guardado = localStorage.getItem(LOCAL_STORAGE_KEY_BOOKMARKS)
    if (guardado) {
      return JSON.parse(guardado)
    }
  } catch (e) {
    console.error(e)
  }
  const inicial = [1, 3]
  localStorage.setItem(LOCAL_STORAGE_KEY_BOOKMARKS, JSON.stringify(inicial))
  return inicial
}

export function guardarBookmarksDemo(ids: number[]) {
  try {
    localStorage.setItem(LOCAL_STORAGE_KEY_BOOKMARKS, JSON.stringify(ids))
  } catch (e) {
    console.error(e)
  }
}

export function obtenerNotificacionesDemo(): BlogNotification[] {
  try {
    const guardado = localStorage.getItem(LOCAL_STORAGE_KEY_NOTIFICACIONES)
    if (guardado) {
      return JSON.parse(guardado)
    }
  } catch (e) {
    console.error(e)
  }
  localStorage.setItem(LOCAL_STORAGE_KEY_NOTIFICACIONES, JSON.stringify(DEMO_NOTIFICACIONES_INICIALES))
  return DEMO_NOTIFICACIONES_INICIALES
}

export function guardarNotificacionesDemo(notificaciones: BlogNotification[]) {
  try {
    localStorage.setItem(LOCAL_STORAGE_KEY_NOTIFICACIONES, JSON.stringify(notificaciones))
  } catch (e) {
    console.error(e)
  }
}

export const DEMO_OBSERVACIONES_INICIALES: ObservacionPendiente[] = [
  {
    id: 501,
    observacion: 'Favor actualizar los rangos de calibración de las bombas de infusión marca Terumo según el protocolo institucional 2026 antes de publicar.',
    estado_anterior: 'borrador',
    estado_nuevo: 'revision',
    created_at: '2026-10-22T11:20:00.000000Z',
    actualizacion_id: 6,
    actualizacion: {
      id: 6,
      actualizacion_titulo: 'Guía de Mantenimiento Preventivo de Equipos Biomédicos de Alta Complejidad',
      actualizacion_estado: 'revision',
      actualizacion_version: '1.1.0',
    },
  },
]

export function obtenerObservacionesDemo(): ObservacionPendiente[] {
  try {
    const guardado = localStorage.getItem(LOCAL_STORAGE_KEY_OBSERVACIONES)
    if (guardado) {
      return JSON.parse(guardado)
    }
  } catch (e) {
    console.error(e)
  }
  localStorage.setItem(LOCAL_STORAGE_KEY_OBSERVACIONES, JSON.stringify(DEMO_OBSERVACIONES_INICIALES))
  return DEMO_OBSERVACIONES_INICIALES
}

export function guardarObservacionesDemo(observaciones: ObservacionPendiente[]) {
  try {
    localStorage.setItem(LOCAL_STORAGE_KEY_OBSERVACIONES, JSON.stringify(observaciones))
  } catch (e) {
    console.error(e)
  }
}

