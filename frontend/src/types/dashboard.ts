export interface AlcanceDashboard {
  tipo: string
  areas: number[]
  descripcion: string
}

export interface UsuarioMasRegistros {
  usuario_id: number
  usuario: string
  total: number
}

export interface ResumenDashboard {
  total_registros: number
  publicados: number
  revision: number
  borradores: number
  programados: number
  inactivos: number
}

export interface EmpleadoMasActivo {
  usuario_id: number
  usuario: string
  total_visualizaciones: number
  registros_vistos: number
  ultima_visualizacion: string | null
}

export interface RegistroPorEstado {
  estado: string
  total: number
}

export interface AreaMasMencionada {
  area_servicio_id: number | null
  area: string
  total: number
  autores: number
}

export interface RegistroPorArea {
  area_servicio_id: number | null
  area: string
  total: number
  pendientes_revision?: number
}

export interface RegistroMasLeido {
  id: number
  titulo: string
  lecturas: number
  area_servicio_id: number | null
}

export interface BlogDashboardData {
  alcance: AlcanceDashboard
  resumen: ResumenDashboard
  registros_mas_leidos_area: RegistroMasLeido[]
  usuarios_mas_registros: UsuarioMasRegistros[]
  registros_por_estado: RegistroPorEstado[]
  registros_por_area: RegistroPorArea[]
  programados_proximos: RegistroProgramado[]
  empleados_mas_activos: EmpleadoMasActivo[]
  lecturas_disponibles: boolean
  areas_mas_mencionadas: AreaMasMencionada[]
}

export interface RegistroProgramado {
  id: number
  titulo: string
  fecha_publicacion: string
  area: string
}
