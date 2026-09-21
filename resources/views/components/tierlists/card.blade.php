@props(['tierlist'])

@php
    $topTier = $tierlist->tiers->first();
    $topItems = $topTier?->items ?? collect();
@endphp

<div class="bg-white shadow-md rounded-xl cursor-pointer hover:shadow-lg transition-all duration-300 p-4" onclick="window.location.href='{{ route('tierlist', ['id' => $tierlist->getKey()]) }}'">
    <div class="flex gap-5">
        {{-- Top Tier Preview --}}
        <div class="shrink-0">
            <div class="w-24 h-24 sm:w-28 sm:h-28 rounded-xl border border-zinc-200 shadow-sm overflow-hidden bg-zinc-100 grid grid-cols-2 gap-0.5 p-0.5">
                @foreach ($topItems->take(4) as $item)
                    <img
                        src="{{ $item->entryable?->cover() }}"
                        class="w-full h-full object-cover rounded-sm"
                        alt="{{ $item->entryable?->name() }}"
                    >
                @endforeach

                @if ($topItems->count() < 4)
                    @for ($i = $topItems->count(); $i < 4; $i++)
                        <div class="w-full h-full bg-zinc-200 rounded-sm"></div>
                    @endfor
                @endif
            </div>

            @if ($tierlist->source)
                <div class="mt-2 relative z-10">
                    <x-spotify-logo :url="$tierlist->source->spotifyUrl()" />
                </div>
            @endif
        </div>

        {{-- Content --}}
        <div class="flex-1 min-w-0">
            {{-- Title + Badge --}}
            <div class="flex items-center gap-2 flex-wrap mb-1">
                <h5 class="text-base md:text-lg font-bold truncate" title="{{ $tierlist->name }}">
                    {{ Str::limit($tierlist->name, 30) }}
                </h5>
                <span class="inline-flex items-center gap-1 text-[10px] px-2 py-0.5 rounded-full bg-{{ $tierlist->type->color() }}-100 text-{{ $tierlist->type->color() }}-700 whitespace-nowrap">
                    <i class="fa-solid {{ $tierlist->type->icon() }}"></i>
                    {{ $tierlist->type->label() }}
                </span>
            </div>

            {{-- Source Name --}}
            @if ($tierlist->source)
                <p class="text-sm text-zinc-500 mb-3 truncate">
                    {{ $tierlist->source->name() }}
                </p>
            @endif

            {{-- Stats Pills --}}
            <div class="flex flex-wrap gap-2 mb-3">
                {{-- Top Ranked Entry --}}
                @if ($topItems->isNotEmpty())
                    <span class="inline-flex items-center gap-1.5 text-xs bg-zinc-100 text-zinc-600 px-2.5 py-1 rounded-lg">
                        <i class="fa-regular fa-star text-amber-500"></i>
                        {{ Str::limit($topItems->first()->entryable?->name(), 20) }}
                    </span>
                @endif

                {{-- Entry Count --}}
                <span class="inline-flex items-center gap-1.5 text-xs bg-zinc-100 text-zinc-600 px-2.5 py-1 rounded-lg">
                    <i class="fa-solid fa-hashtag text-zinc-400"></i>
                    {{ $tierlist->items_count }} {{ $tierlist->type->itemLabel() }}
                </span>

                {{-- Status (only show if incomplete) --}}
                @unless ($tierlist->is_complete)
                    <span class="inline-flex items-center gap-1.5 text-xs px-2.5 py-1 rounded-lg bg-amber-50 text-amber-700">
                        <i class="fa-solid fa-spinner"></i>
                        In Progress
                    </span>
                @endunless
            </div>

            {{-- Footer: User + Time --}}
            <div class="flex items-center gap-3 text-xs text-zinc-400">
                @if (Route::currentRouteName() != 'profile')
                    <div class="relative z-10">
                        <a href="{{ route('profile', ['id' => $tierlist->user->spotify_id]) }}" class="inline-flex items-center gap-1 hover:text-zinc-600 transition-colors">
                            <i class="fa-regular fa-user"></i>
                            {{ $tierlist->user->name }}
                            <i class="fa fa-arrow-up-right-from-square text-blue-500"></i>
                        </a>
                    </div>
                    <span class="text-zinc-300">|</span>
                @endif
                <span title="{{ $tierlist->formatted_completed_at }}">
                    <i class="fa-regular fa-clock mr-0.5"></i>
                    {{ $tierlist->completed_at }}
                </span>
            </div>
        </div>
    </div>
</div>
