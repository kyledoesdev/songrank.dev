<div x-data="{ open: false }">
    <button type="button" class="text-xs text-primary hover:underline cursor-pointer" x-on:click="open = true">
        <i class="fa-solid fa-box-archive mr-1"></i>
        View catalog
    </button>

    @teleport('body')
        <div
            x-show="open"
            x-cloak
            x-transition.opacity
            x-on:keydown.escape.window="open = false"
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
        >
            <div class="absolute inset-0 bg-black/50" x-on:click="open = false"></div>

            <x-card class="relative w-full max-w-3xl flex flex-col max-h-[85vh]">
                <x-card.header class="flex items-center justify-between gap-3">
                    <nav class="flex items-center gap-2 text-sm text-zinc-500 min-w-0" aria-label="Breadcrumb">
                        <button type="button" class="shrink-0 font-semibold text-zinc-800 hover:text-zinc-600 cursor-pointer" wire:click="showTopLevel">
                            <i class="fa-solid fa-box-archive text-primary-icon mr-1"></i>
                            {{ $user->name }}'s Catalog
                        </button>

                        @if ($crate)
                            @foreach ($crate->ancestors() as $ancestor)
                                <i class="fa-solid fa-chevron-right text-[10px] text-zinc-300"></i>
                                <button type="button" class="truncate hover:text-zinc-800 cursor-pointer" wire:click="showCrate({{ $ancestor->getKey() }})">
                                    {{ $ancestor->name }}
                                </button>
                            @endforeach

                            <i class="fa-solid fa-chevron-right text-[10px] text-zinc-300"></i>
                            <span class="truncate font-medium text-zinc-800">{{ $crate->name }}</span>
                        @endif
                    </nav>

                    <button type="button" class="text-zinc-400 hover:text-zinc-700 cursor-pointer" x-on:click="open = false" title="Close">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </x-card.header>

                <div class="p-4 overflow-y-auto flex-1 space-y-4">
                    @if ($this->crates->isNotEmpty())
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            @foreach ($this->crates as $listed)
                                <button
                                    type="button"
                                    class="flex items-center gap-3 p-3 rounded-xl border border-zinc-200 hover:border-zinc-300 hover:shadow-sm text-left cursor-pointer transition-all"
                                    wire:click="showCrate({{ $listed->getKey() }})"
                                    wire:key="catalog-crate-{{ $listed->getKey() }}"
                                >
                                    <x-catalog.crate-icon :crate="$listed" class="w-10 h-10 text-lg" />

                                    <div class="min-w-0">
                                        <p class="text-sm font-medium text-zinc-800 truncate">{{ $listed->name }}</p>
                                        <p class="text-xs text-zinc-500">
                                            {{ $listed->items_count }} {{ Str::plural('item', $listed->items_count) }}

                                            @if ($listed->children_count)
                                                <span class="text-zinc-300 mx-1">|</span>
                                                {{ $listed->children_count }} {{ Str::plural('crate', $listed->children_count) }}
                                            @endif
                                        </p>
                                    </div>
                                </button>
                            @endforeach
                        </div>
                    @endif

                    @if ($this->items->isNotEmpty())
                        <div class="space-y-2">
                            @foreach ($this->items as $item)
                                <a href="{{ $item->entity->url() }}" class="block" wire:key="catalog-item-{{ $item->getKey() }}">
                                    <x-catalog.entity-row :entity="$item->entity" class="hover:border-zinc-300 hover:shadow-sm transition-all">
                                        <i class="fa-solid fa-arrow-up-right-from-square text-zinc-400 text-sm mr-1"></i>
                                    </x-catalog.entity-row>
                                </a>
                            @endforeach
                        </div>
                    @endif

                    @if ($this->crates->isEmpty() && $this->items->isEmpty())
                        <div class="text-center py-10">
                            <i class="fa-solid fa-box-open text-3xl text-zinc-300 mb-3"></i>
                            <p class="text-sm text-zinc-500">Nothing to see in here yet.</p>
                        </div>
                    @endif
                </div>
            </x-card>
        </div>
    @endteleport
</div>
