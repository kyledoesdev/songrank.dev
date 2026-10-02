@props([
    'review',
    'size' => 'text-base',
])

@if ($review->hasScore())
    <div {{ $attributes->class(['flex items-center gap-1']) }}>
        <div class="flex items-center {{ $size }} text-amber-400">
            @for ($i = 1; $i <= 10; $i++)
                @if ($i <= $review->fullStars())
                    <i class="fa-solid fa-star"></i>
                @elseif ($i === $review->fullStars() + 1 && $review->hasHalfStar())
                    <i class="fa-solid fa-star-half-stroke"></i>
                @else
                    <i class="fa-regular fa-star text-zinc-300"></i>
                @endif
            @endfor
        </div>

        <span class="ml-1 font-semibold text-zinc-700 {{ $size }}">{{ $review->score() }}<span class="text-zinc-400 text-xs">/10</span></span>
    </div>
@endif
