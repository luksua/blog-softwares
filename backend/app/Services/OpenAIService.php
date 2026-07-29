<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class OpenAIService
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
     * Genera un resumen breve y profesional a partir del texto plano
     * de una publicación del blog.
     */
    public function generarResumen(string $contenidoTexto, string $titulo = '', int $maxCaracteres = 800): string
    {
        $systemPrompt = <<<PROMPT
Eres un asistente que redacta resúmenes claros y profesionales para el blog interno
de Asotrauma (institución del sector salud). Lees el contenido de una publicación y
generas un resumen breve, objetivo y bien redactado en español, que sirve como
"resumen" o "extracto" de la publicación en el listado del blog.

Reglas estrictas:
- Máximo {$maxCaracteres} caracteres.
- Un solo párrafo, sin viñetas, sin títulos, sin markdown, sin comillas.
- No inventes información que no esté en el contenido proporcionado.
- Tono institucional, directo y claro.
- No repitas el título de forma literal al inicio del resumen.
- Devuelve únicamente el texto del resumen, nada más.
PROMPT;

        $mensajeUsuario = $titulo !== ''
            ? "Título de la publicación: {$titulo}\n\nContenido:\n\n{$contenidoTexto}\n\nGenera el resumen."
            : "Contenido:\n\n{$contenidoTexto}\n\nGenera el resumen.";

        $respuesta = Http::withToken($this->apiKey)
            ->timeout(60)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => $this->model,
                'temperature' => 0.4,
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $mensajeUsuario],
                ],
            ]);

        if ($respuesta->failed()) {
            Log::error('Error al generar resumen con OpenAI', [
                'status' => $respuesta->status(),
                'body' => $respuesta->body(),
            ]);

            throw new RuntimeException('No se pudo generar el resumen con IA. Intenta nuevamente.');
        }

        $texto = trim((string) ($respuesta->json('choices.0.message.content') ?? ''));

        if ($texto === '') {
            throw new RuntimeException('La IA no devolvió ningún resumen.');
        }

        // Quitar comillas envolventes si la IA las agrega
        $texto = trim($texto, " \t\n\r\0\x0B\"'");

        if (mb_strlen($texto) > $maxCaracteres) {
            $texto = mb_substr($texto, 0, $maxCaracteres - 1) . '…';
        }

        return $texto;
    }
}