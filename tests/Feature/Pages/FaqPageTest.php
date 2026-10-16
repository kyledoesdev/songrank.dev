<?php

use App\Livewire\Faq as FaqPage;
use App\Models\Faq;
use Livewire\Livewire;

use function Pest\Laravel\get;

describe('faq page', function () {
    test('displays active faqs', function () {
        $faq = Faq::factory()->create([
            'text' => '<p>Select an artist and start comparing!</p>',
            'is_active' => true,
        ]);

        get(route('faq'))
            ->assertOk()
            ->assertSee('wire:key="faq-'.$faq->getKey().'"', escape: false)
            ->assertSee('<p>Select an artist and start comparing!</p>', escape: false);
    });

    test('does not display inactive faqs', function () {
        Faq::query()->forceDelete();

        $active = Faq::factory()->create();
        Faq::factory()->inactive()->create();

        expect(Livewire::test(FaqPage::class)->instance()->faqs->modelKeys())->toBe([$active->getKey()]);
    });

    test('displays faqs in order', function () {
        $second = Faq::factory()->create(['order' => 2]);
        $first = Faq::factory()->create(['order' => 1]);

        Livewire::test(FaqPage::class)->assertSeeHtmlInOrder([
            'wire:key="faq-'.$first->getKey().'"',
            'wire:key="faq-'.$second->getKey().'"',
        ]);
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
