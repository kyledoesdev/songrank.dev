<?php

namespace Database\Seeders;

use App\Enums\Billing\Plan;
use App\Models\PlanFeature;
use Illuminate\Database\Seeder;

class PlanFeatureSeeder extends Seeder
{
    /**
     * @var array<string, list<array{label: string, detail?: string, is_included?: bool, is_coming_soon?: bool}>>
     */
    protected array $features = [
        'free' => [
            ['label' => '{rankings} rankings'],
            ['label' => '{tierlists} tier lists of each type', 'detail' => 'Up to {tierlist_entries} entries on each'],
            ['label' => '{reviews} reviews'],
            ['label' => 'Share, embed, comment on and export your lists'],
            ['label' => 'Rearrange tier lists after publishing', 'is_included' => false],
            ['label' => 'Edit reviews after publishing', 'is_included' => false],
            ['label' => '{catalog_folders} catalog folders', 'detail' => 'Organize your rankings, tier lists and reviews', 'is_coming_soon' => true],
            ['label' => 'Subfolders in your catalog', 'is_included' => false, 'is_coming_soon' => true],
            ['label' => 'Earn experience', 'is_coming_soon' => true],
        ],
        'pro' => [
            ['label' => '{rankings} rankings'],
            ['label' => '{tierlists} tier lists', 'detail' => 'Up to {tierlist_entries} entries on each'],
            ['label' => '{reviews} reviews'],
            ['label' => 'Share, embed, comment on and export your lists'],
            ['label' => 'Rearrange tier lists after publishing'],
            ['label' => 'Edit reviews after publishing'],
            ['label' => '{catalog_folders} catalog folders', 'detail' => 'Organize your rankings, tier lists and reviews', 'is_coming_soon' => true],
            ['label' => 'Subfolders in your catalog', 'detail' => '{subfolder_levels} level deep', 'is_coming_soon' => true],
            ['label' => '{experience} experience', 'is_coming_soon' => true],
        ],
    ];

    /**
     * Only seeds a plan that has no features yet, so a re-run never undoes
     * what was arranged in the admin panel.
     */
    public function run(): void
    {
        foreach ($this->features as $plan => $features) {
            if (PlanFeature::withTrashed()->where('plan', $plan)->exists()) {
                continue;
            }

            foreach ($features as $order => $feature) {
                PlanFeature::query()->create([...$feature, 'plan' => Plan::from($plan), 'order' => $order + 1]);
            }
        }
    }
}
