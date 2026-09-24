{{-- Livewire partial: expects $tierlist, with its source loaded and items counted. --}}

<a
    href="{{ route('tierlist', ['id' => $tierlist->getKey()]) }}"
    wire:key="tierlist-{{ $tierlist->getKey() }}"
    class="flex items-center gap-3 p-3 rounded-xl border border-zinc-200 hover:border-zinc-300 hover:shadow-lg transition-all duration-300"
>
    @if ($tierlist->source?->cover())
        <img
            src="{{ $tierlist->source->cover() }}"
            alt="{{ $tierlist->source->name() }}"
            class="w-14 h-14 rounded-xl border border-zinc-200 object-cover shadow-sm shrink-0"
        >
    @else
        <div class="w-14 h-14 rounded-xl bg-primary-muted flex items-center justify-center shrink-0">
            <i class="fa-solid {{ $tierlist->type->icon() }} text-primary-icon text-lg"></i>
        </div>
    @endif

    <div class="min-w-0 flex-1">
        <div class="flex items-center gap-2 flex-wrap">
            <p class="font-medium text-sm text-zinc-800 truncate" title="{{ $tierlist->name }}">
                {{ $tierlist->name }}
            </p>
            <span class="inline-flex items-center gap-1 text-[10px] px-2 py-0.5 rounded-full bg-{{ $tierlist->type->color() }}-100 text-{{ $tierlist->type->color() }}-700 whitespace-nowrap">
                <i class="fa-solid {{ $tierlist->type->icon() }}"></i>
                {{ $tierlist->type->label() }}
            </span>
        </div>

        <p class="text-xs text-zinc-500 truncate mt-0.5">
            {{ $tierlist->items_count }} {{ $tierlist->type->itemLabel() }}
            <span class="text-zinc-300 mx-1">|</span>
            {{ $tierlist->is_complete ? $tierlist->completed_at : 'In Progress' }}
        </p>
    </div>
</a>
