<div>
    @if (! $tierlist->is_complete)
        <livewire:tierlist.tierlist-builder :tierlist="$tierlist" />
    @else
    <div class="pl-4 pr-4 pb-4 bg-white shadow-lg rounded-lg mt-4">
        <div class="flex justify-between items-center">
            <div>
                <h5 class="text-base sm:text-lg md:text-xl font-medium">{{ $tierlist->name }}</h5>
                <p class="text-xs text-zinc-500">
                    {{ $tierlist->items->count() }} {{ $tierlist->type->itemLabel() }}
                    <span class="text-zinc-300 mx-1">|</span>
                    {{ $tierlist->type->label() }} tier list
                </p>
            </div>
            <div class="flex gap-1 sm:gap-2">
                @if (auth()->id() === $tierlist->user_id)
                    <a href="{{ route('tierlist.edit', ['id' => $tierlist->getKey()]) }}" class="btn-secondary p-1 sm:p-2">
                        <i class="fa fa-solid fa-pencil text-sm sm:text-base"></i>
                    </a>
                @endif
                @auth
                    <a href="{{ route('dashboard') }}" class="btn-primary p-1 sm:p-2">
                        <i class="fa fa-solid fa-house text-sm sm:text-base"></i>
                    </a>
                @endauth
            </div>
        </div>

        <hr>

        {{-- The finished board. --}}
        <div class="mt-4 space-y-1">
            @foreach ($tierlist->placementTiers() as $tier)
                <div class="flex items-stretch rounded-lg overflow-hidden border border-zinc-200" wire:key="tier-{{ $tier->getKey() }}">
                    <div
                        class="w-16 sm:w-24 shrink-0 flex items-center justify-center p-2 text-zinc-800 font-bold"
                        style="background-color: {{ $tier->color }}"
                    >
                        {{ $tier->name }}
                    </div>

                    <div class="flex-1 flex flex-wrap gap-2 p-2 bg-zinc-50 min-h-20">
                        @foreach ($tier->items as $item)
                            @include('livewire.tierlist.partials.board-tile', ['item' => $item])
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>

        @if ($tierlist->bank?->items->isNotEmpty())
            <div class="mt-4 rounded-lg border border-zinc-200 overflow-hidden">
                <div class="px-4 py-2 bg-gray-50 border-b border-zinc-200">
                    <h4 class="font-semibold text-gray-800 text-sm">
                        Unranked
                        <span class="text-xs font-normal text-zinc-500">
                            &mdash; {{ $tierlist->bank->items->count() }} still to place
                        </span>
                    </h4>
                </div>

                <div class="flex flex-wrap gap-2 p-2">
                    @foreach ($tierlist->bank->items as $item)
                        @include('livewire.tierlist.partials.board-tile', ['item' => $item])
                    @endforeach
                </div>
            </div>
        @endif
    </div>
    @endif
</div>
