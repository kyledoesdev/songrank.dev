@use('App\Enums\TierlistType')

<x-card>
    <x-card.header>
        <h4 class="font-semibold text-gray-800">Tier Lists</h4>
        <p class="text-xs text-zinc-500 mt-0.5">
            Sort artists, albums or tracks into tiers and share the board.
        </p>
    </x-card.header>

    <div class="grid grid-cols-1 gap-3 p-4">
        @foreach (TierlistType::cases() as $type)
            @php($limit = $this->limitFor($type))
            @php($count = $this->countFor($type))

            @if ($this->canCreate($type))
                <a
                    href="{{ route('tierlists.create', ['type' => $type->value]) }}"
                    wire:key="create-{{ $type->value }}"
                    @class([
                        'flex items-center gap-3 p-3 rounded-xl border-2 transition-all duration-300 hover:scale-101 hover:shadow-lg',
                        'border-purple-300 bg-purple-50 hover:border-purple-500' => $type === TierlistType::ARTIST,
                        'border-green-300 bg-green-50 hover:border-green-500' => $type === TierlistType::ALBUM,
                        'border-blue-300 bg-blue-50 hover:border-blue-500' => $type === TierlistType::TRACK,
                    ])
                >
                    <i class="fa-solid {{ $type->icon() }} text-xl text-zinc-600"></i>
                    <div class="min-w-0">
                        <p class="font-medium text-sm text-zinc-800">{{ $type->label() }} Tier List</p>
                        <p class="text-xs text-zinc-500">
                            @if (is_null($limit))
                                Unlimited
                            @else
                                {{ $count }} / {{ $limit }} used
                            @endif
                        </p>
                    </div>
                </a>
            @else
                <a
                    href="{{ route('billing') }}"
                    wire:key="upgrade-{{ $type->value }}"
                    class="flex items-center gap-3 p-3 rounded-xl border-2 border-zinc-200 bg-zinc-50 transition-all duration-300 hover:border-purple-300"
                    title="You have used your {{ $type->label() }} tier list"
                >
                    <i class="fa-solid fa-star text-xl text-purple-400"></i>
                    <div class="min-w-0">
                        <p class="font-medium text-sm text-zinc-500">{{ $type->label() }} Tier List</p>
                        <p class="text-xs text-zinc-500">
                            {{ $count }} / {{ $limit }} used &mdash; go Pro for more
                        </p>
                    </div>
                </a>
            @endif
        @endforeach
    </div>
</x-card>
