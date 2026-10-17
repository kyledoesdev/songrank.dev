<?php

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Features\SupportTesting\Testable;

/**
 * The payloads a component handed to resources/js/alerts.js on its last
 * request — `flash` for messages, `confirm` for yes/no prompts.
 *
 * @return Collection<int, array<string, mixed>>
 */
function alertsFrom(Testable $component, string $handler = 'flash'): Collection
{
    return collect($component->effects['xjs'] ?? [])
        ->pluck('expression')
        ->filter(fn (string $expression) => Str::startsWith($expression, "window.{$handler}("))
        ->map(fn (string $expression) => json_decode(Str::between($expression, "window.{$handler}(", ');'), true))
        ->values();
}
