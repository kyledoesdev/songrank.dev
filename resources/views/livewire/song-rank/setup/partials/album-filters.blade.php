{{-- Livewire partial: expects $albums (nullable Collection from ArtistSetup::albums()). --}}

@php
    $albums = $albums ? collect($albums) : collect();
    $hasAlbums = $albums->isNotEmpty();
@endphp

<div
    x-data="{ showAlbumModal: false, activeTab: '{{ $hasAlbums ? 'album' : 'custom' }}' }"
    @open-album-modal.window="showAlbumModal = true"
>
    <template x-teleport="body">
        <div
            x-show="showAlbumModal"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-50 overflow-y-auto"
            @keydown.escape.window="showAlbumModal = false"
            x-cloak
        >
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
                <div
                    class="fixed inset-0 bg-gray-500/75 transition-opacity"
                    @click="showAlbumModal = false"
                ></div>

                <div
                    x-show="showAlbumModal"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-95"
                    class="relative inline-block w-full max-w-2xl bg-white rounded-lg text-left shadow-xl transform sm:my-8"
                >
                    <div class="flex items-center justify-between p-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Filters</h3>
                        <button
                            type="button"
                            class="text-gray-400 hover:text-gray-600 transition-colors cursor-pointer"
                            @click="showAlbumModal = false"
                            aria-label="Close"
                        >
                            <i class="fa-solid fa-xmark text-xl"></i>
                        </button>
                    </div>

                    @if ($hasAlbums)
                        <div class="border-b border-gray-200">
                            <nav class="flex -mb-px">
                                <button
                                    type="button"
                                    @click="activeTab = 'album'"
                                    :class="activeTab === 'album'
                                        ? 'border-purple-500 text-purple-600'
                                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                    class="px-6 py-3 text-sm font-medium border-b-2 transition-colors cursor-pointer"
                                >
                                    <i class="fa-solid fa-compact-disc mr-2"></i>
                                    Albums
                                </button>
                                <button
                                    type="button"
                                    @click="activeTab = 'single'"
                                    :class="activeTab === 'single'
                                        ? 'border-purple-500 text-purple-600'
                                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                    class="px-6 py-3 text-sm font-medium border-b-2 transition-colors cursor-pointer"
                                >
                                    <i class="fa-solid fa-music mr-2"></i>
                                    Singles
                                </button>
                                <button
                                    type="button"
                                    @click="activeTab = 'custom'"
                                    :class="activeTab === 'custom'
                                        ? 'border-purple-500 text-purple-600'
                                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                    class="px-6 py-3 text-sm font-medium border-b-2 transition-colors cursor-pointer"
                                >
                                    <i class="fa-solid fa-wand-magic-sparkles mr-2"></i>
                                    Quick Filters
                                </button>
                            </nav>
                        </div>
                    @endif

                    <div class="overflow-y-auto p-4" style="max-height: calc(70vh - 120px);">
                        @if ($hasAlbums)
                            {{-- Albums / Singles tabs --}}
                            <div x-show="activeTab !== 'custom'" class="space-y-2">
                                @foreach ($albums as $album)
                                    <div
                                        x-show="activeTab === '{{ $album['type'] }}'"
                                        wire:key="album-modal-{{ $album['id'] }}"
                                        class="flex items-center gap-3 p-2 rounded-lg hover:bg-zinc-50 transition-colors duration-150"
                                    >
                                        <img
                                            src="{{ $album['cover'] }}"
                                            alt="{{ $album['name'] }}"
                                            class="w-12 h-12 rounded-lg shrink-0"
                                        />
                                        <div class="min-w-0 flex-1">
                                            <p class="text-sm font-medium text-gray-800 truncate" title="{{ $album['name'] }}">
                                                {{ $album['name'] }}
                                            </p>
                                            <p class="text-xs text-zinc-400">
                                                {{ $album['selected_count'] }}/{{ $album['track_count'] }} tracks
                                            </p>
                                        </div>
                                        <input
                                            type="checkbox"
                                            @checked(! $album['none_selected'])
                                            wire:click="toggleAlbum('{{ $album['id'] }}')"
                                            class="rounded text-purple-500 focus:ring-purple-400 cursor-pointer shrink-0"
                                        />
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        {{-- Custom Filters tab --}}
                        <div x-show="activeTab === 'custom'" x-cloak class="space-y-3">
                            <p class="text-sm text-zinc-500 mb-4">
                                Quickly remove tracks matching common patterns.
                            </p>
                            <div class="flex flex-wrap gap-2">
                                <button
                                    type="button"
                                    class="btn-primary px-3 py-2 text-sm transform transition-all duration-300 hover:scale-105 active:scale-95 hover:shadow-md"
                                    wire:click="removeTracksMatching('remix')"
                                >
                                    Remove Remixes
                                </button>
                                <button
                                    type="button"
                                    class="btn-secondary px-3 py-2 text-sm transform transition-all duration-300 hover:scale-105 active:scale-95 hover:shadow-md"
                                    wire:click="removeTracksMatching('live from')"
                                >
                                    Remove "Live From" Tracks
                                </button>
                                <button
                                    type="button"
                                    class="btn-helper px-3 py-2 text-sm transform transition-all duration-300 hover:scale-105 active:scale-95 hover:shadow-md"
                                    wire:click="removeTracksMatching('instrumental')"
                                >
                                    Remove "Instrumental" Tracks
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>
