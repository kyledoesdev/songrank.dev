@props(['tierlist'])

@php
    $topItem = $tierlist->tiers->first()->items->first();
@endphp

<x-card class="h-full cursor-pointer hover:shadow-lg transition-all duration-300 p-4" onclick="window.location.href='{{ route('tierlist', ['id' => $tierlist->getKey()]) }}'">
    <div class="flex gap-4">
        <x-tierlists.mini-board :tierlist="$tierlist" class="shrink-0 w-28 sm:w-32" />

        {{-- Content --}}
        <div class="flex-1 min-w-0">
            {{-- Title + Badge --}}
            <div class="flex items-center gap-2 flex-wrap mb-2">
                <h5 class="text-base md:text-lg font-bold truncate" title="{{ $tierlist->name }}">
                    {{ Str::limit($tierlist->name, 30) }}
                </h5>
                <span class="inline-flex items-center gap-1 text-[10px] px-2 py-0.5 rounded-full bg-{{ $tierlist->type->color() }}-100 text-{{ $tierlist->type->color() }}-700 whitespace-nowrap">
                    <i class="fa-solid {{ $tierlist->type->icon() }}"></i>
                    {{ Str::ucfirst($tierlist->type->itemLabel()) }}
                </span>
            </div>

            {{-- Stats Pills --}}
            <div class="flex flex-wrap gap-2 mb-3">
                @if ($topItem)
                    <span class="inline-flex items-center gap-1.5 text-xs bg-zinc-100 text-zinc-600 px-2 py-1 rounded">
                        <i class="fa-regular fa-star text-amber-500"></i>
                        {{ Str::limit($topItem->entryable->name(), 20) }}
                    </span>
                @endif

                <span class="inline-flex items-center gap-1.5 text-xs bg-zinc-100 text-zinc-600 px-2 py-1 rounded">
                    <i class="fa-solid fa-hashtag text-zinc-400"></i>
                    {{ $tierlist->items_count }} {{ $tierlist->type->itemLabel() }}
                </span>

                @unless ($tierlist->is_complete)
                    <span class="inline-flex items-center gap-1.5 text-xs px-2 py-1 rounded bg-amber-50 text-amber-700">
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
</x-card>
