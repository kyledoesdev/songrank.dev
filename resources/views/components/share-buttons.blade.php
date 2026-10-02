@use('App\Enums\ShareTarget')

@props(['url', 'text', 'embed' => null])

<div
    {{ $attributes->class(['relative']) }}
    x-data="{ open: false, copied: false, embedCopied: false }"
    @click.outside="open = false"
    @keydown.escape.window="open = false"
>
    <button type="button" class="btn-helper my-0 px-2 py-1" title="Share" @click="open = ! open">
        <i class="fa-solid fa-share-nodes text-sm"></i>
    </button>

    <div
        class="absolute right-0 z-20 mt-1 w-44 rounded-lg border border-zinc-200 bg-white py-1 text-sm shadow-lg"
        style="display: none"
        x-show="open"
        x-transition
    >
        @foreach (ShareTarget::cases() as $target)
            <a
                href="{{ $target->intentUrl($text, $url) }}"
                target="_blank"
                rel="noopener noreferrer"
                class="flex items-center gap-2 px-3 py-2 text-zinc-700 hover:bg-zinc-50"
                @click="open = false"
            >
                <i class="{{ $target->icon() }} w-4 text-center"></i>
                Share on {{ $target->label() }}
            </a>
        @endforeach

        <button
            type="button"
            class="flex w-full items-center gap-2 px-3 py-2 text-zinc-700 hover:bg-zinc-50 cursor-pointer"
            @click="navigator.clipboard.writeText(@js($url)).then(() => { copied = true; setTimeout(() => { copied = false; open = false }, 1200) })"
        >
            <i class="fa-solid fa-link w-4 text-center" x-show="! copied"></i>
            <i class="fa-solid fa-check w-4 text-center text-green-500" style="display: none" x-show="copied"></i>
            <span x-text="copied ? 'Link copied' : 'Copy link'">Copy link</span>
        </button>

        @if ($embed)
            <button
                type="button"
                class="flex w-full items-center gap-2 px-3 py-2 text-zinc-700 hover:bg-zinc-50 cursor-pointer"
                @click="navigator.clipboard.writeText(@js($embed)).then(() => { embedCopied = true; setTimeout(() => { embedCopied = false; open = false }, 1200) })"
            >
                <i class="fa-solid fa-code w-4 text-center" x-show="! embedCopied"></i>
                <i class="fa-solid fa-check w-4 text-center text-green-500" style="display: none" x-show="embedCopied"></i>
                <span x-text="embedCopied ? 'Embed copied' : 'Copy embed code'">Copy embed code</span>
            </button>
        @endif
    </div>
</div>
