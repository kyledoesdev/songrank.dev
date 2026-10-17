<?php

namespace App\Actions\Reviews;

use Blaspsoft\Blasp\Facades\Blasp;
use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

/**
 * A review body is rendered unescaped, and the editor hands back HTML the
 * author controls, so only the markup the editor itself produces is kept.
 */
final class CleanReviewBody
{
    private const ELEMENTS = [
        'p', 'br', 'hr',
        'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
        'strong', 'em', 'u', 's', 'mark', 'sub', 'sup',
        'ul', 'ol', 'li', 'blockquote', 'code', 'pre',
    ];

    /**
     * @return array{body: string, body_text: string}
     */
    public function handle(string $html): array
    {
        $body = $this->mask($this->sanitize($html));

        return [
            'body' => $body,
            'body_text' => $this->plainText($body),
        ];
    }

    private function sanitize(string $html): string
    {
        $config = collect(self::ELEMENTS)
            ->reduce(fn (HtmlSanitizerConfig $config, string $element) => $config->allowElement($element), new HtmlSanitizerConfig)
            ->allowElement('a', ['href'])
            ->allowLinkSchemes(['https', 'http', 'mailto'])
            ->forceAttribute('a', 'rel', 'nofollow noopener noreferrer')
            ->forceAttribute('a', 'target', '_blank');

        return (new HtmlSanitizer($config))->sanitize($html);
    }

    /**
     * strip_tags() leaves nothing between block elements, so a heading and the
     * paragraph after it would run together in search and in card excerpts.
     */
    private function plainText(string $html): string
    {
        $spaced = preg_replace('#</(p|h[1-6]|li|blockquote|pre)>|<br\s*/?>#i', '$0 ', $html);

        return trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($spaced))));
    }

    /**
     * Blasp reads tag characters as letter substitutions — `two</p>` comes back
     * as `t*****>` — so it runs on the text between tags, never on the tags.
     */
    private function mask(string $html): string
    {
        return collect(preg_split('/(<[^>]*>)/', $html, -1, PREG_SPLIT_DELIM_CAPTURE))
            ->map(function (string $part): string {
                if (str_starts_with($part, '<') || blank($part)) {
                    return $part;
                }

                return Blasp::english()->mask('*')->check($part)->clean();
            })
            ->implode('');
    }
}
