@use('App\Enums\RankingType')

<x-card>
    <x-card.header>
        <h4 class="font-semibold text-gray-800">Rankings</h4>
        <p class="text-xs text-zinc-500 mt-0.5">
            Rank songs with head-to-head comparisons using our merge-sort algorithm.
        </p>
    </x-card.header>

    <div class="grid grid-cols-1 gap-3 p-4">
        @foreach (RankingType::cases() as $type)
            @if (auth()->user()->canCreateRanking())
                <a
                    href="{{ route('rankings.create', ['type' => $type->value]) }}"
                    wire:key="create-{{ $type->value }}"
                    @class([
                        'flex items-center gap-3 p-3 rounded-xl border-2 transition-all duration-300 hover:scale-101 hover:shadow-lg',
                        'border-purple-300 bg-purple-50 hover:border-purple-500' => $type === RankingType::ARTIST,
                        'border-green-300 bg-green-50 hover:border-green-500' => $type === RankingType::PLAYLIST,
                        'border-blue-300 bg-blue-50 hover:border-blue-500' => $type === RankingType::SHOW,
                    ])
                >
                    <i class="fa-solid {{ $type->icon() }} text-xl text-zinc-600"></i>
                    <p class="font-medium text-sm text-zinc-800">{{ $type->label() }}</p>
                </a>
            @else
                <a
                    href="{{ route('billing') }}"
                    wire:key="upgrade-{{ $type->value }}"
                    class="flex items-center gap-3 p-3 rounded-xl border-2 border-zinc-200 bg-zinc-50 transition-all duration-300 hover:border-purple-300"
                    title="You have reached your ranking limit"
                >
                    <i class="fa-solid fa-star text-xl text-purple-400"></i>
                    <p class="font-medium text-sm text-zinc-500">{{ $type->label() }}</p>
                </a>
            @endif
        @endforeach
    </div>

    <x-card.footer>
        <p class="text-xs text-zinc-500">
            @if (is_null(auth()->user()->rankingLimit()))
                Unlimited rankings
            @else
                {{ auth()->user()->rankings()->count() }} / {{ auth()->user()->rankingLimit() }} used
                @unless (auth()->user()->canCreateRanking())
                    &mdash; <a href="{{ route('billing') }}" class="text-purple-600 hover:underline">go Pro for more</a>
                @endunless
            @endif
        </p>
    </x-card.footer>
</x-card>
