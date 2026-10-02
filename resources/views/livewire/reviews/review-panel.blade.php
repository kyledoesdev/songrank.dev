@use('App\Enums\ReviewType')

<x-card>
    <x-card.header>
        <h4 class="font-semibold text-gray-800">Reviews</h4>
        <p class="text-xs text-zinc-500 mt-0.5">
            Share what you think with the community.
        </p>
    </x-card.header>

    <div class="grid grid-cols-1 gap-3 p-4">
        @if (auth()->user()->canCreateReview())
            @foreach (ReviewType::cases() as $type)
                <a
                    href="{{ route('reviews.create', ['type' => $type->value]) }}"
                    wire:key="write-{{ $type->value }}"
                    @class([
                        'flex items-center gap-3 p-3 rounded-xl border-2 transition-all duration-300 hover:scale-101 hover:shadow-lg',
                        'border-purple-300 bg-purple-50 hover:border-purple-500' => $type === ReviewType::ARTIST,
                        'border-green-300 bg-green-50 hover:border-green-500' => $type === ReviewType::ALBUM,
                        'border-blue-300 bg-blue-50 hover:border-blue-500' => $type === ReviewType::TRACK,
                    ])
                >
                    <i class="fa-solid {{ $type->icon() }} text-xl text-zinc-600"></i>
                    <p class="font-medium text-sm text-zinc-800">{{ Str::ucfirst($type->withArticle()) }}</p>
                </a>
            @endforeach
        @else
            <a
                href="{{ route('billing') }}"
                class="flex items-center gap-3 p-3 rounded-xl border-2 border-zinc-200 bg-zinc-50 transition-all duration-300 hover:border-purple-300"
                title="You have used your review allowance"
            >
                <i class="fa-solid fa-star text-xl text-purple-400"></i>
                <p class="font-medium text-sm text-zinc-500">Go Pro for unlimited reviews</p>
            </a>
        @endif
    </div>

    <x-card.footer>
        <p class="text-xs text-zinc-500">
            @if (is_null(auth()->user()->reviewLimit()))
                Unlimited reviews
            @else
                {{ $this->count }} / {{ auth()->user()->reviewLimit() }} used
                @unless (auth()->user()->canCreateReview())
                    &mdash; <a href="{{ route('billing') }}" class="text-purple-600 hover:underline">go Pro for more</a>
                @endunless
            @endif
        </p>
    </x-card.footer>
</x-card>
