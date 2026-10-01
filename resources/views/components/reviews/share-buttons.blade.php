@use('App\Enums\ShareTarget')

@props(['review'])

@php
    $url = route('review', ['id' => $review->getKey()]);
    $text = $review->shareText();
@endphp

<div {{ $attributes->class(['flex items-center gap-2 flex-wrap']) }}>
    <span class="text-xs text-zinc-500">Share</span>

    @foreach (ShareTarget::cases() as $target)
        <a
            href="{{ $target->intentUrl($text, $url) }}"
            target="_blank"
            rel="noopener noreferrer"
            class="btn-secondary px-2 py-1 text-sm"
            title="Share on {{ $target->label() }}"
        >
            <i class="{{ $target->icon() }}"></i>
        </a>
    @endforeach

    <button
        type="button"
        class="btn-helper px-2 py-1 text-sm"
        title="Copy link"
        x-data="{ copied: false }"
        @click="navigator.clipboard.writeText(@js($url)).then(() => { copied = true; setTimeout(() => copied = false, 2000) })"
    >
        <i class="fa-solid fa-link" x-show="!copied"></i>
        <i class="fa-solid fa-check" x-show="copied" x-cloak></i>
    </button>
</div>
