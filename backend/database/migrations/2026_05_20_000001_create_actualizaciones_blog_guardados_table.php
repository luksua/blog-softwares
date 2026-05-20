<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('actualizaciones_blog_guardados', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('usuario_id');
            $table->unsignedBigInteger('actualizacion_id');
            $table->timestamps();

            $table->unique(
                ['usuario_id', 'actualizacion_id'],
                'actualizaciones_guardados_usuario_actualizacion_unique'
            );

            $table->index('usuario_id', 'actualizaciones_guardados_usuario_id_index');
            $table->index('actualizacion_id', 'actualizaciones_guardados_actualizacion_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('actualizaciones_blog_guardados');
    }
};
