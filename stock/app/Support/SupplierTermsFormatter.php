<?php

namespace App\Support;

use Illuminate\Support\Str;

class SupplierTermsFormatter
{
    /**
     * Render stored body for web / PDF. Plain text: section splits on ⸻. Editor HTML: sanitized + normalized.
     */
    public static function toHtml(?string $body): string
    {
        if ($body === null || trim($body) === '') {
            return '';
        }

        $body = trim(str_replace(["\r\n", "\r"], "\n", $body));

        if (self::isStoredHtml($body)) {
            $inner = SupplierTermsHtmlSanitizer::sanitize($body);
            $inner = self::normalizeEditorHtml($inner);

            return '<div class="terms-stored-html">' . $inner . '</div>';
        }

        if (self::looksLikeMarkdown($body)) {
            $normalized = preg_replace('/^\s*⸻\s*$/m', "\n\n---\n\n", $body);

            return '<div class="terms-stored-html">' . Str::markdown($normalized, [
                'html_input' => 'strip',
                'allow_unsafe_links' => false,
            ]) . '</div>';
        }

        $body = preg_replace('/^\s*(?:⸻|—{2,}|-{3,}|_{3,})\s*$/mu', "⸻", $body);

        $parts = preg_split('/^\s*⸻\s*$/mu', $body);
        $sections = [];
        foreach ($parts as $part) {
            $part = trim($part);
            if ($part !== '') {
                $sections[] = $part;
            }
        }

        $html = '<div class="terms-stored-html">';
        foreach ($sections as $i => $part) {
            $class = 'terms-section';
            if ($i === 0) {
                $class .= ' terms-section--lead';
            }
            $html .= '<section class="' . $class . '">' . nl2br(e($part)) . '</section>';
            if ($i < count($sections) - 1) {
                $html .= '<hr class="terms-separator" />';
            }
        }
        $html .= '</div>';

        return $html;
    }

    /**
     * Clean CKEditor / pasted HTML for consistent display (page, modal, PDF).
     */
    public static function normalizeEditorHtml(string $html): string
    {
        $html = trim($html);
        if ($html === '') {
            return '';
        }

        $html = preg_replace('#<figure[^>]*>\s*(<table\b.*?</table>)\s*</figure>#is', '$1', $html);
        $html = preg_replace('#</?figure[^>]*>#i', '', $html);

        $html = preg_replace('/<p>(?:\s|&nbsp;|<br\s*\/?>)*<\/p>/iu', '', $html);
        $html = preg_replace('/<p>(?:\s|&nbsp;)*(?:<br\s*\/?>\s*)+/iu', '<p>', $html);
        $html = preg_replace('/(?:<br\s*\/?>\s*)+(<\/p>)/iu', '$1', $html);
        $html = preg_replace('/<strong>(?:\s|&nbsp;)+/iu', '<strong>', $html);
        $html = preg_replace('/(?:\s|&nbsp;)+<\/strong>/iu', '</strong>', $html);

        $html = preg_replace(
            '/<p>\s*(?:<br\s*\/?>\s*)*<strong>(\d+\.\s+[^<]+)<\/strong>\s*(?:<br\s*\/?>)?/iu',
            '<h2 class="terms-doc-section">$1</h2><p>',
            $html
        );
        $html = preg_replace(
            '/<br\s*\/?>\s*<strong>(\d+\.\s+[^<]+)<\/strong>/iu',
            '<h2 class="terms-doc-section">$1</h2>',
            $html
        );
        $html = preg_replace(
            '/<p>\s*<strong>(\d+)\.<\/strong>\s*<strong>([^<]+)<\/strong>/iu',
            '<h2 class="terms-doc-section">$1. $2</h2><p>',
            $html
        );

        $html = preg_replace('/<\/p>\s*<\/p>/iu', '</p>', $html);
        $html = preg_replace('/<p>\s*<\/p>/iu', '', $html);
        $html = preg_replace('/<p>\s*<br\s*\/?>\s*/iu', '<p>', $html);

        if (preg_match('/<p\b/i', $html)) {
            $html = preg_replace('/<p>/i', '<p class="terms-doc-header">', $html, 1);
        }

        $html = preg_replace_callback(
            '/<p class="terms-doc-header">(.*?)<\/p>/is',
            function (array $m) {
                $inner = preg_replace('/(?:&nbsp;|(?<=\s)\s)+/u', ' ', $m[1]);
                $inner = preg_replace('/\s*<br\s*\/?>\s*/iu', '<br>', $inner);

                return '<p class="terms-doc-header">' . trim($inner) . '</p>';
            },
            $html,
            1
        );

        return trim($html);
    }

    public static function toPlainTextForApi(?string $body): string
    {
        if ($body === null || trim($body) === '') {
            return '';
        }

        if (!self::isStoredHtml($body)) {
            return trim(str_replace(["\r\n", "\r"], "\n", $body));
        }

        $html = str_replace(["\r\n", "\r"], "\n", $body);
        $html = preg_replace('#<br\s*/?>#i', "\n", $html);
        $html = preg_replace('#</(p|div|h[1-6]|blockquote|caption)\b[^>]*>#i', "\n", $html);
        $html = preg_replace('#</th>#i', "\t", $html);
        $html = preg_replace('#</td>#i', "\t", $html);
        $html = preg_replace('#</tr>#i', "\n", $html);
        $html = preg_replace('#</(table|thead|tbody|tfoot)\b[^>]*>#i', "\n", $html);
        $html = preg_replace('#</li>\s*#i', "\n", $html);
        $html = preg_replace('#<li\b[^>]*>\s*#i', '• ', $html);

        $plain = strip_tags($html);
        $plain = html_entity_decode($plain, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $plain = preg_replace("/[ \t]+\n/", "\n", $plain);
        $plain = preg_replace("/\n{3,}/", "\n\n", $plain);

        return trim($plain);
    }

    public static function isStoredHtml(?string $body): bool
    {
        if ($body === null || trim($body) === '') {
            return false;
        }

        return (bool) preg_match('/<(p|div|h[1-6]|ul|ol|table|blockquote|figure)\b/i', $body);
    }

    private static function looksLikeMarkdown(string $body): bool
    {
        return (bool) preg_match('/^#{1,6}\s/m', $body)
            || (bool) preg_match('/\n#{1,6}\s/', $body);
    }
}
