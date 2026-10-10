@use('App\Enums\CrateEntityType')

<div x-data="{ open: false }" x-on:open-add-items.window="open = true">
    <button type="button" class="btn-primary my-0 px-2 py-1" x-on:click="open = true" title="Add items">
        <i class="fa-solid fa-file-circle-plus text-sm"></i>
        <span class="hidden sm:inline text-sm ml-1">Add items</span>
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

            <x-card class="relative w-full max-w-2xl flex flex-col max-h-[85vh]">
                <x-card.header class="flex items-center justify-between gap-3">
                    <div class="min-w-0">
                        <h5 class="font-semibold text-zinc-800 truncate">Add to {{ $crate->name }}</h5>
                        <p class="text-xs text-zinc-500 mt-0.5">Anything you add here can sit in your other crates too.</p>
                    </div>

                    <button type="button" class="text-zinc-400 hover:text-zinc-700 cursor-pointer" x-on:click="open = false" title="Close">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </x-card.header>

                <div class="px-4 pt-3 space-y-3">
                    <div class="flex flex-wrap gap-2">
                        @foreach (CrateEntityType::cases() as $type)
                            <button
                                type="button"
                                wire:click="$set('tab', '{{ $type->value }}')"
                                @class([
                                    'flex items-center gap-2 px-3 py-1.5 rounded-full border-2 text-sm transition-all duration-300 cursor-pointer',
                                    'border-purple-500 bg-purple-100 text-purple-700' => $tab === $type,
                                    'border-zinc-300 bg-white text-zinc-500 hover:border-zinc-400' => $tab !== $type,
                                ])
                            >
                                <i class="fa-solid {{ $type->icon() }}"></i>
                                {{ $type->label() }}
                            </button>
                        @endforeach
                    </div>

                    <div class="relative">
                        <i class="fa fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-zinc-400 text-sm"></i>
                        <input
                            type="text"
                            class="w-full pl-9 pr-3 py-2 text-sm border border-zinc-300 rounded-full transition-all duration-300 focus:ring-2 focus:ring-purple-400 focus:border-purple-400 focus:outline-none"
                            placeholder="Search by name"
                            wire:model.live.debounce.300ms="search"
                        />
                    </div>
                </div>

                <div class="p-4 overflow-y-auto flex-1 space-y-2">
                    @forelse ($this->results as $entity)
                        @include('livewire.catalog.partials.add-items-row', ['entity' => $entity])
                    @empty
                        <p class="text-sm text-zinc-500 text-center py-8">
                            @if (filled($search))
                                Nothing matches &ldquo;{{ $search }}&rdquo;.
                            @else
                                You haven't made any {{ Str::lower($tab->label()) }} yet.
                            @endif
                        </p>
                    @endforelse
                </div>

                @if ($this->results->hasPages())
                    <x-card.footer>
                        {{ $this->results->links('pagination.minimal') }}
                    </x-card.footer>
                @endif
            </x-card>
        </div>
    @endteleport
</div>
