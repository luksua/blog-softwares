
export interface Version {
  id: number;
  titulo: string;
  resumen: string;
  imagen_destacada: string;
  estado: string;
  fecha_publicacion: string;
  imagenes: ImagenGaleria[]; // El arreglo que nos devuelve el "with('imagenes')" de Laravel
}

export interface ImagenGaleria {
  id: number;
  ruta_imagen: string;
}