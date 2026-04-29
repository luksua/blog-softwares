<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('act_categorias', function (Blueprint $table) {
            $table->id('categoria_actualizacion_id');
            $table->string('categoria_actualizacion_nombre', 100);
            $table->boolean('categoria_actualizacion_activa')->default(true);
            $table->integer('categoria_actualizacion_orden')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('act_actualizacion');
    }
};