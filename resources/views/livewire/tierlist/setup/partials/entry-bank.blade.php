@use('App\Enums\TierlistType')

{{-- Livewire partial: expects $type. $bank comes from the including component. --}}

@php($count = $this->bankCount())
@php($limit = $this->bankLimit())
@php($removedCount = $this->removedEntryCount())
@php($showAlbumFilter = $showAlbumFilter ?? false)

<div class="border border-gray-200 bg-white rounded-lg overflow-hidden">
    <x-card.header class="flex items-center justify-between gap-3">
        <h4 class="font-semibold text-gray-800">
            <i class="fa-solid {{ $type->icon() }} mr-1 text-zinc-400"></i>
            The Board
        </h4>

        <div class="flex items-center gap-2">
            @if ($showAlbumFilter)
                <button
                    type="button"
                    class="text-xs px-2 py-1 rounded-lg bg-purple-50 text-purple-700 hover:bg-purple-100 transition-colors cursor-pointer"
                    x-data
                    @click="$dispatch('open-album-modal')"
                    title="Filter by album"
                >
                    <i class="fa-solid fa-sliders"></i>
                </button>
            @endif

            @if ($removedCount > 0)
                <button
                    type="button"
                    class="text-xs px-2 py-1 rounded-lg bg-zinc-100 text-zinc-600 hover:bg-zinc-200 transition-colors cursor-pointer"
                    x-data
                    @click="$dispatch('open-removed-entries-modal')"
                    title="Removed {{ $type->itemLabel() }}"
                >
                    <i class="fa-solid fa-trash-can mr-1"></i>
                    {{ $removedCount }}
                </button>
            @endif

            <span @class([
                'text-xs px-2 py-1 rounded-lg',
                'bg-red-100 text-red-700' => $this->bankIsFull(),
                'bg-zinc-100 text-zinc-600' => ! $this->bankIsFull(),
            ])>
                {{ $count }} / {{ $limit }} {{ $type->itemLabel() }}
            </span>
        </div>
    </x-card.header>

    <div class="card-scroller-half p-2" x-auto-animate>
        @forelse ($bank as $entry)
            <div
                wire:key="bank-{{ $entry['id'] }}"
                x-data="{ removing: false }"
                x-show="!removing"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-x-0"
                x-transition:leave-end="opacity-0 -translate-x-4"
            >
                @include('livewire.tierlist.setup.partials.entry-tile', [
                    'entry' => $entry,
                    'action' => 'remove',
                ])
            </div>
        @empty
            <p class="p-4 text-sm text-zinc-500">
                Nothing on the board yet. Search for {{ $type->itemLabel() }} and add the ones you want to rank.
            </p>
        @endforelse
    </div>
</div>
