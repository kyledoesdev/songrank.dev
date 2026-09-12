<div>
    @if ($this->hasAnything())
        <div class="bg-white shadow-md rounded-lg p-2">
            <h5 class="md:text-md mt-2 mb-4 px-2">
                Pick up where you left off
            </h5>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4" x-auto-animate>
                @foreach ($this->rankings as $ranking)
                    <div class="border rounded-xl" wire:key="ranking-{{ $ranking->getKey() }}">
                        <livewire:ranking.card :ranking="$ranking" :key="'ranking-card-'.$ranking->getKey()" />
                    </div>
                @endforeach

                @foreach ($this->tierlists as $tierlist)
                    @include('livewire.tierlist.partials.tierlist-row', ['tierlist' => $tierlist])
                @endforeach
            </div>
        </div>
    @endif
</div>
