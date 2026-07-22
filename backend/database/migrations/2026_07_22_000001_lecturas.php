<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('actualizaciones_blog_lecturas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('actualizacion_id');
            $table->unsignedInteger('usuario_id');
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['actualizacion_id', 'usuario_id']); // una fila por lector, no por cada refresh
            $table->index('usuario_id');
            $table->index('actualizacion_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('actualizaciones_blog_lecturas');
    }
};