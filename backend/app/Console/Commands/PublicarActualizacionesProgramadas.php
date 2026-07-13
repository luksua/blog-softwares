<?php

namespace App\Console\Commands;

use App\Models\BlogNotification;
use App\Models\UpdateBlog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PublicarActualizacionesProgramadas extends Command
{
    /**
     * Nombre y firma del comando.
     */
    protected $signature = 'blog:publicar-programados';

    /**
     * Descripción del comando.
     */
    protected $description = 'Publica automáticamente los registros del blog cuyo estado es "programado" y cuya fecha/hora de publicación ya se cumplió.';

    public function handle(): int
    {
        $ahora = now();

        $pendientes = UpdateBlog::query()
            ->where('actualizacion_estado', 'programado')
            ->whereNotNull('actualizacion_fecha_publicacion')
            ->where('actualizacion_fecha_publicacion', '<=', $ahora)
            ->get();

        if ($pendientes->isEmpty()) {
            $this->info('No hay registros programados pendientes de publicar.');

            return self::SUCCESS;
        }

        $publicados = 0;

        foreach ($pendientes as $actualizacion) {
            try {
                DB::transaction(function () use ($actualizacion) {
                    $actualizacion->actualizacion_estado = 'publicado';
                    $actualizacion->actualizacion_version =
                        (string) (((int) $actualizacion->actualizacion_version) + 1);
                    $actualizacion->save();

                    $this->notificarAutor($actualizacion);
                });

                $publicados++;
            } catch (\Throwable $e) {
                Log::error('Error al publicar registro programado del blog.', [
                    'actualizacion_id' => $actualizacion->id,
                    'error' => $e->getMessage(),
                ]);

                $this->error("No se pudo publicar el registro #{$actualizacion->id}: {$e->getMessage()}");
            }
        }

        $this->info("Registros publicados automáticamente: {$publicados}.");

        return self::SUCCESS;
    }

    private function notificarAutor(UpdateBlog $actualizacion): void
    {
        $autorId = (int) $actualizacion->actualizacion_usuario_id_autor;

        if ($autorId <= 0) {
            return;
        }

        BlogNotification::create([
            'usuario_id_destino' => $autorId,
            // No hay un usuario "actor" real (la publicación la dispara el
            // scheduler), así que se usa el propio autor para no violar
            // una eventual FK NOT NULL en usuario_id_actor.
            'usuario_id_actor' => $autorId,
            'actualizacion_id' => $actualizacion->id,
            'tipo' => 'publicacion_programada',
            'titulo' => 'Registro publicado automáticamente',
            'mensaje' => sprintf(
                'El registro "%s" se publicó automáticamente según la fecha programada.',
                $actualizacion->actualizacion_titulo
            ),
            'data' => [
                'estado_anterior' => 'programado',
                'estado_nuevo' => 'publicado',
                'ruta_sugerida' => [
                    'name' => 'mis-registros-show',
                    'params' => [
                        'id' => $actualizacion->id,
                    ],
                ],
            ],
        ]);
    }
}
