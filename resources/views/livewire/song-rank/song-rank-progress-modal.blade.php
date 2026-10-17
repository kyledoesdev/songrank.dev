<div x-data="{ showModal: false, activeTab: 'order' }">
    <button 
        type="button"
        class="btn-secondary"
        @click="showModal = true; $wire.refreshData()"
    >
        <i class="fa-solid fa-list-check mr-1"></i>
        View Progress
    </button>

    <template x-teleport="body">
        <div 
            x-show="showModal"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-50 overflow-y-auto"
            @keydown.escape.window="showModal = false"
            x-cloak
        >
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
                <div 
                    class="fixed inset-0 bg-gray-500/75 transition-opacity"
                    @click="showModal = false"
                ></div>

                <div 
                    x-show="showModal"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-95"
                    class="relative inline-block w-full max-w-4xl bg-white rounded-lg text-left shadow-xl transform sm:my-8"
                >
                    <div class="flex items-center justify-between p-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Ranking Progress</h3>
                        <button 
                            type="button"
                            class="text-gray-400 hover:text-gray-600 transition-colors cursor-pointer"
                            @click="showModal = false"
                            aria-label="Close"
                        >
                            <i class="fa-solid fa-xmark text-xl"></i>
                        </button>
                    </div>

                    <div class="border-b border-gray-200">
                        <nav class="flex -mb-px">
                            <button 
                                type="button"
                                @click="activeTab = 'order'"
                                :class="activeTab === 'order' 
                                    ? 'border-purple-500 text-purple-600' 
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                class="px-6 py-3 text-sm font-medium border-b-2 transition-colors cursor-pointer"
                            >
                                <i class="fa-solid fa-sort mr-2"></i>
                                Current Order
                            </button>
                            <button 
                                type="button"
                                @click="activeTab = 'tracks'"
                                :class="activeTab === 'tracks' 
                                    ? 'border-purple-500 text-purple-600' 
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                class="px-6 py-3 text-sm font-medium border-b-2 transition-colors cursor-pointer"
                            >
                                <i class="fa-solid fa-music mr-2"></i>
                                All Tracks
                            </button>
                        </nav>
                    </div>

                    <div class="overflow-y-auto" style="max-height: calc(85vh - 120px);">
                        <div x-show="activeTab === 'order'" x-cloak>
                            <livewire:song-rank.progress-tabs.current-order
                                :ranking="$ranking"
                                :songs="$ranking->songs" 
                                :sorting-state="$sortingState"
                                :key="'order-' . $refreshKey" {{-- todo - hate this --}}
                            />
                        </div>
                        <div x-show="activeTab === 'tracks'" x-cloak>
                            <livewire:song-rank.progress-tabs.tracks-list 
                                :ranking="$ranking"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>