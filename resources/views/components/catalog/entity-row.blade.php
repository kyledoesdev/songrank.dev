@props(['entity'])

@php($cover = $entity->cover())

<div {{ $attributes->class(['flex items-center gap-3 p-2 rounded-xl border border-zinc-200']) }}>
    @if ($cover)
        <img src="{{ $cover }}" alt="{{ $entity->name }}" class="w-12 h-12 rounded-lg object-cover border border-zinc-200 shrink-0">
    @else
        <div class="w-12 h-12 rounded-lg bg-zinc-100 flex items-center justify-center shrink-0">
            <i class="fa-solid {{ $entity->type->icon() }} text-zinc-400"></i>
        </div>
    @endif

    <div class="min-w-0 flex-1">
        <p class="text-sm font-medium text-zinc-800 truncate" title="{{ $entity->name }}">{{ $entity->name }}</p>
        <p class="text-xs text-zinc-500 truncate">
            {{ $entity->label() }}

            @if ($entity->progressLabel())
                <span class="ml-1 px-1.5 py-0.5 rounded bg-amber-50 text-amber-700">{{ $entity->progressLabel() }}</span>
            @endif
        </p>
    </div>

    {{ $slot }}
</div>
