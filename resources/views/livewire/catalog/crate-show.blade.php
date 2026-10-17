@use('App\Models\Ranking')
@use('App\Models\Review')
@use('App\Models\Tierlist')

<div class="mt-4 space-y-3">
    <x-card class="px-4 py-2.5">
        <nav class="flex items-center gap-2 text-sm text-zinc-500 min-w-0" aria-label="Breadcrumb">
            <a href="{{ route('catalog') }}" class="shrink-0 hover:text-zinc-800">
                <i class="fa-solid fa-box-archive text-primary-icon mr-0.5"></i>
                Catalog
            </a>

            @foreach ($crate->ancestors() as $ancestor)
                <i class="fa-solid fa-chevron-right text-[10px] text-zinc-300"></i>
                <a href="{{ route('catalog.crate', ['id' => $ancestor->getKey()]) }}" class="truncate hover:text-zinc-800">
                    {{ $ancestor->name }}
                </a>
            @endforeach

            <i class="fa-solid fa-chevron-right text-[10px] text-zinc-300"></i>
            <span class="truncate font-medium text-zinc-800">{{ $crate->name }}</span>
        </nav>
    </x-card>

    <x-card>
        <x-card.header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div class="flex items-center gap-3 min-w-0">
                    <x-catalog.crate-icon :crate="$crate" class="w-12 h-12 text-xl" />

                    <div class="min-w-0">
                        <h5 class="text-base sm:text-lg md:text-xl font-medium truncate">{{ $crate->name }}</h5>
                        <p class="text-xs text-zinc-500 mt-0.5">
                            {{ $this->items->count() }} {{ Str::plural('item', $this->items->count()) }}

                            @if ($this->crates->isNotEmpty())
                                <span class="text-zinc-300 mx-1">|</span>
                                {{ $this->crates->count() }} {{ Str::plural('crate', $this->crates->count()) }}
                            @endif
                        </p>
                    </div>
                </div>

                <div class="flex items-center shrink-0">
                    <livewire:catalog.add-to-crate-modal :crate="$crate" :wire:key="'add-items-'.$crate->getKey()" />

                    @if ($this->canAddCrate())
                        <button type="button" class="btn-secondary my-0 px-2 py-1" wire:click="startCreatingCrate" title="New crate">
                            <i class="fa-solid fa-folder-plus text-sm"></i>
                            <span class="hidden sm:inline text-sm ml-1">New crate</span>
                        </button>
                    @endif

                    <livewire:catalog.crate-settings
                        :crate="$crate"
                        redirect-on-delete
                        class="btn-helper my-0 px-2 py-1"
                        :wire:key="'crate-settings-'.$crate->getKey()"
                    />
                </div>
            </div>
        </x-card.header>

        @if ($this->crates->isNotEmpty())
            <div class="p-4 border-b border-zinc-200">
                <h6 class="text-sm font-semibold text-zinc-700 mb-3">Crates inside</h6>

                @include('livewire.catalog.partials.crate-grid')
            </div>
        @elseif (! Auth::user()->is_pro)
            <p class="px-4 pt-3 text-xs text-zinc-500">
                <i class="fa-solid fa-star text-purple-400 mr-1"></i>
                Pro can put crates inside crates.
                <a href="{{ route('billing') }}" class="text-primary hover:underline">Go Pro</a>
            </p>
        @endif

        <div class="p-4">
            @if ($this->items->isEmpty())
                <div class="text-center py-10">
                    <i class="fa-solid fa-box-open text-3xl text-zinc-300 mb-3"></i>
                    <p class="text-sm text-zinc-500 mb-4">Nothing in this crate yet.</p>

                    <button type="button" class="btn-primary px-3 py-1.5" x-data x-on:click="$dispatch('open-add-items')">
                        <i class="fa-solid fa-file-circle-plus mr-1"></i>
                        Add items
                    </button>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4" wire:sort="moveItem">
                    @foreach ($this->items as $item)
                        <div class="relative h-full" wire:key="crate-item-{{ $item->getKey() }}" wire:sort:item="{{ $item->getKey() }}">
                            @switch ($item->entity::class)
                                @case(Ranking::class)
                                    <x-rankings.card :ranking="$item->entity" />
                                    @break
                                @case(Tierlist::class)
                                    <x-tierlists.card :tierlist="$item->entity" />
                                    @break
                                @case(Review::class)
                                    <x-reviews.card :review="$item->entity" />
                                    @break
                            @endswitch

                            <div class="absolute top-2 right-2 flex items-center gap-1">
                                <span
                                    wire:sort:handle
                                    class="h-7 w-7 rounded-lg flex items-center justify-center bg-white/90 text-zinc-400 hover:text-zinc-700 cursor-grab active:cursor-grabbing"
                                    title="Drag to reorder"
                                >
                                    <i class="fa-solid fa-grip-vertical text-sm"></i>
                                </span>

                                <button
                                    type="button"
                                    class="h-7 w-7 rounded-lg flex items-center justify-center bg-white/90 text-zinc-400 hover:text-red-600 cursor-pointer"
                                    wire:click="removeItem({{ $item->getKey() }})"
                                    title="Take it out of this crate"
                                >
                                    <i class="fa-solid fa-xmark text-sm"></i>
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </x-card>

    @include('livewire.catalog.partials.new-crate-modal', ['parentName' => $crate->name])
</div>
