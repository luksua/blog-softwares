<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('actualizaciones_blog_notificaciones', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('usuario_id_destino');
            $table->unsignedBigInteger('usuario_id_actor')->nullable();
            $table->unsignedBigInteger('actualizacion_id')->nullable();
            $table->string('tipo', 80);
            $table->string('titulo', 255);
            $table->text('mensaje')->nullable();
            $table->json('data')->nullable();
            $table->timestamp('leida_en')->nullable();
            $table->timestamps();

            $table->index(['usuario_id_destino', 'leida_en'], 'abn_usuario_leida_idx');
            $table->index('actualizacion_id', 'abn_actualizacion_idx');
            $table->index('tipo', 'abn_tipo_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('actualizaciones_blog_notificaciones');
    }
};
