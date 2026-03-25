<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */

    public function up()
    {
        Schema::create('imagenes_actualizaciones', function (Blueprint $table) {
            $table->id();

            $table->integer('actualizacion_id');

            $table->string('ruta_imagen'); // Ruta donde guardaremos el archivo
            $table->timestamps();

            // tabla ya hecha de actualizaciones
            $table->foreign('actualizacion_id')
                ->references('id')
                ->on('actualizaciones_blog')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('imagenes_actualizaciones');
    }
};
