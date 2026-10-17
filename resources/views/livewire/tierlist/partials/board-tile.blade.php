{{-- Livewire partial: expects $item, a TierlistItem with its entryable loaded. --}}

@php($entry = $item->entryable)

<a
    href="{{ $entry->spotifyUrl() }}"
    target="_blank"
    class="w-16 sm:w-20 shrink-0 group"
    title="{{ $entry->name() }}"
>
    <img
        src="{{ $entry->cover() }}"
        alt="{{ $entry->name() }}"
        class="w-16 h-16 sm:w-20 sm:h-20 rounded-xl border border-zinc-200 object-cover shadow-sm transition-transform duration-300 group-hover:scale-105"
    >
    <p class="mt-1 text-[10px] sm:text-xs text-zinc-600 truncate text-center">
        {{ $entry->name() }}
    </p>
</a>
