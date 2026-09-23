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

        @if (auth()->id() !== $tierlist->user_id)
            <div class="mx-2 md:mx-4 mt-4 bg-primary-soft border border-primary-soft rounded-lg p-3 flex items-center gap-3">
                @if ($tierlist->user->avatar)
                    <img
                        src="{{ $tierlist->user->avatar }}"
                        alt="{{ $tierlist->user->name }}"
                        class="h-10 w-10 rounded-full object-cover shrink-0"
                    >
                @else
                    <div class="h-10 w-10 rounded-full bg-primary-muted flex items-center justify-center shrink-0">
                        <i class="fa-regular fa-user text-primary-icon"></i>
                    </div>
                @endif

                <div class="min-w-0 flex-1">
                    <p class="text-sm font-medium text-zinc-800 truncate">
                        Tiered by
                        <a href="{{ route('profile', ['id' => $tierlist->user->spotify_id]) }}" class="text-primary hover:underline">
                            {{ $tierlist->user->name }}
                            <i class="fa fa-arrow-up-right-from-square text-blue-500 text-xs"></i>
                        </a>
                    </p>
                    <p class="text-xs text-zinc-500 truncate" title="{{ $tierlist->formatted_completed_at }}">
                        {{ $tierlist->items->count() }} {{ $tierlist->type->itemLabel() }}
                        <span class="text-zinc-300 mx-1">|</span>
                        <i class="fa-regular fa-clock mr-0.5"></i>
                        {{ $tierlist->completed_at }}
                    </p>
                </div>
            </div>
        @endif

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

                    <div class="flex-1 flex flex-wrap gap-2 p-2 bg-zinc-50 min-h-20 max-h-48 sm:max-h-56 overflow-y-auto">
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

        @if ($tierlist->is_complete && $tierlist->comments_enabled)
            <div class="mx-4 pb-8">
                @if ($tierlist->comments_replies_enabled)
                    <livewire:comments
                        :model="$tierlist"
                        newest-first
                        no-comments-text="No comments yet - you could be the first!"
                    />
                @else
                    <livewire:comments
                        :model="$tierlist"
                        newest-first
                        no-replies
                        no-comments-text="No comments yet - you could be the first!"
                    />
                @endif
            </div>
        @endif
    </div>
    @endif
</div>
