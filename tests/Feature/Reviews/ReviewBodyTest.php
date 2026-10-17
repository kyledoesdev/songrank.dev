<?php

use App\Actions\Reviews\CleanReviewBody;

describe('what the editor produces', function () {
    it('keeps every heading level', function () {
        $body = cleanBody('<h1>One</h1><h2>Two</h2><h3>Three</h3><h4>Four</h4><h5>Five</h5><h6>Six</h6>');

        expect($body)->toBe('<h1>One</h1><h2>Two</h2><h3>Three</h3><h4>Four</h4><h5>Five</h5><h6>Six</h6>');
    });

    it('keeps inline formatting', function () {
        $html = '<p><strong>bold</strong> <em>italic</em> <s>struck</s> <u>under</u> <mark>marked</mark> <code>code</code></p>';

        expect(cleanBody($html))->toBe($html);
    });

    it('keeps lists, quotes and rules', function () {
        $html = '<ul><li><p>one</p></li></ul><ol><li><p>two</p></li></ol><blockquote><p>quoted</p></blockquote><hr />';

        expect(cleanBody($html))->toBe($html);
    });

    it('keeps a link but opens it safely in a new tab', function () {
        $body = cleanBody('<p><a href="https://example.com">a link</a></p>');

        expect($body)->toContain('href="https://example.com"')
            ->toContain('rel="nofollow noopener noreferrer"')
            ->toContain('target="_blank"');
    });
});

describe('what it strips', function () {
    it('drops scripts and styles', function () {
        $body = cleanBody('<p>fine</p><script>alert(1)</script><style>p { color: red }</style>');

        expect($body)->toBe('<p>fine</p>')
            ->not->toContain('alert');
    });

    it('drops images and embeds', function () {
        $body = cleanBody('<p>fine</p><img src="https://example.com/x.png"><iframe src="https://example.com"></iframe>');

        expect($body)->toBe('<p>fine</p>');
    });

    it('drops event handlers and inline styles', function () {
        $body = cleanBody('<p onclick="alert(1)" style="color: red" class="x">fine</p>');

        expect($body)->toBe('<p>fine</p>');
    });

    it('drops a javascript link target', function () {
        $body = cleanBody('<p><a href="javascript:alert(1)">click</a></p>');

        expect($body)->not->toContain('javascript');
    });
});

describe('masking profanity', function () {
    it('masks words in the text', function () {
        expect(cleanBody('<p>this is a fucking great record</p>'))
            ->toBe('<p>this is a ******* great record</p>');
    });

    it('never touches the markup around them', function () {
        $body = cleanBody('<h2>fucking</h2><p><a href="https://example.com/fucking-review">fucking link</a></p>');

        expect($body)->toContain('<h2>*******</h2>')
            ->toContain('href="https://example.com/fucking-review"')
            ->toContain('>******* link</a>');
    });

    it('leaves clean text alone', function () {
        expect(cleanBody('<p>a perfectly fine record</p>'))->toBe('<p>a perfectly fine record</p>');
    });
});

describe('the plain text copy', function () {
    it('keeps a space between blocks', function () {
        $result = (new CleanReviewBody)->handle('<h2>Verdict</h2><p>Great.</p><ul><li><p>one</p></li><li><p>two</p></li></ul>');

        expect($result['body_text'])->toBe('Verdict Great. one two');
    });

    it('decodes entities', function () {
        $result = (new CleanReviewBody)->handle('<p>Tom &amp; Jerry &quot;live&quot;</p>');

        expect($result['body_text'])->toBe('Tom & Jerry "live"');
    });

    it('is masked like the body', function () {
        $result = (new CleanReviewBody)->handle('<p>fucking great</p>');

        expect($result['body_text'])->toBe('******* great');
    });
});

function cleanBody(string $html): string
{
    return (new CleanReviewBody)->handle($html)['body'];
}
