@props(['title', 'icon', 'entries', 'countLabel'])

<x-card class="p-4">
    <h3 class="text-lg text-center k-line font-semibold mb-4">
        <i class="fa-solid {{ $icon }} text-primary-icon mr-1"></i>
        {{ $title }}
    </h3>

    {{-- Podium: #2 | #1 | #3 --}}
    <div class="flex items-end justify-center gap-3 mb-6">
        @foreach ([2, 1, 3] as $place)
            @php $entry = $entries->get($place - 1); @endphp

            @if ($entry)
                @php
                    $medal = match ($place) {
                        1 => 'medal-gold',
                        2 => 'medal-silver',
                        3 => 'medal-bronze',
                    };
                    $size = $place === 1 ? 'w-24 h-24' : 'w-16 h-16';
                @endphp

                <div
                    class="flex flex-col items-center w-1/3 min-w-0 {{ $place === 1 ? 'pb-4' : '' }} {{ $entry['url'] ? 'cursor-pointer' : '' }}"
                    @if ($entry['url']) onclick="window.location.href='{{ $entry['url'] }}'" @endif
                >
                    @if ($entry['image'])
                        <img
                            src="{{ $entry['image'] }}"
                            alt="{{ $entry['name'] }}"
                            class="{{ $size }} rounded-xl object-cover border border-zinc-200 shadow-sm"
                        >
                    @else
                        <div class="{{ $size }} rounded-xl border border-zinc-200 shadow-sm bg-primary-muted flex items-center justify-center">
                            <i class="fa-solid {{ $icon }} text-primary-icon"></i>
                        </div>
                    @endif

                    <span class="w-6 h-6 mt-2 rounded-full {{ $medal }} text-xs font-bold flex items-center justify-center shadow">
                        {{ $place }}
                    </span>

                    <span class="mt-1 text-sm font-semibold truncate w-full text-center" title="{{ $entry['name'] }}">
                        {{ $entry['name'] }}
                    </span>

                    @if ($entry['subtitle'] ?? null)
                        <span class="text-[11px] text-zinc-400 truncate w-full text-center">
                            by {{ $entry['subtitle'] }}
                        </span>
                    @endif

                    <span class="text-xs text-zinc-500">
                        {{ $entry['count'] }} {{ $countLabel }}
                    </span>
                </div>
            @endif
        @endforeach
    </div>

    {{-- Places 4-10 --}}
    @if ($entries->count() > 3)
        <ul class="space-y-2">
            @foreach ($entries->slice(3) as $index => $entry)
                <li>
                    <div
                        class="flex items-center gap-3 p-2 rounded-md hover:bg-gray-100 {{ $entry['url'] ? 'cursor-pointer' : '' }}"
                        @if ($entry['url']) onclick="window.location.href='{{ $entry['url'] }}'" @endif
                    >
                        <span class="w-8 h-8 shrink-0 rounded-full bg-primary-accent flex items-center justify-center text-sm">
                            {{ $index + 1 }}
                        </span>

                        @if ($entry['image'])
                            <img
                                src="{{ $entry['image'] }}"
                                alt="{{ $entry['name'] }}"
                                class="w-8 h-8 shrink-0 rounded-lg object-cover border border-zinc-200"
                            >
                        @else
                            <div class="w-8 h-8 shrink-0 rounded-lg border border-zinc-200 bg-primary-muted flex items-center justify-center">
                                <i class="fa-solid {{ $icon }} text-xs text-primary-icon"></i>
                            </div>
                        @endif

                        <span class="flex-1 min-w-0">
                            <span class="block truncate text-sm" title="{{ $entry['name'] }}">
                                {{ $entry['name'] }}
                            </span>

                            @if ($entry['subtitle'] ?? null)
                                <span class="block text-[11px] text-zinc-400 truncate">
                                    by {{ $entry['subtitle'] }}
                                </span>
                            @endif
                        </span>

                        <span class="text-xs text-zinc-500 whitespace-nowrap">
                            {{ $entry['count'] }} {{ $countLabel }}
                        </span>
                    </div>
                </li>
            @endforeach
        </ul>
    @endif
</x-card>
