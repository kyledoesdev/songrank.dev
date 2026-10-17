{{-- Livewire partial: expects $type from the including setup view. --}}

<x-card>
    <x-card.header>
        <h4 class="font-semibold text-gray-800">Results</h4>
        <p class="text-xs text-zinc-500 mt-0.5">Pick the {{ $type->subjectLabel() }} you want to write about.</p>
    </x-card.header>

    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3 p-4" x-auto-animate>
        @foreach ($this->results() as $result)
            <div
                wire:key="result-{{ $result['id'] }}"
                class="group bg-white rounded-xl overflow-hidden border border-zinc-200 hover:border-primary-soft hover:shadow-lg transition-all duration-300"
            >
                <button type="button" wire:click="chooseSubject('{{ $result['id'] }}')" class="block w-full text-left cursor-pointer">
                    <img
                        src="{{ $result['cover'] }}"
                        alt="{{ $result['name'] }}"
                        class="w-full aspect-square object-cover group-hover:scale-105 transition-transform duration-300"
                    >

                    <div class="px-2 pt-2">
                        <p class="text-sm font-medium text-zinc-800 truncate">{{ $result['name'] }}</p>

                        @if (filled($result['artist_name'] ?? null))
                            <p class="text-xs text-zinc-500 truncate">{{ $result['artist_name'] }}</p>
                        @endif
                    </div>
                </button>

                <div class="px-2 pt-1 pb-2">
                    <x-spotify-logo :url="$this->spotifyUrlFor($result)" />
                </div>
            </div>
        @endforeach
    </div>
</x-card>
