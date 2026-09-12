{{-- Livewire partial: expects $modes, an array of value => label. $mode comes from the including component. --}}

{{--
    Alpine owns which pill looks active so the highlight moves on click rather
    than after the round trip. The server still does the switching -- it clears
    the results and the source -- and re-syncs `active` on the way back, so a
    failed request cannot leave the pill lying about what mode we are in.
--}}
<div
    class="px-2 pb-2 flex flex-wrap gap-2"
    x-data="{ active: @js($mode) }"
    x-effect="active = $wire.mode"
>
    @foreach ($modes as $value => $label)
        <button
            type="button"
            wire:key="mode-{{ $value }}"
            x-on:click="active = @js($value); $wire.switchMode(@js($value))"
            class="text-xs px-3 py-1.5 rounded-full border transition-all duration-300 cursor-pointer"
            x-bind:class="active === @js($value)
                ? 'border-purple-400 bg-purple-50 text-purple-700'
                : 'border-zinc-200 bg-white text-zinc-500 hover:border-zinc-300'"
        >
            {{ $label }}
        </button>
    @endforeach
</div>
