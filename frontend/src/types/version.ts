
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
}

export interface ImagenGaleria {
  id: number;
  ruta_imagen: string;
}