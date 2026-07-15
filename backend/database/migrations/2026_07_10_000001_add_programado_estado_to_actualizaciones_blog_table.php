<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLA = 'actualizaciones_blog';
    private const COLUMNA = 'actualizacion_estado';
    private const NUEVO_ESTADO = 'programado';

    public function up(): void
    {
        if (! Schema::hasColumn(self::TABLA, self::COLUMNA)) {
            return;
        }

        $tipoColumna = $this->obtenerTipoColumna();

        // Si la columna es un ENUM y no incluye 'programado', se amplía.
        if ($tipoColumna && str_starts_with($tipoColumna, 'enum(') && ! str_contains($tipoColumna, "'" . self::NUEVO_ESTADO . "'")) {
            $valoresActuales = $this->extraerValoresEnum($tipoColumna);
            $valoresActuales[] = self::NUEVO_ESTADO;

            $listaEnum = implode(',', array_map(
                fn (string $valor) => "'" . addslashes($valor) . "'",
                array_unique($valoresActuales)
            ));

            DB::statement(sprintf(
                'ALTER TABLE `%s` MODIFY `%s` ENUM(%s) NOT NULL DEFAULT \'borrador\'',
                self::TABLA,
                self::COLUMNA,
                $listaEnum
            ));
        }

        // Si es varchar/string, no se requiere ningún cambio de esquema.
    }

    public function down(): void
    {
        // No se revierte el ensanchamiento del enum para evitar perder datos
        // de registros que ya hayan quedado en estado 'programado'.
    }

    private function obtenerTipoColumna(): ?string
    {
        $fila = DB::selectOne(
            'SELECT COLUMN_TYPE as tipo FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            [self::TABLA, self::COLUMNA]
        );

        return $fila?->tipo;
    }

    private function extraerValoresEnum(string $tipoColumna): array
    {
        preg_match('/^enum\((.*)\)$/i', $tipoColumna, $coincidencias);

        if (! isset($coincidencias[1])) {
            return [];
        }

        preg_match_all("/'((?:[^'\\\\]|\\\\.)*)'/", $coincidencias[1], $valores);

        return array_map(
            fn (string $valor) => stripslashes($valor),
            $valores[1] ?? []
        );
    }
};
