{{-- Livewire partial: expects $entry and $action ('add' or 'remove'). --}}

@php($subtitle = $this->subtitleFor($entry))

<div class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 transition-all duration-300">
    <img
        src="{{ $entry['cover'] }}"
        alt="{{ $entry['name'] }}"
        class="w-12 h-12 sm:w-14 sm:h-14 rounded-xl border border-zinc-200 object-cover shadow-sm shrink-0"
    >

    <div class="min-w-0 flex-1">
        <h5 class="text-sm sm:text-base truncate" title="{{ $entry['name'] }}">
            {{ $entry['name'] }}
        </h5>

        @if ($subtitle)
            <p class="text-xs text-zinc-500 truncate">{{ $subtitle }}</p>
        @endif

        <div class="mt-1">
            <x-spotify-logo :url="$this->spotifyUrlFor($entry)" />
        </div>
    </div>

    @if ($action === 'add')
        <button
            type="button"
            class="btn-primary px-2 py-1 shrink-0 disabled:opacity-40"
            wire:click="addEntry('{{ $entry['id'] }}')"
            wire:key="add-{{ $entry['id'] }}"
            @disabled($this->holds($entry['id']))
            title="{{ $this->holds($entry['id']) ? 'Already on the board' : 'Add to the board' }}"
        >
            <i class="fa-solid {{ $this->holds($entry['id']) ? 'fa-check' : 'fa-plus' }}"></i>
        </button>
    @else
        <button
            type="button"
            class="text-gray-500 hover:text-red-600 transition-all duration-200 p-1 shrink-0 cursor-pointer transform hover:scale-110"
            wire:click="removeEntry('{{ $entry['id'] }}')"
            wire:key="remove-{{ $entry['id'] }}"
            title="Take off the board"
        >
            <i class="fa-solid fa-trash-can"></i>
        </button>
    @endif
</div>
