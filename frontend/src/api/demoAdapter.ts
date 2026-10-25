import type { InternalAxiosRequestConfig, AxiosResponse } from 'axios'
import {
  DEMO_AREAS,
  DEMO_CATEGORIAS,
  DEMO_ESTADOS,
  DEMO_DASHBOARD_DATA,
  obtenerActualizacionesDemo,
  guardarActualizacionesDemo,
  obtenerBookmarksDemo,
  guardarBookmarksDemo,
  obtenerNotificacionesDemo,
  guardarNotificacionesDemo,
  obtenerObservacionesDemo,
  guardarObservacionesDemo,
} from './demoData'
import { USUARIO_DEMO } from './auth'

export async function demoAdapter(config: InternalAxiosRequestConfig): Promise<AxiosResponse> {
  const url = (config.url || '').replace(/^https?:\/\/[^/]+/, '')
  const method = (config.method || 'get').toLowerCase()

  // Simular pequeña latencia realista de red para suavidad de UI (150ms)
  await new Promise((resolve) => setTimeout(resolve, 120))

  const cleanUrl = url.startsWith('/api') ? url.replace('/api', '') : url
  const [pathname, search] = cleanUrl.split('?')
  const params = new URLSearchParams(search || '')

  if (config.params) {
    Object.entries(config.params).forEach(([k, v]) => {
      if (v !== undefined && v !== null) params.set(k, String(v))
    })
  }

  // 1. Auth me
  if (pathname === '/me') {
    return crearRespuesta(200, {
      usuario: USUARIO_DEMO.usuario,
      permisos: USUARIO_DEMO.permisos,
      es_admin: true,
      puede_supervisar_area: true,
      tipo_usuario: 'admin',
    }, config)
  }

  // 2. CSRF cookie
  if (pathname.includes('csrf-cookie')) {
    return crearRespuesta(204, null, config)
  }

  // 3. Catálogos
  if (pathname === '/area-servicio') {
    return crearRespuesta(200, { data: DEMO_AREAS }, config)
  }

  if (pathname === '/categorias') {
    return crearRespuesta(200, { data: DEMO_CATEGORIAS }, config)
  }

  if (pathname === '/estados-actualizacion') {
    return crearRespuesta(200, { data: DEMO_ESTADOS }, config)
  }

  // 4. Actualizaciones (Listar)
  if (pathname === '/actualizaciones' && method === 'get') {
    let lista = [...obtenerActualizacionesDemo()]
    const vista = params.get('vista') || 'blog'
    const busqueda = (params.get('busqueda') || '').toLowerCase().trim()
    const areaId = params.get('area_servicio_id')
    const catId = params.get('actualizacion_categoria_id')
    const orden = params.get('orden') || 'recientes'

    // Filtro por vista
    if (vista === 'blog') {
      lista = lista.filter((item) => item.actualizacion_estado === 'publicado')
    } else if (vista === 'mis-registros') {
      lista = lista.filter((item) => Number(item.actualizacion_usuario_id_autor) === 1)
    } else if (vista === 'supervision') {
      // En supervisión el supervisor ve todos los registros de los colaboradores para auditar o marcar revisión
    }

    const estadoParam = params.get('estado')
    if (estadoParam) {
      lista = lista.filter((item) => item.actualizacion_estado === estadoParam)
    }

    // Filtro por búsqueda
    if (busqueda) {
      lista = lista.filter(
        (item) =>
          item.actualizacion_titulo?.toLowerCase().includes(busqueda) ||
          item.actualizacion_resumen?.toLowerCase().includes(busqueda) ||
          item.actualizacion_version?.toLowerCase().includes(busqueda)
      )
    }

    // Filtro por área
    if (areaId) {
      lista = lista.filter((item) => Number(item.actualizacion_area_servicio_id) === Number(areaId))
    }

    // Filtro por categoría
    if (catId) {
      lista = lista.filter((item) => {
        if (item.actualizacion_categoria_ids && Array.isArray(item.actualizacion_categoria_ids)) {
          return item.actualizacion_categoria_ids.map(Number).includes(Number(catId))
        }
        return Number(item.actualizacion_categoria_id) === Number(catId)
      })
    }

    // Orden
    if (orden === 'az') {
      lista.sort((a, b) => (a.actualizacion_titulo || '').localeCompare(b.actualizacion_titulo || ''))
    } else if (orden === 'za') {
      lista.sort((a, b) => (b.actualizacion_titulo || '').localeCompare(a.actualizacion_titulo || ''))
    } else if (orden === 'antiguos') {
      lista.sort((a, b) => new Date(a.actualizacion_fecha_creacion).getTime() - new Date(b.actualizacion_fecha_creacion).getTime())
    } else {
      // Recientes
      lista.sort((a, b) => new Date(b.actualizacion_fecha_publicacion || b.actualizacion_fecha_creacion).getTime() - new Date(a.actualizacion_fecha_publicacion || a.actualizacion_fecha_creacion).getTime())
    }

    const perPage = Number(params.get('per_page') || 10)
    const page = Number(params.get('page') || 1)
    const total = lista.length
    const lastPage = Math.max(1, Math.ceil(total / perPage))
    const start = (page - 1) * perPage
    const pagedData = lista.slice(start, start + perPage)

    return crearRespuesta(200, {
      data: pagedData,
      meta: {
        current_page: page,
        last_page: lastPage,
        per_page: perPage,
        total,
      },
    }, config)
  }

  // 5. Crear Actualización
  if (pathname === '/actualizaciones' && method === 'post') {
    const payload = parseRequestBody(config.data)
    const lista = obtenerActualizacionesDemo()
    const nuevoId = lista.length > 0 ? Math.max(...lista.map((x) => Number(x.id))) + 1 : 1

    const areaId = payload.actualizacion_area_servicio_id || payload.area_servicio_id
    const areaEncontrada = DEMO_AREAS.find((a) => Number(a.area_servicio_id) === Number(areaId)) || DEMO_AREAS[0]

    let catIds: number[] = []
    if (Array.isArray(payload.actualizacion_categoria_ids)) {
      catIds = payload.actualizacion_categoria_ids.map(Number)
    } else if (payload.actualizacion_categoria_id) {
      catIds = [Number(payload.actualizacion_categoria_id)]
    } else {
      catIds = [1]
    }

    const categoriasSeleccionadas = DEMO_CATEGORIAS.filter((c) =>
      catIds.includes(Number(c.categoria_actualizacion_id))
    )

    let contenidoParseado: any = payload.actualizacion_contenido || payload.contenido
    if (typeof contenidoParseado === 'string') {
      try {
        contenidoParseado = JSON.parse(contenidoParseado)
      } catch {}
    }

    const contenidoHtmlGenerado = (contenidoParseado && Array.isArray(contenidoParseado.blocks))
      ? generarHtmlDesdeBloques(contenidoParseado.blocks)
      : (typeof payload.contenido === 'string' ? payload.contenido : '<h2>Detalles</h2><p>Contenido creado en modo demo interactivo.</p>')

    const nuevoItem = {
      id: nuevoId,
      actualizacion_titulo: payload.actualizacion_titulo || payload.titulo || 'Nueva Actualización Demo',
      actualizacion_version: payload.actualizacion_version || payload.version || '1.0.0',
      actualizacion_resumen: payload.actualizacion_resumen || payload.resumen || '',
      actualizacion_imagen_destacada: payload.actualizacion_imagen_destacada || payload.imagen_destacada || 'https://images.unsplash.com/photo-1576091160399-112ba8d25d1d?w=1200&auto=format&fit=crop&q=80',
      actualizacion_estado: payload.actualizacion_estado || payload.estado || 'borrador',
      actualizacion_fecha_publicacion: payload.actualizacion_fecha_publicacion || payload.fecha_publicacion || new Date().toISOString(),
      actualizacion_fecha_creacion: new Date().toISOString(),
      actualizacion_area_servicio_id: areaEncontrada.area_servicio_id,
      actualizacion_categoria_id: categoriasSeleccionadas[0]?.categoria_actualizacion_id || 1,
      actualizacion_categoria_ids: catIds,
      actualizacion_imagenes: [],
      actualizacion_lecturas: 0,
      area_servicio: areaEncontrada,
      categoria: categoriasSeleccionadas[0] || DEMO_CATEGORIAS[0],
      categorias: categoriasSeleccionadas.length > 0 ? categoriasSeleccionadas : [DEMO_CATEGORIAS[0]],
      actualizacion_contenido: contenidoParseado,
      actualizacion_contenido_html: payload.actualizacion_contenido_html || payload.contenido_html || contenidoHtmlGenerado,
      actualizacion_usuario_id_autor: 1,
      usuario_autor: USUARIO_DEMO.usuario,
    }

    lista.unshift(nuevoItem)
    guardarActualizacionesDemo(lista)

    return crearRespuesta(201, {
      success: true,
      message: 'Actualización guardada correctamente en la demo.',
      data: nuevoItem,
    }, config)
  }

  // 6. Detalle de Actualización /actualizaciones/:id
  const matchDetalle = pathname.match(/^\/actualizaciones\/(\d+)$/)
  if (matchDetalle) {
    const id = Number(matchDetalle[1])
    const lista = obtenerActualizacionesDemo()
    const item = lista.find((x) => Number(x.id) === id)

    if (method === 'get') {
      if (!item) return crearRespuesta(404, { message: 'Publicación no encontrada.' }, config)
      return crearRespuesta(200, { data: item }, config)
    }

    if (method === 'put' || method === 'patch') {
      const payload = parseRequestBody(config.data)
      if (!item) return crearRespuesta(404, { message: 'Publicación no encontrada.' }, config)

      let contenidoParseado: any = payload.actualizacion_contenido || payload.contenido
      if (typeof contenidoParseado === 'string') {
        try {
          contenidoParseado = JSON.parse(contenidoParseado)
        } catch {}
      }

      Object.assign(item, {
        actualizacion_titulo: payload.actualizacion_titulo || payload.titulo || item.actualizacion_titulo,
        actualizacion_version: payload.actualizacion_version || payload.version || item.actualizacion_version,
        actualizacion_resumen: payload.actualizacion_resumen || payload.resumen || item.actualizacion_resumen,
        actualizacion_imagen_destacada: payload.actualizacion_imagen_destacada || payload.imagen_destacada || item.actualizacion_imagen_destacada,
        actualizacion_estado: payload.actualizacion_estado || payload.estado || item.actualizacion_estado,
        actualizacion_contenido: contenidoParseado !== undefined ? contenidoParseado : item.actualizacion_contenido,
      })

      if (contenidoParseado && Array.isArray(contenidoParseado.blocks)) {
        item.actualizacion_contenido_html = generarHtmlDesdeBloques(contenidoParseado.blocks)
      }

      // Si el autor corrigió un registro que estaba en revisión:
      if (item.actualizacion_estado === 'revision' || payload.es_correccion) {
        item.actualizacion_estado = 'publicado'
        item.revision_observacion = null
        const obsLista = obtenerObservacionesDemo().filter((o) => Number(o.actualizacion_id) !== id)
        guardarObservacionesDemo(obsLista)
      }

      guardarActualizacionesDemo(lista)
      return crearRespuesta(200, { success: true, message: 'Actualización editada y corrección enviada con éxito.', data: item }, config)
    }
  }

  // 7. Acciones sobre actualización (estado, revisión, visualización)
  const matchAccion = pathname.match(/^\/actualizaciones\/(\d+)\/(visualizacion|estado|revision|activar|inactivar)$/)
  if (matchAccion && method === 'post') {
    const id = Number(matchAccion[1])
    const accion = matchAccion[2]
    const lista = obtenerActualizacionesDemo()
    const item = lista.find((x) => Number(x.id) === id)

    if (accion === 'visualizacion') {
      if (item) {
        item.actualizacion_lecturas = (item.actualizacion_lecturas || 0) + 1
        guardarActualizacionesDemo(lista)
      }
      return crearRespuesta(200, { success: true }, config)
    }

    if (accion === 'estado') {
      const payload = parseRequestBody(config.data)
      if (item && payload.estado) {
        item.actualizacion_estado = payload.estado
        guardarActualizacionesDemo(lista)
      }
      return crearRespuesta(200, { success: true, message: `Estado cambiado a ${payload.estado}.` }, config)
    }

    if (accion === 'revision') {
      const payload = parseRequestBody(config.data)
      if (item) {
        if (payload.accion === 'aprobar') {
          item.actualizacion_estado = 'publicado'
          item.revision_observacion = null
        } else {
          // El supervisor marca como revisión y envía observación de corrección
          const motivo = payload.observacion || 'Favor revisar el contenido y corregir los puntos indicados antes de publicar.'
          item.actualizacion_estado = 'revision'
          item.revision_observacion = {
            id: Date.now(),
            observacion: motivo,
            created_at: new Date().toISOString(),
          }

          // Guardar en observaciones pendientes para el autor
          const obsLista = obtenerObservacionesDemo().filter((o) => Number(o.actualizacion_id) !== item.id)
          obsLista.unshift({
            id: Date.now(),
            observacion: motivo,
            estado_anterior: 'publicado',
            estado_nuevo: 'revision',
            created_at: new Date().toISOString(),
            actualizacion_id: item.id,
            actualizacion: {
              id: item.id,
              actualizacion_titulo: item.actualizacion_titulo,
              actualizacion_estado: 'revision',
              actualizacion_version: item.actualizacion_version,
            },
          })
          guardarObservacionesDemo(obsLista)

          // Agregar notificación al autor
          const notifs = obtenerNotificacionesDemo()
          notifs.unshift({
            id: Date.now(),
            tipo: 'observacion',
            titulo: 'Revisión requerida por supervisor',
            mensaje: `Observación en "${item.actualizacion_titulo}": ${motivo}`,
            data: { ruta_sugerida: { name: 'mis-registros-show', params: { id: item.id } } },
            leida_en: null,
            created_at: new Date().toISOString(),
            actualizacion_id: item.id,
            actualizacion: {
              id: item.id,
              actualizacion_titulo: item.actualizacion_titulo,
              actualizacion_estado: 'revision',
              actualizacion_version: item.actualizacion_version,
            },
          })
          guardarNotificacionesDemo(notifs)
        }
        guardarActualizacionesDemo(lista)
      }
      return crearRespuesta(200, { success: true, message: 'Corrección y observación enviada exitosamente al autor.' }, config)
    }

    if (accion === 'activar' && item) {
      item.actualizacion_estado = 'publicado'
      guardarActualizacionesDemo(lista)
      return crearRespuesta(200, { success: true, message: 'Publicación activada.' }, config)
    }

    if (accion === 'inactivar' && item) {
      item.actualizacion_estado = 'inactivo'
      guardarActualizacionesDemo(lista)
      return crearRespuesta(200, { success: true, message: 'Publicación inactivada.' }, config)
    }
  }

  // 8. Resumen con IA simulado
  if (pathname === '/actualizaciones/generar-resumen' && method === 'post') {
    const payload = parseRequestBody(config.data)
    const titulo = payload.titulo || 'esta actualización'
    const resumenIA = `Resumen con IA para "${titulo}": Se implementan mejoras críticas de seguridad, optimización en flujos asistenciales e integración con servicios clínicos clave para maximizar la eficiencia operativa.`
    return crearRespuesta(200, { resumen: resumenIA }, config)
  }

  // 9. Subida de imágenes simulada
  if (pathname.includes('subir-imagen')) {
    const mockImg = 'https://images.unsplash.com/photo-1576091160399-112ba8d25d1d?w=1200&auto=format&fit=crop&q=80'
    return crearRespuesta(200, {
      success: 1,
      url: mockImg,
      file: { url: mockImg },
      ruta: mockImg,
    }, config)
  }

  // 10. Bookmarks
  if (pathname === '/bookmarks/ids') {
    return crearRespuesta(200, { data: obtenerBookmarksDemo() }, config)
  }

  if (pathname === '/bookmarks' && method === 'get') {
    const bookmarkIds = obtenerBookmarksDemo()
    const todos = obtenerActualizacionesDemo()
    const guardados = todos.filter((x) => bookmarkIds.includes(Number(x.id)))
    return crearRespuesta(200, { data: guardados }, config)
  }

  const matchBookmark = pathname.match(/^\/bookmarks\/(\d+)$/)
  if (matchBookmark) {
    const id = Number(matchBookmark[1])
    let bookmarks = obtenerBookmarksDemo()

    if (method === 'post') {
      if (!bookmarks.includes(id)) {
        bookmarks.push(id)
        guardarBookmarksDemo(bookmarks)
      }
      return crearRespuesta(200, { success: true, message: 'Artículo guardado en favoritos.' }, config)
    }

    if (method === 'delete') {
      bookmarks = bookmarks.filter((x) => x !== id)
      guardarBookmarksDemo(bookmarks)
      return crearRespuesta(200, { success: true, message: 'Artículo eliminado de favoritos.' }, config)
    }
  }

  // 11. Notificaciones
  if (pathname === '/notificaciones' && method === 'get') {
    const notifs = obtenerNotificacionesDemo()
    const noLeidas = notifs.filter((n) => !n.leida_en).length
    return crearRespuesta(200, {
      data: notifs,
      meta: {
        no_leidas: noLeidas,
        current_page: 1,
        last_page: 1,
        total: notifs.length,
      },
    }, config)
  }

  if (pathname === '/notificaciones/contador') {
    const notifs = obtenerNotificacionesDemo()
    const noLeidas = notifs.filter((n) => !n.leida_en).length
    return crearRespuesta(200, { data: { no_leidas: noLeidas } }, config)
  }

  if (pathname === '/notificaciones/observaciones') {
    const obs = obtenerObservacionesDemo()
    return crearRespuesta(200, { data: obs }, config)
  }

  const matchNotifLeida = pathname.match(/^\/notificaciones\/(\d+)\/leida$/)
  if (matchNotifLeida && method === 'post') {
    const id = Number(matchNotifLeida[1])
    const notifs = obtenerNotificacionesDemo()
    const n = notifs.find((x) => x.id === id)
    if (n) {
      n.leida_en = new Date().toISOString()
      guardarNotificacionesDemo(notifs)
    }
    return crearRespuesta(200, { success: true }, config)
  }

  if (pathname === '/notificaciones/marcar-todas-leidas' && method === 'post') {
    const notifs = obtenerNotificacionesDemo()
    notifs.forEach((n) => {
      n.leida_en = n.leida_en || new Date().toISOString()
    })
    guardarNotificacionesDemo(notifs)
    return crearRespuesta(200, { success: true }, config)
  }

  // 12. Dashboard
  if (pathname === '/blog-dashboard') {
    return crearRespuesta(200, { data: DEMO_DASHBOARD_DATA }, config)
  }

  // 13. Historial de lecturas
  if (pathname === '/mi-historial-lecturas') {
    const todos = obtenerActualizacionesDemo().slice(0, 4)
    const historial = todos.map((item, idx) => ({
      actualizacion_id: item.id,
      actualizacion_titulo: item.actualizacion_titulo,
      actualizacion_resumen: item.actualizacion_resumen,
      ultima_visualizacion: new Date(Date.now() - (idx + 1) * 3600 * 1000 * 6).toISOString(),
      veces_visualizado: 5 - idx,
    }))
    return crearRespuesta(200, { data: historial }, config)
  }

  // 14. Observaciones pendientes
  if (pathname === '/observaciones-pendientes/contador') {
    const obs = obtenerObservacionesDemo()
    return crearRespuesta(200, { data: { pendientes: obs.length } }, config)
  }

  if (pathname.includes('observaciones-pendientes')) {
    const obs = obtenerObservacionesDemo()
    return crearRespuesta(200, { data: obs, meta: { total: obs.length } }, config)
  }

  // Fallback genérico
  return crearRespuesta(200, { data: [], message: 'OK' }, config)
}

