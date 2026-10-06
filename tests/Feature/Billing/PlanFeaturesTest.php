<?php

use App\Enums\Billing\Plan;
use App\Models\PlanFeature;

describe('placeholders', function () {
    it('fills each limit from the billing config', function () {
        config([
            'billing.ranking_limits.free' => 250,
            'billing.tierlist_limits.free' => ['artist' => 4, 'album' => 4, 'track' => 4],
            'billing.tierlist_item_limits.free' => 80,
            'billing.review_limits.free' => 1,
            'billing.catalog_limits.free' => ['folders' => 8, 'depth' => 1],
        ]);

        expect(Plan::FREE->fillPlaceholders('{rankings} / {tierlists} / {tierlist_entries} / {reviews} / {catalog_folders}'))
            ->toBe('250 / 4 / 80 / 1 / 8');
    });

    it('calls a null limit unlimited', function () {
        expect(Plan::PRO->fillPlaceholders('{rankings} rankings, {tierlists} tier lists, {reviews} reviews'))
            ->toBe('Unlimited rankings, Unlimited tier lists, Unlimited reviews');
    });

    it('gives the largest tier list limit when the types differ', function () {
        config(['billing.tierlist_limits.free' => ['artist' => 3, 'album' => 5, 'track' => 2]]);

        expect(Plan::FREE->fillPlaceholders('{tierlists} | {tierlists.artist} | {tierlists.track}'))->toBe('Up to 5 | 3 | 2');
    });

    it('counts subfolder levels below the top level', function () {
        config(['billing.catalog_limits.pro' => ['folders' => null, 'depth' => 3]]);

        expect(Plan::PRO->fillPlaceholders('{subfolder_levels}'))->toBe('2');
    });

    it('names the experience rate', function () {
        expect(Plan::PRO->fillPlaceholders('{experience} experience'))->toBe('Double experience');

        config(['billing.experience_multipliers.pro' => 5]);

        expect(Plan::PRO->fillPlaceholders('{experience} experience'))->toBe('5x experience');
    });

    it('leaves text without placeholders alone', function () {
        expect(Plan::FREE->fillPlaceholders('Edit reviews after publishing'))->toBe('Edit reviews after publishing');
    });
});

describe('the seeded features', function () {
    it('reflect the billing config on each card', function () {
        config(['billing.ranking_limits.free' => 200]);

        $free = PlanFeature::cached()->get('free')->map->renderedLabel();
        $pro = PlanFeature::cached()->get('pro')->map->renderedLabel();

        expect($free)->toContain('200 rankings', '3 tier lists of each type', '5 reviews', '5 catalog folders');
        expect($pro)->toContain('Unlimited rankings', 'Unlimited tier lists', 'Double experience');
        expect(PlanFeature::cached()->get('pro')->firstWhere('label', '{tierlists} tier lists')->renderedDetail())->toBe('Up to 500 entries on each');
    });
});

describe('card order', function () {
    it('lists checks, then crosses, then anything coming soon', function () {
        PlanFeature::query()->forceDelete();

        $soon = PlanFeature::factory()->comingSoon()->create(['order' => 1]);
        $excluded = PlanFeature::factory()->excluded()->create(['order' => 2]);
        $soonExcluded = PlanFeature::factory()->comingSoon()->excluded()->create(['order' => 3]);
        $second = PlanFeature::factory()->create(['order' => 5]);
        $first = PlanFeature::factory()->create(['order' => 4]);

        expect(PlanFeature::cached()->get('free')->modelKeys())->toBe([
            $first->getKey(),
            $second->getKey(),
            $excluded->getKey(),
            $soon->getKey(),
            $soonExcluded->getKey(),
        ]);
    });

    it('puts a new feature at the end of its plan', function () {
        $last = PlanFeature::query()->where('plan', Plan::PRO)->max('order');

        expect(PlanFeature::factory()->pro()->create()->order)->toBe($last + 1);
    });
});

describe('price', function () {
    it('shows the Pro price from the billing config', function () {
        config(['billing.pro.amount' => 1500]);

        expect(Plan::PRO->price())->toBe('$15.00');
        expect(Plan::FREE->price())->toBe('$0.00');
    });
});
