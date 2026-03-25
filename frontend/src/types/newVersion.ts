
export interface NewVersion {
  id?: number | null;
  titulo: string;
  contenido: string;
  resumen: string;
  imagen_destacada: string;
  area_servicio_id: number | null;
  usuario_id_autor: number | null;
  estado: string;
  fecha_publicacion: string;
}