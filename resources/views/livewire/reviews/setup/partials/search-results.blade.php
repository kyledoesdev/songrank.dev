{{-- Livewire partial: expects $type from the including setup view. --}}

<div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3" x-auto-animate>
    @foreach ($this->results() as $result)
        <button
            type="button"
            wire:key="result-{{ $result['id'] }}"
            wire:click="chooseSubject('{{ $result['id'] }}')"
            class="group text-left bg-white rounded-xl overflow-hidden border border-zinc-200 hover:border-primary-soft hover:shadow-lg transition-all duration-300 cursor-pointer"
        >
            <img
                src="{{ $result['cover'] }}"
                alt="{{ $result['name'] }}"
                class="w-full aspect-square object-cover group-hover:scale-105 transition-transform duration-300"
            >

            <div class="p-2">
                <p class="text-sm font-medium text-zinc-800 truncate">{{ $result['name'] }}</p>

                @if (filled($result['artist_name'] ?? null))
                    <p class="text-xs text-zinc-500 truncate">{{ $result['artist_name'] }}</p>
                @endif
            </div>
        </button>
    @endforeach
</div>
