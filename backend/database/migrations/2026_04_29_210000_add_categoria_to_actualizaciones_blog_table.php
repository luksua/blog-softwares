<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('actualizaciones_blog', function (Blueprint $table) {
            $table->unsignedBigInteger('actualizacion_categoria_id')->nullable()->after('actualizacion_area_servicio_id');

            $table->foreign('actualizacion_categoria_id', 'fk_actualizaciones_blog_categoria')
                ->references('categoria_actualizacion_id')
                ->on('categorias_actualizacion')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('actualizaciones_blog', function (Blueprint $table) {
            $table->dropForeign('fk_actualizaciones_blog_categoria');
            $table->dropColumn('actualizacion_categoria_id');
        });
    }
};