@props(['crate', 'sortable' => false])

<x-card {{ $attributes->class(['relative h-full hover:shadow-lg transition-all duration-300']) }}>
    <a href="{{ route('catalog.crate', ['id' => $crate->getKey()]) }}" class="flex items-center gap-4 p-4 pr-20">
        <x-catalog.crate-icon :crate="$crate" class="w-14 h-14 text-2xl" />

        <div class="min-w-0">
            <h5 class="text-base md:text-lg font-bold truncate" title="{{ $crate->name }}">
                {{ $crate->name }}
            </h5>

            <div class="flex flex-wrap gap-2 mt-1">
                <span class="inline-flex items-center gap-1.5 text-xs bg-zinc-100 text-zinc-600 px-2 py-1 rounded">
                    <i class="fa-solid fa-hashtag text-zinc-400"></i>
                    {{ $crate->items_count }} {{ Str::plural('item', $crate->items_count) }}
                </span>

                @if ($crate->children_count)
                    <span class="inline-flex items-center gap-1.5 text-xs bg-zinc-100 text-zinc-600 px-2 py-1 rounded">
                        <i class="fa-solid fa-folder-tree text-zinc-400"></i>
                        {{ $crate->children_count }} {{ Str::plural('crate', $crate->children_count) }}
                    </span>
                @endif
            </div>
        </div>
    </a>

    @if ($sortable || isset($actions))
        <div class="absolute top-2 right-2 flex items-center gap-1">
            @if ($sortable)
                <span
                    wire:sort:handle
                    class="h-8 w-8 rounded-lg flex items-center justify-center text-zinc-400 hover:text-zinc-700 hover:bg-zinc-100 cursor-grab active:cursor-grabbing"
                    title="Drag to reorder"
                >
                    <i class="fa-solid fa-grip-vertical"></i>
                </span>
            @endif

            {{ $actions ?? '' }}
        </div>
    @endif
</x-card>
