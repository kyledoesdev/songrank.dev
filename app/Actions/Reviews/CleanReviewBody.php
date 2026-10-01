<?php

namespace App\Actions\Reviews;

use Blaspsoft\Blasp\Facades\Blasp;
use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

/**
 * A review body is rendered unescaped, and the editor hands back HTML the
 * author controls, so it is run through an allowlist before it is stored.
 */
final class CleanReviewBody
{
    /**
     * @return array{body: ?string, body_text: ?string}
     */
    public function handle(?string $html): array
    {
        if (blank($html)) {
            return ['body' => null, 'body_text' => null];
        }

        $body = $this->mask($this->sanitize($html));
        $text = trim(preg_replace('/\s+/u', ' ', strip_tags($body)));

        return [
            'body' => blank($text) ? null : $body,
            'body_text' => blank($text) ? null : $text,
        ];
    }

    private function sanitize(string $html): string
    {
        $config = (new HtmlSanitizerConfig)
            ->allowElement('p')
            ->allowElement('br')
            ->allowElement('strong')
            ->allowElement('b')
            ->allowElement('em')
            ->allowElement('i')
            ->allowElement('u')
            ->allowElement('s')
            ->allowElement('h2')
            ->allowElement('h3')
            ->allowElement('ul')
            ->allowElement('ol')
            ->allowElement('li')
            ->allowElement('blockquote')
            ->allowElement('code')
            ->allowElement('pre')
            ->allowElement('a', ['href'])
            ->allowLinkSchemes(['https', 'http', 'mailto'])
            ->forceAttribute('a', 'rel', 'nofollow noopener noreferrer')
            ->forceAttribute('a', 'target', '_blank');

        return (new HtmlSanitizer($config))->sanitize($html);
    }

    private function mask(string $html): string
    {
        return Blasp::language('english')
            ->maskWith('*')
            ->check($html)
            ->getCleanString();
    }
}
