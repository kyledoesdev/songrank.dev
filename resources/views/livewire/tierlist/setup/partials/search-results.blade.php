{{-- Livewire partial: expects $title, plus $bulkAction when the whole set can be added at once. --}}

<div class="border border-gray-200 bg-white rounded-lg overflow-hidden">
    <x-card.header class="flex items-center justify-between gap-3">
        <h4 class="font-semibold text-gray-800">{{ $title }}</h4>

        @isset($bulkAction)
            <button
                type="button"
                class="btn-secondary px-2 py-1 text-sm"
                wire:click="{{ $bulkAction }}"
            >
                <i class="fa-solid fa-plus mr-1"></i>
                Add all
            </button>
        @endisset
    </x-card.header>

    <div class="card-scroller-half p-2" x-auto-animate>
        @forelse ($this->results() as $entry)
            <div wire:key="result-{{ $entry['id'] }}">
                @include('livewire.tierlist.setup.partials.entry-tile', [
                    'entry' => $entry,
                    'action' => 'add',
                ])
            </div>
        @empty
            <p class="p-4 text-sm text-zinc-500">Nothing here yet. Search above to get started.</p>
        @endforelse
    </div>
</div>
