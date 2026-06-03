<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('actualizaciones_blog_categorias', function (Blueprint $table) {
            $table->id();

            // IMPORTANTE:
            // Debe tener el mismo tipo que actualizaciones_blog.id
            $table->integer('actualizacion_id');

            // Esta sí queda BIGINT porque act_categorias.categoria_actualizacion_id viene de $table->id()
            $table->unsignedBigInteger('categoria_actualizacion_id');

            $table->timestamps();

            $table->unique(
                ['actualizacion_id', 'categoria_actualizacion_id'],
                'abc_actualizacion_categoria_unique'
            );

            $table->foreign('actualizacion_id', 'abc_actualizacion_fk')
                ->references('id')
                ->on('actualizaciones_blog')
                ->cascadeOnDelete();

            $table->foreign('categoria_actualizacion_id', 'abc_categoria_fk')
                ->references('categoria_actualizacion_id')
                ->on('act_categorias')
                ->cascadeOnDelete();
        });

        DB::table('actualizaciones_blog')
            ->whereNotNull('actualizacion_categoria_id')
            ->orderBy('id')
            ->select('id', 'actualizacion_categoria_id')
            ->chunkById(500, function ($actualizaciones) {
                $ahora = now();

                $filas = $actualizaciones
                    ->map(fn ($actualizacion) => [
                        'actualizacion_id' => $actualizacion->id,
                        'categoria_actualizacion_id' => $actualizacion->actualizacion_categoria_id,
                        'created_at' => $ahora,
                        'updated_at' => $ahora,
                    ])
                    ->all();

                if (! empty($filas)) {
                    DB::table('actualizaciones_blog_categorias')->insertOrIgnore($filas);
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('actualizaciones_blog_categorias');
    }
};