function crearRespuesta(status: number, data: any, config: InternalAxiosRequestConfig): AxiosResponse {
  return {
    data,
    status,
    statusText: status === 200 ? 'OK' : 'Created',
    headers: { 'content-type': 'application/json' },
    config,
  }
}

function parseRequestBody(data: any): Record<string, any> {
  if (!data) return {}

  // Extraer campos si data es instancia de FormData (enviado desde NewVersion)
  if (typeof FormData !== 'undefined' && data instanceof FormData) {
    const result: Record<string, any> = {}
    data.forEach((val, key) => {
      if (key.endsWith('[]')) {
        const cleanKey = key.slice(0, -2)
        if (!result[cleanKey]) result[cleanKey] = []
        result[cleanKey].push(val)
      } else {
        result[key] = val
      }
    })
    return result
  }

  if (typeof data === 'object') return data
  try {
    return JSON.parse(data)
  } catch {
    return {}
  }
}

function generarHtmlDesdeBloques(blocks: any[]): string {
  if (!Array.isArray(blocks)) return ''
  return blocks.map((b) => {
    if (b.type === 'header') {
      const level = b.data?.level || 2
      return `<h${level}>${b.data?.text || ''}</h${level}>`
    }
    if (b.type === 'paragraph') {
      return `<p>${b.data?.text || ''}</p>`
    }
    if (b.type === 'list') {
      const tag = b.data?.style === 'ordered' ? 'ol' : 'ul'
      const items = (b.data?.items || []).map((it: any) => `<li>${typeof it === 'string' ? it : it.content || ''}</li>`).join('')
      return `<${tag}>${items}</${tag}>`
    }
    return ''
  }).join('\n')
}
