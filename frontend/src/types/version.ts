export interface Version {
  id: number;
  actualizacion_titulo: string;
  actualizacion_version: string;
  actualizacion_resumen: string;
  actualizacion_imagen_destacada: string;
  actualizacion_estado: string;
  actualizacion_fecha_publicacion: string;
  actualizacion_fecha_creacion: string;
  actualizacion_imagenes: ImagenGaleria[]; // El arreglo que nos devuelve el "with('imagenes')" de Laravel
  area_servicio: AreaServicio;
  categoria: Category;
}

export interface ImagenGaleria {
  id: number;
  ruta_imagen: string;
}

export interface AreaServicio {
  id?: number;
  area_servicio_nombre: string;
}

export interface Category {
  id?: number;
  categoria_actualizacion_nombre: string;
}