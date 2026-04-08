<?php

namespace App\Services;

class EditorJsRenderer
{
    public function render(?string $json): string
    {
        if (!$json) {
            return '';
        }

        $data = json_decode($json, true);

        if (!is_array($data) || !isset($data['blocks']) || !is_array($data['blocks'])) {
            return '<p class="text-danger">Contenido inválido.</p>';
        }

        $html = '';

        foreach ($data['blocks'] as $block) {
            $type = $block['type'] ?? null;
            $blockData = $block['data'] ?? [];

            switch ($type) {
                case 'paragraph':
                    $text = $this->safeText($blockData['text'] ?? '');
                    $html .= "<p>{$text}</p>";
                    break;

                case 'header':
                    $level = (int) ($blockData['level'] ?? 2);
                    $level = max(1, min(6, $level));
                    $text = $this->safeText($blockData['text'] ?? '');
                    $html .= "<h{$level}>{$text}</h{$level}>";
                    break;

                case 'list':
                    $style = $blockData['style'] ?? 'unordered';
                    $items = $blockData['items'] ?? [];

                    $html .= $this->renderList($items, $style);
                    break;

                case 'quote':
                    $text = $this->safeText($blockData['text'] ?? '');
                    $caption = $this->safeText($blockData['caption'] ?? '');

                    $html .= "<blockquote><p>{$text}</p>";
                    if ($caption) {
                        $html .= "<footer>{$caption}</footer>";
                    }
                    $html .= "</blockquote>";
                    break;

                case 'delimiter':
                    $html .= '<hr>';
                    break;

                case 'image':
                    $url = $blockData['file']['url'] ?? $blockData['url'] ?? null;
                    $caption = $this->safeText($blockData['caption'] ?? '');

                    if ($url) {
                        $safeUrl = e($url);
                        $html .= "<figure>";
                        $html .= "<img src=\"{$safeUrl}\" alt=\"{$caption}\" class=\"img-fluid rounded\">";
                        if ($caption) {
                            $html .= "<figcaption>{$caption}</figcaption>";
                        }
                        $html .= "</figure>";
                    }
                    break;
            }
        }

        return $html;
    }

    private function renderList(array $items, string $style = 'unordered'): string
    {
        $tag = $style === 'ordered' ? 'ol' : 'ul';

        if (empty($items)) {
            return '';
        }

        $html = "<{$tag}>";

        foreach ($items as $item) {
            if (is_string($item)) {
                $html .= '<li>' . $this->safeText($item) . '</li>';
                continue;
            }

            if (is_array($item)) {
                $content = $this->safeText($item['content'] ?? '');
                $children = $item['items'] ?? [];

                $html .= '<li>';
                $html .= $content;

                if (is_array($children) && !empty($children)) {
                    $html .= $this->renderList($children, $style);
                }

                $html .= '</li>';
            }
        }

        $html .= "</{$tag}>";

        return $html;
    }

    private function safeText(string|array|null $text): string
    {
        if (is_array($text)) {
            $text = $text['text'] ?? $text['content'] ?? json_encode($text, JSON_UNESCAPED_UNICODE);
        }

        return e((string) ($text ?? ''));
    }
}