{{-- Livewire partial: expects $entity, one of the viewer's own records. --}}

@php
    $cover = $entity->cover();
    $filed = $this->isFiled($entity);
@endphp

<div class="flex items-center gap-3 p-2 rounded-xl border border-zinc-200" wire:key="add-item-{{ $tab->value }}-{{ $entity->getKey() }}">
    @if ($cover)
        <img src="{{ $cover }}" alt="{{ $entity->name }}" class="w-12 h-12 rounded-lg object-cover border border-zinc-200 shrink-0">
    @else
        <div class="w-12 h-12 rounded-lg bg-zinc-100 flex items-center justify-center shrink-0">
            <i class="fa-solid {{ $entity->type->icon() }} text-zinc-400"></i>
        </div>
    @endif

    <div class="min-w-0 flex-1">
        <p class="text-sm font-medium text-zinc-800 truncate" title="{{ $entity->name }}">{{ $entity->name }}</p>
        <p class="text-xs text-zinc-500 truncate">
            {{ $entity->label() }}

            @if ($entity->progressLabel())
                <span class="ml-1 px-1.5 py-0.5 rounded bg-amber-50 text-amber-700">{{ $entity->progressLabel() }}</span>
            @endif
        </p>
    </div>

    <button
        type="button"
        wire:click="toggle({{ $entity->getKey() }})"
        @class([
            'shrink-0 text-xs px-3 py-1.5 rounded-lg border cursor-pointer transition-colors',
            'border-green-300 bg-green-50 text-green-700 hover:border-red-300 hover:bg-red-50 hover:text-red-700' => $filed,
            'border-zinc-200 text-zinc-600 hover:border-purple-300 hover:text-purple-700' => ! $filed,
        ])
        title="{{ $filed ? 'Take it out of this crate' : 'Add it to this crate' }}"
    >
        @if ($filed)
            <i class="fa-solid fa-check mr-1"></i>
            Added
        @else
            <i class="fa-solid fa-plus mr-1"></i>
            Add
        @endif
    </button>
</div>
