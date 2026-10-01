{{-- Livewire partial: expects $type from the including setup view. --}}

<div class="flex items-center gap-4 p-3 rounded-xl border border-primary-soft bg-primary-soft">
    <img
        src="{{ $this->subject['cover'] }}"
        alt="{{ $this->subject['name'] }}"
        class="h-20 w-20 rounded-lg object-cover shrink-0"
    >

    <div class="min-w-0 flex-1">
        <p class="text-xs uppercase tracking-wide text-zinc-500">
            <i class="fa-solid {{ $type->icon() }} mr-1"></i>
            {{ $type->label() }}
        </p>

        <p class="text-lg font-semibold text-zinc-800 truncate">{{ $this->subject['name'] }}</p>

        @if (filled($this->subject['artist_name'] ?? null))
            <p class="text-sm text-zinc-600 truncate">{{ $this->subject['artist_name'] }}</p>
        @endif
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
