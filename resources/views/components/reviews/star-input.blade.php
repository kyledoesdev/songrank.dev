@props(['stars' => null])

{{-- Every layer is always rendered and only its visibility changes, so a
     Livewire morph only ever patches attributes. Half a star per click target. --}}
<div class="mt-2" x-data="{ hover: null, get shown() { return this.hover ?? $wire.stars } }">
    <div class="flex items-center gap-0.5 text-2xl text-amber-400" @mouseleave="hover = null">
        @for ($i = 1; $i <= 10; $i++)
            <span class="relative inline-block w-5 h-6 leading-6">
                <i class="fa-regular fa-star absolute inset-0 text-zinc-300"></i>

                <i
                    class="fa-solid fa-star-half-stroke absolute inset-0"
                    style="display: {{ ! is_null($stars) && $stars >= $i - 0.5 && $stars < $i ? 'block' : 'none' }}"
                    x-show="shown !== null && shown >= {{ $i - 0.5 }} && shown < {{ $i }}"
                ></i>

                <i
                    class="fa-solid fa-star absolute inset-0"
                    style="display: {{ ! is_null($stars) && $stars >= $i ? 'block' : 'none' }}"
                    x-show="shown !== null && shown >= {{ $i }}"
                ></i>

                <button
                    type="button"
                    class="absolute inset-y-0 left-0 w-1/2 cursor-pointer"
                    aria-label="{{ $i - 0.5 }} out of 10"
                    @mouseenter="hover = {{ $i - 0.5 }}"
                    wire:click="setStars({{ $i - 0.5 }})"
                ></button>

                <button
                    type="button"
                    class="absolute inset-y-0 right-0 w-1/2 cursor-pointer"
                    aria-label="{{ $i }} out of 10"
                    @mouseenter="hover = {{ $i }}"
                    wire:click="setStars({{ $i }})"
                ></button>
            </span>
        @endfor
    </div>

    <div class="flex items-center gap-3 mt-2">
        <p class="text-sm text-zinc-600">
            <span x-text="shown === null ? 'Not scored' : shown.toFixed(1) + ' / 10'">{{ is_null($stars) ? 'Not scored' : number_format($stars, 1).' / 10' }}</span>
        </p>

        <button
            type="button"
            class="text-xs text-zinc-400 hover:text-zinc-600 cursor-pointer"
            style="display: {{ is_null($stars) ? 'none' : 'inline' }}"
            x-show="$wire.stars !== null"
            wire:click="clearStars"
        >
            Clear
        </button>
    </div>
</div>
