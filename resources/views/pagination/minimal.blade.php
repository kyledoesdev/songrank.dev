@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination Navigation" class="flex items-center justify-between gap-3 text-xs text-zinc-500">
        <span class="tabular-nums">
            {{ $paginator->firstItem() }} &ndash; {{ $paginator->lastItem() }}
            <span class="mx-1 text-zinc-400">of</span>
            {{ $paginator->total() }}
        </span>

        <div class="flex items-center gap-1">
            <button
                type="button"
                wire:click="previousPage('{{ $paginator->getPageName() }}')"
                wire:loading.attr="disabled"
                @disabled($paginator->onFirstPage())
                class="h-7 w-7 rounded-md flex items-center justify-center text-zinc-600 hover:bg-zinc-200 cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed disabled:hover:bg-transparent"
                title="Previous page"
            >
                <i class="fa-solid fa-chevron-left"></i>
            </button>

            <span class="px-1 tabular-nums">{{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}</span>

            <button
                type="button"
                wire:click="nextPage('{{ $paginator->getPageName() }}')"
                wire:loading.attr="disabled"
                @disabled(! $paginator->hasMorePages())
                class="h-7 w-7 rounded-md flex items-center justify-center text-zinc-600 hover:bg-zinc-200 cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed disabled:hover:bg-transparent"
                title="Next page"
            >
                <i class="fa-solid fa-chevron-right"></i>
            </button>
        </div>
    </nav>
@endif
