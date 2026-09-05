<div>
    <div class="pl-4 pr-4 bg-white shadow-lg rounded-lg mt-4">
        <div class="flex justify-center bg-white p-4">
            <div class="flex items-center space-x-2 k-line">
                <span class="text-xs sm:text-sm md:text-base whitespace-nowrap font-bold">
                    Progress will be saved automatically as you rank!
                </span>
            </div>
        </div>

        <!-- Progress Bar -->
        <div class="px-4 py-2">
            <div class="flex justify-between items-center mb-2">
                <span class="text-sm font-medium">Progress</span>
                <span class="text-sm text-gray-600">{{ $progressPercentage }}%</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2">
                <div class="bg-blue-600 h-2 rounded-full transition-all duration-300" 
                    style="width: {{ $progressPercentage }}%"></div>
            </div>
        </div>

        <div class="flex justify-center mb-4 md:mb-8 px-4">
            <span class="text-center">Directions: click on the song title button for the song you like more.</span>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 md:gap-8 px-2 md:px-4 overflow-x-hidden">
            @if($currentSong1 && $currentSong2)
                <x-songs.comparison-card :song="$currentSong1" :show-embeds="$showEmbeds" wire-key="song1-{{ $currentSong1['id'] }}" />
                <x-songs.comparison-card :song="$currentSong2" :show-embeds="$showEmbeds" wire-key="song2-{{ $currentSong2['id'] }}" />
            @endif
        </div>

        <hr class="my-4" />
        
        <div class="px-4 py-4">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <span class="hidden md:flex md:items-center text-gray-600">
                    <i class="fa-solid fa-mug-saucer mr-2"></i>
                    Get Cozy, this may take a while. Enjoy the process!
                </span>
                
                <div class="flex flex-wrap items-center gap-3 order-1 sm:order-1">
                    <button
                        class="btn-primary"
                        wire:click="undoLastChoice"
                        wire:loading.attr="disabled"
                        wire:target="undoLastChoice, chooseSong"
                        @disabled(! $canUndo)
                    >
                        <span wire:loading.remove wire:target="undoLastChoice">
                            <i class="fa-solid fa-rotate-left mr-1"></i>
                            Undo
                        </span>
                        <span wire:loading wire:target="undoLastChoice" class="inline-flex items-center gap-2">
                            <i class="fa-solid fa-spinner fa-spin"></i>
                        </span>
                    </button>

                    <livewire:song-rank.song-rank-progress-modal
                        :ranking="$ranking"
                        :songs="$ranking->songs"
                        :sorting-state="$sortingState"
                    />
                    
                    @unless ($ranking->isShowType())
                        <label class="flex items-center cursor-pointer select-none bg-helper hover:bg-blue-400 transition-colors rounded-md px-3 py-1.5">
                            <input
                                type="checkbox"
                                wire:model.live="showEmbeds"
                                class="w-4 h-4 text-blue-300 bg-white border-helper rounded-sm focus:ring-blue-400"
                            >
                            <span class="ml-2 text-sm text-zinc-800 whitespace-nowrap">Spotify Players</span>
                        </label>
                    @endunless
                </div>
            </div>
        </div>
    </div>
</div>