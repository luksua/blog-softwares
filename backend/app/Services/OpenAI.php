<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class OpenAI
{
    private string $apiKey;
    private string $model;

    public function __construct()
    {
        $this->apiKey = (string) config('services.openai.key', '');
        $this->model = (string) config('services.openai.model', 'gpt-4o-mini');

        if (empty($this->apiKey)) {
            throw new RuntimeException('OPENAI_API_KEY no está configurada.');
        }
    }

    /**
     * Genera un resumen breve y profesional a partir del contenido
     * de una publicación del blog.
     */
    public function generarResumen(
        string $contenidoTexto,
        string $titulo = '',
        int $maxCaracteres = 800
    ): string {

        $systemPrompt = <<<PROMPT
                Eres un asistente que redacta resúmenes claros y profesionales para un blog interno de una institución del sector salud.

                Lee el contenido de una publicación y genera un resumen breve, objetivo y bien redactado en español.

                Reglas:
                - Máximo {$maxCaracteres} caracteres.
                - Un solo párrafo.
                - Sin viñetas.
                - Sin títulos.
                - Sin markdown.
                - Sin comillas.
                - No inventes información que no aparezca en el contenido.
                - Utiliza un tono institucional, directo y claro.
                - No repitas el título literalmente al inicio.
                - Devuelve únicamente el texto del resumen.
                PROMPT;

        $mensajeUsuario = $titulo !== ''
            ? "Título de la publicación:\n{$titulo}\n\nContenido:\n{$contenidoTexto}\n\nGenera el resumen."
            : "Contenido:\n{$contenidoTexto}\n\nGenera el resumen.";

        try {
            $respuesta = Http::withToken($this->apiKey)
                ->acceptJson()
                ->timeout(60)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => $this->model,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => $systemPrompt,
                        ],
                        [
                            'role' => 'user',
                            'content' => $mensajeUsuario,
                        ],
                    ],
                ]);

            if ($respuesta->failed()) {
                Log::error('Error al generar resumen con OpenAI', [
                    'status' => $respuesta->status(),
                    'body' => $respuesta->body(),
                ]);

                throw new RuntimeException(
                    'OpenAI respondió con un error HTTP ' . $respuesta->status()
                );
            }

            $texto = trim(
                (string) $respuesta->json('choices.0.message.content', '')
            );

            if ($texto === '') {
                Log::error('OpenAI no devolvió contenido', [
                    'response' => $respuesta->json(),
                ]);

                throw new RuntimeException(
                    'La IA no devolvió ningún resumen.'
                );
            }

            // Eliminar comillas envolventes
            $texto = trim(
                $texto,
                " \t\n\r\0\x0B\"'"
            );

            // Garantizar el máximo de caracteres
            if (mb_strlen($texto) > $maxCaracteres) {
                $texto = mb_substr(
                    $texto,
                    0,
                    $maxCaracteres - 1
                ) . '…';
            }

            return $texto;
        } catch (\Illuminate\Http\Client\ConnectionException $e) {

            Log::error('Error de conexión con OpenAI', [
                'message' => $e->getMessage(),
            ]);

            throw new RuntimeException(
                'No fue posible conectarse con el servicio de IA.'
            );
        } catch (\Throwable $e) {

            Log::error('Error inesperado en OpenAIService', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            throw $e;
        }
    }
}
