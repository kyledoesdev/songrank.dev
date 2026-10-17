{{-- Livewire partial: expects $crates, one level of the picker's tree. --}}

<ul class="space-y-1">
    @foreach ($crates as $crate)
        <li wire:key="pick-crate-{{ $crate->getKey() }}">
            <label class="flex items-center gap-3 p-2 rounded-lg hover:bg-zinc-50 cursor-pointer">
                <input type="checkbox" class="w-4 h-4 accent-purple-500" value="{{ $crate->getKey() }}" wire:model.live="crateIds">
                <x-catalog.crate-icon :crate="$crate" class="w-8 h-8 text-sm" />
                <span class="text-sm text-zinc-800 truncate">{{ $crate->name }}</span>
            </label>

            @if ($this->childrenOf($crate)->isNotEmpty())
                <div class="ml-6 pl-3 border-l border-zinc-200">
                    @include('livewire.catalog.partials.crate-picker-branch', ['crates' => $this->childrenOf($crate)])
                </div>
            @endif
        </li>
    @endforeach
</ul>
