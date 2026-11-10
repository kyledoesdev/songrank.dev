{{-- Livewire partial: expects $removedEntries (Collection of entry arrays) and $type. --}}

<div
    x-data="{ showRemovedModal: false }"
    @open-removed-entries-modal.window="showRemovedModal = true"
>
    <template x-teleport="body">
        <div
            x-show="showRemovedModal"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-50 overflow-y-auto"
            @keydown.escape.window="showRemovedModal = false"
            x-cloak
        >
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
                <div
                    class="fixed inset-0 bg-gray-500/75 transition-opacity"
                    @click="showRemovedModal = false"
                ></div>

                <div
                    x-show="showRemovedModal"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-95"
                    class="relative inline-block w-full max-w-2xl bg-white rounded-lg text-left shadow-xl transform sm:my-8"
                >
                    <div class="flex items-center justify-between p-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">
                            Removed {{ $type->itemLabel() }}
                            <span class="font-normal text-sm text-gray-600">({{ $removedEntries->count() }})</span>
                        </h3>

                        <div class="flex items-center gap-3">
                            @if ($removedEntries->count() > 1)
                                <button
                                    type="button"
                                    class="text-xs px-2 py-1 rounded-lg text-red-600 hover:bg-red-50 transition-colors cursor-pointer"
                                    wire:click="clearRemovedEntries"
                                    @click="showRemovedModal = false"
                                >
                                    Clear all
                                </button>
                            @endif

                            <button
                                type="button"
                                class="text-gray-400 hover:text-gray-600 transition-colors cursor-pointer"
                                @click="showRemovedModal = false"
                                aria-label="Close"
                            >
                                <i class="fa-solid fa-xmark text-xl"></i>
                            </button>
                        </div>
                    </div>

                    <div class="overflow-y-auto p-4 space-y-2" style="max-height: calc(70vh - 80px);">
                        @foreach ($removedEntries as $entry)
                            @php($subtitle = $this->subtitleFor($entry))

                            <div
                                wire:key="removed-entry-{{ $entry['id'] }}"
                                class="flex items-center gap-3 p-2 rounded-lg hover:bg-zinc-50 transition-colors duration-150"
                            >
                                <img
                                    src="{{ $entry['cover'] }}"
                                    alt="{{ $entry['name'] }}"
                                    class="w-12 h-12 rounded-lg shrink-0"
                                />
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-medium text-gray-800 truncate" title="{{ $entry['name'] }}">
                                        {{ $entry['name'] }}
                                    </p>
                                    @if ($subtitle)
                                        <p class="text-xs text-zinc-500 truncate">{{ $subtitle }}</p>
                                    @endif
                                </div>
                                <button
                                    type="button"
                                    class="btn-secondary px-2 py-1 text-xs shrink-0"
                                    wire:click="restoreEntry('{{ $entry['id'] }}')"
                                >
                                    <i class="fa-solid fa-arrow-rotate-left mr-1"></i> Restore
                                </button>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>
