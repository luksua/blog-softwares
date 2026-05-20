<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('actualizaciones_blog_revision_observaciones', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('actualizacion_id');
            $table->unsignedBigInteger('usuario_id_supervisor');
            $table->text('observacion');
            $table->string('estado_anterior', 50)->nullable();
            $table->string('estado_nuevo', 50)->default('revision');
            $table->timestamps();

            $table->index('actualizacion_id', 'abr_obs_actualizacion_idx');
            $table->index('usuario_id_supervisor', 'abr_obs_supervisor_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('actualizaciones_blog_revision_observaciones');
    }
};
