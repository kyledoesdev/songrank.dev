@props(['stars' => null])

{{-- Half a star per click target, so 0.5 is the left side of the first star. --}}
<div
    class="mt-2"
    x-data="{ stars: @js($stars === null ? null : (float) $stars), hover: null }"
>
    <div class="flex items-center gap-0.5 text-2xl text-amber-400" @mouseleave="hover = null">
        @for ($i = 1; $i <= 10; $i++)
            <span class="relative inline-block w-5 h-6 leading-6">
                <i class="fa-regular fa-star absolute inset-0 text-zinc-300"></i>

                <i
                    class="fa-solid fa-star-half-stroke absolute inset-0"
                    x-show="(hover ?? stars) >= {{ $i - 0.5 }}"
                    x-cloak
                ></i>

                <i
                    class="fa-solid fa-star absolute inset-0"
                    x-show="(hover ?? stars) >= {{ $i }}"
                    x-cloak
                ></i>

                <button
                    type="button"
                    class="absolute inset-y-0 left-0 w-1/2 cursor-pointer"
                    aria-label="{{ $i - 0.5 }} out of 10"
                    @mouseenter="hover = {{ $i - 0.5 }}"
                    @click="stars = {{ $i - 0.5 }}; $wire.setStars('{{ $i - 0.5 }}')"
                ></button>

                <button
                    type="button"
                    class="absolute inset-y-0 right-0 w-1/2 cursor-pointer"
                    aria-label="{{ $i }} out of 10"
                    @mouseenter="hover = {{ $i }}"
                    @click="stars = {{ $i }}; $wire.setStars('{{ $i }}')"
                ></button>
            </span>
        @endfor
    </div>

    <div class="flex items-center gap-3 mt-2">
        <p class="text-sm text-zinc-600">
            <span x-text="(hover ?? stars) === null ? 'Not scored' : (hover ?? stars).toFixed(1) + ' / 10'"></span>
        </p>

        <button
            type="button"
            class="text-xs text-zinc-400 hover:text-zinc-600 cursor-pointer"
            x-show="stars !== null"
            x-cloak
            @click="stars = null; $wire.clearStars()"
        >
            Clear
        </button>
    </div>
</div>
