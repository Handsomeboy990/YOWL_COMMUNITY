<?php

namespace App\Support;

/**
 * Cleans the HTML produced by the administration editor.
 *
 * The editor writes HTML, and that HTML goes straight into a public page. A
 * compromised or careless administrator account must not be able to place a
 * script, an event handler or a javascript: address on a legal page, so the
 * document is rebuilt from an allow list rather than filtered for known bad
 * patterns: anything not named here simply does not survive.
 */
class RichText
{
    /** @var array<string, array<int, string>> */
    private const ALLOWED = [
        'p' => [], 'br' => [], 'strong' => [], 'em' => [], 'u' => [], 's' => [],
        'h2' => [], 'h3' => [], 'h4' => [],
        'ul' => [], 'ol' => [], 'li' => [],
        'blockquote' => [], 'hr' => [], 'code' => [], 'pre' => [],
        'a' => ['href', 'title'],
        'img' => ['src', 'alt', 'width', 'height'],
        'iframe' => ['src', 'title', 'allow', 'allowfullscreen', 'width', 'height'],
        'figure' => [], 'figcaption' => [],
        'table' => [], 'thead' => [], 'tbody' => [], 'tr' => [], 'th' => [], 'td' => [],
    ];

    /** Hosts a video may be embedded from. */
    private const VIDEO_HOSTS = [
        'www.youtube.com', 'youtube.com', 'www.youtube-nocookie.com',
        'player.vimeo.com', 'www.dailymotion.com', 'geo.dailymotion.com',
    ];

    public static function clean(?string $html): string
    {
        if (! $html || trim($html) === '') {
            return '';
        }

        $document = new \DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);
        $document->loadHTML(
            '<?xml encoding="UTF-8"><div id="racine">'.$html.'</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();

        $root = $document->getElementById('racine');
        if (! $root) {
            return '';
        }

        self::walk($root);

        $out = '';
        foreach ($root->childNodes as $child) {
            $out .= $document->saveHTML($child);
        }

        return trim($out);
    }

    private static function walk(\DOMNode $node): void
    {
        // Copie du tableau : on modifie l'arbre en le parcourant.
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child instanceof \DOMText) {
                continue;
            }

            if (! $child instanceof \DOMElement) {
                $node->removeChild($child);
                continue;
            }

            $tag = strtolower($child->nodeName);

            if (! array_key_exists($tag, self::ALLOWED)) {
                // La balise disparait, son texte reste : supprimer un <span>
                // ne doit pas emporter la phrase qu'il entoure.
                while ($child->firstChild) {
                    $node->insertBefore($child->firstChild, $child);
                }
                $node->removeChild($child);
                continue;
            }

            self::cleanAttributes($child, $tag);
            self::walk($child);
        }
    }

    private static function cleanAttributes(\DOMElement $element, string $tag): void
    {
        foreach (iterator_to_array($element->attributes) as $attribute) {
            if (! in_array(strtolower($attribute->name), self::ALLOWED[$tag], true)) {
                $element->removeAttribute($attribute->name);
            }
        }

        if ($tag === 'a') {
            $href = $element->getAttribute('href');
            if (! self::isSafeUrl($href)) {
                $element->removeAttribute('href');
            } else {
                // Un lien sortant ne doit pas donner la main a la page ouverte.
                $element->setAttribute('target', '_blank');
                $element->setAttribute('rel', 'noopener noreferrer');
            }
        }

        if ($tag === 'img' && ! self::isSafeUrl($element->getAttribute('src'))) {
            $element->parentNode?->removeChild($element);
        }

        if ($tag === 'iframe') {
            $src = $element->getAttribute('src');
            $host = parse_url($src, PHP_URL_HOST);
            if (! self::isSafeUrl($src) || ! in_array($host, self::VIDEO_HOSTS, true)) {
                $element->parentNode?->removeChild($element);

                return;
            }
            // allow-scripts et allow-same-origin ensemble laisseraient la page
            // encadree retirer son propre bac a sable : jamais les deux.
            $element->setAttribute('sandbox', 'allow-scripts allow-popups allow-presentation');
            $element->setAttribute('loading', 'lazy');
            $element->setAttribute('referrerpolicy', 'no-referrer');
        }
    }

    private static function isSafeUrl(string $url): bool
    {
        $url = trim($url);
        if ($url === '') {
            return false;
        }

        // Les adresses relatives et les ancres restent dans le site.
        if (str_starts_with($url, '/') || str_starts_with($url, '#')) {
            return true;
        }

        return in_array(strtolower((string) parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true);
    }
}
