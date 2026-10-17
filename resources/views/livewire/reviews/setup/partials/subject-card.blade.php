{{-- Livewire partial: expects $type from the including setup view. --}}

<x-card>
    <x-card.header>
        <h4 class="font-semibold text-gray-800">
            <i class="fa-solid {{ $type->icon() }} mr-1 text-primary-icon"></i>
            {{ $type->label() }}
        </h4>
    </x-card.header>

    <div class="flex items-center gap-4 p-4">
        <img
            src="{{ $this->subject['cover'] }}"
            alt="{{ $this->subject['name'] }}"
            class="h-24 w-24 rounded-lg object-cover shrink-0"
        >

        <div class="min-w-0 flex-1">
            <p class="text-lg font-semibold text-zinc-800 truncate">{{ $this->subject['name'] }}</p>

            @if (filled($this->subject['artist_name'] ?? null))
                <p class="text-sm text-zinc-600 truncate">{{ $this->subject['artist_name'] }}</p>
            @endif

            <div class="mt-1">
                <x-spotify-logo :url="$this->spotifyUrlFor($this->subject)" />
            </div>
        </div>

        <button
            type="button"
            wire:click="clearSubject"
            class="btn-secondary px-2 py-1 shrink-0"
            title="Pick something else"
        >
            <i class="fa-solid fa-rotate-left"></i>
        </button>
    </div>
</x-card>
