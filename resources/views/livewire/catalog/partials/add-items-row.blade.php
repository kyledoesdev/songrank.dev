{{-- Livewire partial: expects $entity, one of the viewer's own records. --}}

@php($filed = $this->isFiled($entity))

<x-catalog.entity-row :entity="$entity" wire:key="add-item-{{ $tab->value }}-{{ $entity->getKey() }}">
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
</x-catalog.entity-row>
