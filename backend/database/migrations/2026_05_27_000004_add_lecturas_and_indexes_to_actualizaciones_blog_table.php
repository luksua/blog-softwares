<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('actualizaciones_blog', 'actualizacion_lecturas')) {
            Schema::table('actualizaciones_blog', function (Blueprint $table) {
                $table->unsignedBigInteger('actualizacion_lecturas')
                    ->default(0)
                    ->after('actualizacion_estado');
            });
        }

        $this->crearIndiceSiNoExiste('actualizaciones_blog', 'abl_estado_idx', function (Blueprint $table) {
            $table->index('actualizacion_estado', 'abl_estado_idx');
        });

        $this->crearIndiceSiNoExiste('actualizaciones_blog', 'abl_area_estado_fecha_idx', function (Blueprint $table) {
            $table->index([
                'actualizacion_area_servicio_id',
                'actualizacion_estado',
                'actualizacion_fecha_publicacion',
            ], 'abl_area_estado_fecha_idx');
        });

        $this->crearIndiceSiNoExiste('actualizaciones_blog', 'abl_autor_estado_idx', function (Blueprint $table) {
            $table->index([
                'actualizacion_usuario_id_autor',
                'actualizacion_estado',
            ], 'abl_autor_estado_idx');
        });

        $this->crearIndiceSiNoExiste('actualizaciones_blog', 'abl_categoria_idx', function (Blueprint $table) {
            $table->index('actualizacion_categoria_id', 'abl_categoria_idx');
        });

        $this->crearIndiceSiNoExiste('actualizaciones_blog', 'abl_fecha_creacion_idx', function (Blueprint $table) {
            $table->index('actualizacion_fecha_creacion', 'abl_fecha_creacion_idx');
        });

        $this->crearIndiceSiNoExiste('actualizaciones_blog', 'abl_lecturas_idx', function (Blueprint $table) {
            $table->index('actualizacion_lecturas', 'abl_lecturas_idx');
        });
    }

    public function down(): void
    {
        foreach ([
            'abl_lecturas_idx',
            'abl_fecha_creacion_idx',
            'abl_categoria_idx',
            'abl_autor_estado_idx',
            'abl_area_estado_fecha_idx',
            'abl_estado_idx',
        ] as $indice) {
            if ($this->existeIndice('actualizaciones_blog', $indice)) {
                Schema::table('actualizaciones_blog', function (Blueprint $table) use ($indice) {
                    $table->dropIndex($indice);
                });
            }
        }

        if (Schema::hasColumn('actualizaciones_blog', 'actualizacion_lecturas')) {
            Schema::table('actualizaciones_blog', function (Blueprint $table) {
                $table->dropColumn('actualizacion_lecturas');
            });
        }
    }

    private function crearIndiceSiNoExiste(string $tabla, string $indice, callable $callback): void
    {
        if ($this->existeIndice($tabla, $indice)) {
            return;
        }

        Schema::table($tabla, $callback);
    }

    private function existeIndice(string $tabla, string $indice): bool
    {
        return count(DB::select("SHOW INDEX FROM {$tabla} WHERE Key_name = ?", [$indice])) > 0;
    }
};
