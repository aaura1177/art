<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMXPath;

class SupplierTermsHtmlSanitizer
{
    /** @var string[] */
    private static $allowedTags = [
        'p', 'br', 'hr', 'strong', 'b', 'em', 'i', 'u', 'sub', 'sup',
        'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
        'ul', 'ol', 'li', 'blockquote',
        'table', 'thead', 'tbody', 'tfoot', 'tr', 'th', 'td', 'caption',
        'div', 'span', 'figure',
    ];

    /**
     * Strip scripts/styles; keep safe tags; remove unsafe attributes.
     */
    public static function sanitize(string $html): string
    {
        $html = trim($html);
        if ($html === '') {
            return '';
        }

        $html = preg_replace('#<script\b[^>]*>.*?</script>#is', '', $html);
        $html = preg_replace('#<style\b[^>]*>.*?</style>#is', '', $html);

        $allowed = '<' . implode('><', self::$allowedTags) . '>';
        $html = strip_tags($html, $allowed);

        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $wrapped = '<?xml encoding="UTF-8"><div id="supplier-terms-sanitize-root">' . $html . '</div>';
        $dom->loadHTML($wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $root = $dom->getElementById('supplier-terms-sanitize-root');
        if (!$root) {
            return htmlspecialchars(strip_tags($html), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }

        $xp = new DOMXPath($dom);
        foreach ($xp->query('//*') as $el) {
            if (!($el instanceof DOMElement)) {
                continue;
            }
            if (strtolower($el->tagName) === 'figure') {
                self::unwrapElement($el);

                continue;
            }
            $names = [];
            foreach (iterator_to_array($el->attributes ?? []) as $attr) {
                $names[] = $attr->name;
            }
            foreach ($names as $name) {
                $el->removeAttribute($name);
            }
        }

        $out = '';
        foreach (iterator_to_array($root->childNodes) as $child) {
            $out .= $dom->saveHTML($child);
        }

        return trim($out);
    }

    private static function unwrapElement(DOMElement $el): void
    {
        $parent = $el->parentNode;
        if (!$parent) {
            return;
        }
        while ($el->firstChild) {
            $parent->insertBefore($el->firstChild, $el);
        }
        $parent->removeChild($el);
    }
}
