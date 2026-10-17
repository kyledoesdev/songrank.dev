<?php

use App\Models\Review;
use App\Models\Tierlist;
use Illuminate\Support\Facades\Log;

use function Pest\Laravel\artisan;
use function Pest\Laravel\travelTo;

beforeEach(function () {
    travelTo(now()->setDate(2026, 10, 2)->setTime(6, 0));
});

describe('tier list stats', function () {
    it('counts tier lists started, deleted and completed yesterday', function () {
        $yesterday = now()->subDay()->setTime(12, 0);

        Tierlist::factory()->count(3)->create(['created_at' => $yesterday]);
        Tierlist::factory()->complete()->create(['created_at' => $yesterday, 'completed_at' => $yesterday]);
        Tierlist::factory()->create(['created_at' => $yesterday, 'deleted_at' => $yesterday]);
        Tierlist::factory()->create(['created_at' => now()->subDays(3)]);

        expect(digestMessage())
            ->toContain('Tier Lists Started: 4.')
            ->toContain('Tier Lists Deleted: 1.')
            ->toContain('Tier Lists Completed: 1.');
    });
});

describe('review stats', function () {
    it('counts reviews started, deleted and published yesterday', function () {
        $yesterday = now()->subDay()->setTime(12, 0);

        Review::factory()->count(2)->create(['created_at' => $yesterday]);
        Review::factory()->published()->create(['created_at' => $yesterday, 'published_at' => $yesterday]);
        Review::factory()->create(['created_at' => $yesterday, 'deleted_at' => $yesterday]);
        Review::factory()->published()->create(['created_at' => now()->subDays(3), 'published_at' => now()->subDays(3)]);

        expect(digestMessage())
            ->toContain('Reviews Started: 3.')
            ->toContain('Reviews Deleted: 1.')
            ->toContain('Reviews Completed: 1.');
    });
});

function digestMessage(): string
{
    $message = '';

    Log::shouldReceive('channel')->with('discord_other_updates')->andReturnSelf();
    Log::shouldReceive('info')->andReturnUsing(function (string $logged) use (&$message) {
        $message = $logged;
    });

    artisan('daily-digest:send')->assertSuccessful();

    return $message;
}
