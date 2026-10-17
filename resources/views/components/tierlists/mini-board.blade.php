@props(['tierlist', 'large' => false])

@php
    $label = $large ? 'w-8 text-xs' : 'w-5 text-[8px]';
    $tile = $large ? 'w-8 h-8' : 'w-5 h-5';
@endphp

<div {{ $attributes->class(['flex flex-col gap-0.5']) }}>
    @forelse ($tierlist->tiers as $tier)
        <div class="flex items-stretch rounded overflow-hidden">
            <div
                class="{{ $label }} shrink-0 flex items-center justify-center font-bold text-zinc-800"
                style="background-color: {{ $tier->color }}"
            >
                {{ Str::limit($tier->name, 2, '') }}
            </div>
            <div class="flex-1 flex gap-px bg-zinc-100 {{ $large ? 'min-h-8' : 'min-h-5' }}">
                @foreach ($tier->items->take(5) as $item)
                    <img
                        src="{{ $item->entryable->cover() }}"
                        class="{{ $tile }} object-cover"
                        alt="{{ $item->entryable->name() }}"
                    >
                @endforeach
            </div>
        </div>
    @empty
        <div class="w-full h-24 rounded bg-zinc-100 flex items-center justify-center">
            <i class="fa-solid {{ $tierlist->type->icon() }} text-zinc-300 text-lg"></i>
        </div>
    @endforelse
</div>
