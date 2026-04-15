
export interface NewVersion {
  id?: number | null;
  titulo: string;
  version: string;
  contenido: any;
  resumen: string;
  imagen_destacada: string;
  area_servicio_id: number | null;
  usuario_id_autor: number | null;
  estado: string;
  fecha_creacion: string;
  fecha_publicacion: string;
  imagenes_quill?: string[]; 
}