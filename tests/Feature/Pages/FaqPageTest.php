<?php

use App\Models\Faq;

use function Pest\Laravel\get;

describe('faq page', function () {
    test('displays active faqs', function () {
        Faq::factory()->create([
            'question' => 'How do I rank songs?',
            'text' => '<p>Select an artist and start comparing!</p>',
            'is_active' => true,
        ]);

        get(route('faq'))
            ->assertOk()
            ->assertSee('How do I rank songs?')
            ->assertSee('Select an artist and start comparing!');
    });

    test('does not display inactive faqs', function () {
        Faq::factory()->inactive()->create([
            'question' => 'Hidden question',
        ]);

        get(route('faq'))
            ->assertOk()
            ->assertDontSee('Hidden question');
    });

    test('displays faqs in order', function () {
        Faq::factory()->create(['question' => 'Second question', 'order' => 2]);
        Faq::factory()->create(['question' => 'First question', 'order' => 1]);

        $content = get(route('faq'))->assertOk()->getContent();

        expect(strpos($content, 'First question'))->toBeLessThan(strpos($content, 'Second question'));
    });

    test('shows an empty state when no faqs exist', function () {
        Faq::query()->forceDelete();

        get(route('faq'))
            ->assertOk()
            ->assertDontSee('wire:key="faq-', escape: false);
    });
});

describe('faq model', function () {
    test('slug is auto-generated from question on create', function () {
        $faq = Faq::factory()->create([
            'question' => 'How do I rank songs?',
        ]);

        expect($faq->slug)->toBe('how-do-i-rank-songs?');
    });
});
