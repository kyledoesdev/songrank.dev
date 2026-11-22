@props(['user'])

@if ($user?->is_pro)
    @php
        $since = $user->formattedProSince();
        $label = $since ? config('app.name')." Pro since {$since}" : config('app.name').' Pro';
    @endphp

    <span
        {{ $attributes->class(['relative inline-flex shrink-0 items-center justify-center align-middle']) }}
        x-data="{ open: false, badge: null }"
        x-init="badge = $el"
        x-on:mouseenter="open = true"
        x-on:mouseleave="open = false"
        role="img"
        aria-label="{{ $label }}"
        data-pro-badge
    >
        <i class="fa-solid fa-certificate text-primary-icon" aria-hidden="true"></i>
        <i class="fa-solid fa-check absolute text-[0.5em] text-white" aria-hidden="true"></i>

        {{-- Teleported, because most names sit in a truncated row that would clip it. --}}
        <template x-if="open">
            <template x-teleport="body">
                <span
                    x-anchor.top.offset.6="badge"
                    class="z-50 pointer-events-none whitespace-nowrap rounded-md bg-zinc-800 px-2 py-1 text-xs font-medium text-white shadow-lg"
                    role="tooltip"
                >
                    {{ $label }}
                </span>
            </template>
        </template>
    </span>
@endif
