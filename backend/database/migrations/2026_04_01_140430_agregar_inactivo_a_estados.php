<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
public function up(): void
    {
        Schema::table('actualizaciones_blog', function (Blueprint $table) {
            // Declaramos la columna con el nuevo arreglo de opciones y aplicamos change()
            $table->enum('actualizacion_estado', ['borrador', 'revision', 'publicado', 'inactivo'])
                  ->default('borrador')
                  ->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('actualizaciones_blog', function (Blueprint $table) {
            // Si hacemos un rollback, quitamos 'inactivo' del arreglo
            $table->enum('actualizacion_estado', ['borrador', 'revision', 'publicado'])
                  ->default('borrador')
                  ->change();
        });
    }
};
