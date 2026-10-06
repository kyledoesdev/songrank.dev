@props(['content'])

@php
    $items = [
        ['key' => 'explore', 'icon' => 'fa-globe'],
        ['key' => 'share', 'icon' => 'fa-share-nodes'],
        ['key' => 'embed', 'icon' => 'fa-code'],
        ['key' => 'comment', 'icon' => 'fa-comments'],
        ['key' => 'react', 'icon' => 'fa-face-smile'],
    ];
@endphp

<section class="py-16 px-4">
    <div class="max-w-6xl mx-auto">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 items-center">
            <div>
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-5">{{ $content->get('community-title') }}</h2>
                <div class="space-y-5">
                    @foreach ($items as $item)
                        <div class="flex gap-3 items-start">
                            <div class="shrink-0 w-8 h-8 rounded-lg bg-white/20 flex items-center justify-center">
                                <i class="fa-solid {{ $item['icon'] }} text-sm text-gray-900"></i>
                            </div>
                            <div>
                                <h4 class="text-sm font-bold text-gray-900">{{ $content->get("community-{$item['key']}-title") }}</h4>
                                <p class="text-sm text-gray-800/60">{{ $content->get("community-{$item['key']}-text") }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="rounded-2xl border border-white/10 shadow-xl overflow-hidden">
                <img src="{{ asset('images/ranking.gif') }}" alt="{{ config('app.name') }} ranking demo" class="w-full h-auto">
            </div>
        </div>
    </div>
</section>
