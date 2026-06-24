<?php

use Illuminate\Database\Seeder;
use App\Models\Category;

class CategoriaIconosSeeder extends Seeder
{
    public function run(): void
    {
        $categorias = [
            'Manual de usuario' => 'bi-person-lines-fill',
            'Manual técnico' => 'bi-tools',
            'Instalador' => 'bi-box-arrow-down',
            'Actualización del sistema' => 'bi-arrow-repeat',
            'Nueva funcionalidad' => 'bi-stars',
            'Mejora' => 'bi-arrow-up-circle-fill',
            'Corrección de errores' => 'bi-bug-fill',
            'Parche de seguridad' => 'bi-shield-fill-check',
            'Guía de instalación' => 'bi-journal-arrow-down',
            'Guía rápida' => 'bi-lightning-charge-fill',
            'Documentación' => 'bi-file-earmark-text-fill',
            'Notas de versión' => 'bi-card-list',
            'General' => 'bi-info-circle-fill',
        ];

        foreach ($categorias as $nombre => $icono) {
            Category::where('nombre', $nombre)->update([
                'icono' => $icono,
            ]);
        }
    }
}