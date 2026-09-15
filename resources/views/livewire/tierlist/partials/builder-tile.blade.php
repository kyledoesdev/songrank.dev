{{-- Livewire partial: expects $item, with its entryable loaded, and $showEmbeds. --}}

@php($entry = $item->entryable)

<div
    wire:key="item-{{ $item->getKey() }}"
    wire:sort:item="{{ $item->getKey() }}"
    @class([
        'shrink-0',
        'w-20 sm:w-24' => ! $showEmbeds,
        'w-full sm:w-80 rounded-xl border border-zinc-200 bg-white shadow-sm overflow-hidden' => $showEmbeds,
    ])
>
    @if ($showEmbeds)
        {{--
            With a player in the card the grip has to exist: a drag cannot start
            on an iframe, which keeps every pointer event it is handed.
        --}}
        <div class="flex items-center gap-1 px-1.5 py-1 bg-zinc-50 border-b border-zinc-200">
            <span wire:sort:handle class="cursor-grab active:cursor-grabbing text-zinc-400 hover:text-zinc-600">
                <i class="fa-solid fa-grip-vertical text-xs"></i>
            </span>

            <span class="text-[10px] text-zinc-600 truncate flex-1" title="{{ $entry->name() }}">
                {{ $entry->name() }}
            </span>
        </div>

        <div wire:sort:ignore class="p-1">
            <iframe
                src="https://open.spotify.com/embed/{{ $item->entryable_type }}/{{ $entry->spotifyId() }}"
                width="100%"
                height="80"
                frameborder="0"
                loading="lazy"
                allow="clipboard-write; encrypted-media; fullscreen; picture-in-picture"
                title="{{ $entry->name() }} on Spotify"
                class="rounded-lg"
            ></iframe>
        </div>
    @else
        {{-- Nothing sits on top of the artwork, and the artwork is the grip. --}}
        <img
            src="{{ $entry->cover() }}"
            alt="{{ $entry->name() }}"
            title="{{ $entry->name() }}"
            class="w-20 h-20 sm:w-24 sm:h-24 rounded-xl border border-zinc-200 object-cover shadow-sm cursor-grab active:cursor-grabbing"
            draggable="false"
        >
    @endif
</div>
