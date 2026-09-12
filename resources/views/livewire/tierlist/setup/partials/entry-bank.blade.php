@use('App\Enums\TierlistType')

{{-- Livewire partial: expects $type. $bank comes from the including component. --}}

@php($count = $this->bankCount())
@php($limit = $this->bankLimit())

<div class="border border-gray-200 bg-white rounded-lg overflow-hidden">
    <div class="px-4 py-3 bg-gray-50 border-b border-gray-200 flex items-center justify-between gap-3">
        <h4 class="font-semibold text-gray-800">
            <i class="fa-solid {{ $type->icon() }} mr-1 text-zinc-400"></i>
            The Board
        </h4>

        <span @class([
            'text-xs px-2 py-1 rounded-lg',
            'bg-red-100 text-red-700' => $this->bankIsFull(),
            'bg-zinc-100 text-zinc-600' => ! $this->bankIsFull(),
        ])>
            {{ $count }} / {{ $limit }} {{ $type->itemLabel() }}
        </span>
    </div>

    <div class="card-scroller-half p-2" x-auto-animate>
        @forelse ($bank as $entry)
            <div wire:key="bank-{{ $entry['id'] }}">
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
