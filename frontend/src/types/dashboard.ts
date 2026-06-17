export interface AlcanceDashboard {
  tipo: string
  areas: number[]
  descripcion: string
}

export interface ResumenDashboard {
  total_registros: number
  publicados: number
  revision: number
  borradores: number
}

export interface RegistroPorEstado {
  estado: string
  total: number
}

export interface UsuarioMasRegistros {
  usuario_id: number
  usuario: string
  total: number
}

export interface RegistroMasLeido {
  id: number
  titulo: string
  lecturas: number
  area_servicio_id: number | null
  area: string
  total: number
  pendientes_revision: number
}

export interface BlogDashboardData {
  alcance: AlcanceDashboard
  resumen: ResumenDashboard
  registros_mas_leidos_area: RegistroMasLeido[]
  usuarios_mas_registros: UsuarioMasRegistros[]
  registros_por_estado: RegistroPorEstado[]
  lecturas_disponibles: boolean
}