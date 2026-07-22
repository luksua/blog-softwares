<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'actualizaciones_blog_visualizaciones',
            function (Blueprint $table) {
                $table->id();
                $table->integer('actualizacion_id');
                $table->unsignedBigInteger('usuario_id');
                $table->unsignedBigInteger('area_servicio_id')->nullable();

                $table->timestamp('visualizado_at')->useCurrent();

                $table->string('ip', 45)->nullable();
                $table->text('user_agent')->nullable();

                $table->index('actualizacion_id');
                $table->index('usuario_id');
                $table->index('area_servicio_id');
                $table->index('visualizado_at');

                $table
                    ->foreign('actualizacion_id')
                    ->references('id')
                    ->on('actualizaciones_blog')
                    ->cascadeOnDelete();
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('actualizaciones_blog_visualizaciones');
    }
};
