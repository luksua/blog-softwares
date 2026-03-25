<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('actualizaciones_blog', function (Blueprint $table) {
            //
            // $table->renameColumn('titulo', 'actualizacion_titulo');
            // $table->renameColumn('contenido', 'actualizacion_contenido');
            // $table->renameColumn('resumen', 'actualizacion_resumen');
            // $table->renameColumn('imagen_destacada', 'actualizacion_imagen_destacada');
            // $table->renameColumn('area_servicio_id', 'actualizacion_area_servicio_id');
            // $table->renameColumn('usuario_id_autor', 'actualizacion_usuario_id_autor');
            // $table->renameColumn('estado', 'actualizacion_estado');
            // $table->renameColumn('fecha_publicacion', 'actualizacion_fecha_publicacion');
            // $table->renameColumn('fecha_creacion', 'actualizacion_fecha_creacion');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('actualizaciones_blog', function (Blueprint $table) {
            //
            $table->renameColumn('actualizacion_titulo', 'titulo');
            $table->renameColumn('actualizacion_contenido', 'contenido');
            $table->renameColumn('actualizacion_resumen', 'resumen');
            $table->renameColumn('actualizacion_imagen_destacada', 'imagen_destacada');
            $table->renameColumn('actualizacion_area_servicio_id', 'area_servicio_id');
            $table->renameColumn('actualizacion_usuario_id_autor', 'usuario_id_autor');
            $table->renameColumn('actualizacion_estado', 'estado');
            $table->renameColumn('actualizacion_fecha_publicacion', 'fecha_publicacion');
            $table->renameColumn('actualizacion_fecha_creacion', 'fecha_creacion');
        });
    }
};
