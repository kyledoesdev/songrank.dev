@props(['content'])

@php
    $groups = [
        ['key' => 'rankings', 'title' => 'Rankings', 'icon' => 'fa-ranking-star', 'types' => App\Enums\RankingType::cases(), 'prefix' => ''],
        ['key' => 'tierlists', 'title' => 'Tier Lists', 'icon' => 'fa-layer-group', 'types' => App\Enums\TierlistType::cases(), 'prefix' => 'by '],
        ['key' => 'reviews', 'title' => 'Reviews', 'icon' => 'fa-pen-nib', 'types' => App\Enums\ReviewType::cases(), 'prefix' => ''],
    ];
@endphp

<section class="py-16 px-4">
    <div class="max-w-6xl mx-auto">
        <div class="text-center mb-12">
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900">{{ $content->get('rank-types-title') }}</h2>
            <p class="mt-3 text-lg text-gray-800/60">{{ $content->get('rank-types-subtitle') }}</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            @foreach ($groups as $group)
                <div class="bg-white/15 backdrop-blur-sm rounded-2xl p-6 md:p-8 border border-white/20 shadow-lg">
                    <h3 class="flex items-center gap-2 text-xl font-bold text-gray-900 mb-6">
                        <i class="fa-solid {{ $group['icon'] }}"></i>
                        {{ $group['title'] }}
                    </h3>

                    <ul class="space-y-5">
                        @foreach ($group['types'] as $type)
                            <li class="flex gap-4 items-start">
                                <div @class([
                                    'shrink-0 w-10 h-10 rounded-xl flex items-center justify-center',
                                    'bg-purple-400/20 text-purple-600' => $type->color() === 'purple',
                                    'bg-green-400/20 text-green-600' => $type->color() === 'green',
                                    'bg-blue-400/20 text-blue-600' => $type->color() === 'blue',
                                ])>
                                    <i class="fa-solid {{ $type->icon() }}"></i>
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-900">{{ $group['prefix'] }}{{ $type->label() }}</p>
                                    <p class="text-sm text-gray-800/60">{{ $content->get("rank-types-{$group['key']}-{$type->value}") }}</p>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </div>
    </div>
</section>
