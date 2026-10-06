<?php

namespace Database\Seeders;

use App\Models\CrateIcon;
use Illuminate\Database\Seeder;

class CrateIconSeeder extends Seeder
{
    /**
     * Font Awesome loads from a kit, so these stay inside the free solid set.
     *
     * @var list<array{name: string, icon: string}>
     */
    protected array $icons = [
        ['name' => 'Folder', 'icon' => 'fa-folder'],
        ['name' => 'Open Folder', 'icon' => 'fa-folder-open'],
        ['name' => 'Folder Tree', 'icon' => 'fa-folder-tree'],
        ['name' => 'Archive', 'icon' => 'fa-box-archive'],
        ['name' => 'Vinyl', 'icon' => 'fa-record-vinyl'],
        ['name' => 'Compact Disc', 'icon' => 'fa-compact-disc'],
        ['name' => 'Music', 'icon' => 'fa-music'],
        ['name' => 'Headphones', 'icon' => 'fa-headphones'],
        ['name' => 'Microphone', 'icon' => 'fa-microphone'],
        ['name' => 'Guitar', 'icon' => 'fa-guitar'],
        ['name' => 'Star', 'icon' => 'fa-star'],
        ['name' => 'Heart', 'icon' => 'fa-heart'],
        ['name' => 'Fire', 'icon' => 'fa-fire'],
        ['name' => 'Crown', 'icon' => 'fa-crown'],
    ];

    /**
     * Only seeds an empty table, so a re-run never undoes what was arranged in
     * the admin panel.
     */
    public function run(): void
    {
        if (CrateIcon::query()->exists()) {
            return;
        }

        foreach ($this->icons as $position => $icon) {
            CrateIcon::query()->create([...$icon, 'position' => $position]);
        }
    }
}
