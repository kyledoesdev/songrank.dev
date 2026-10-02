@props(['content'])

@php
    $modes = [
        ['key' => 'rankings', 'icon' => 'fa-ranking-star', 'color' => 'purple'],
        ['key' => 'tierlists', 'icon' => 'fa-layer-group', 'color' => 'green'],
        ['key' => 'reviews', 'icon' => 'fa-pen-nib', 'color' => 'blue'],
    ];
@endphp

<section class="py-16 px-4">
    <div class="max-w-6xl mx-auto">
        <div class="text-center mb-12">
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900">{{ $content->get('how-it-works-title') }}</h2>
            <p class="mt-3 text-lg text-gray-800/60">{{ $content->get('how-it-works-subtitle') }}</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            @foreach ($modes as $mode)
                <div class="bg-white/15 backdrop-blur-sm rounded-2xl p-8 border border-white/20 shadow-lg text-center hover:bg-white/25 transition-all">
                    <div @class([
                        'w-16 h-16 mx-auto mb-4 rounded-full flex items-center justify-center',
                        'bg-purple-400/20 text-purple-600' => $mode['color'] === 'purple',
                        'bg-green-400/20 text-green-600' => $mode['color'] === 'green',
                        'bg-blue-400/20 text-blue-600' => $mode['color'] === 'blue',
                    ])>
                        <i class="fa-solid {{ $mode['icon'] }} text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">{{ $content->get("how-it-works-{$mode['key']}-title") }}</h3>
                    <p class="text-gray-800/60">{{ $content->get("how-it-works-{$mode['key']}-text") }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>
