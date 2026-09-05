<div class="p-4 space-y-6">
    @if($duel)
        <div class="border border-yellow-300 bg-yellow-50 rounded-lg overflow-hidden">
            <div class="px-4 py-3 bg-yellow-100 border-b border-yellow-200">
                <h4 class="font-semibold text-yellow-800 text-center">
                    <i class="fa-solid fa-bolt mr-2"></i>
                    Current Matchup
                </h4>
            </div>
            <div class="p-4">
                <div class="flex items-center justify-center gap-2 sm:gap-4">
                    <div class="flex-1">
                        <x-songs.ranked-item :ranking="$ranking" :song="$duel['left']" />
                    </div>
                    <div class="flex-shrink-0">
                        <i class="fa-solid fa-khanda text-2xl sm:text-3xl text-yellow-600"></i>
                    </div>
                    <div class="flex-1">
                        <x-songs.ranked-item :ranking="$ranking" :song="$duel['right']" />
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if($best_order)
        <div class="border border-gray-200 bg-white rounded-lg overflow-hidden">
            <div class="px-4 py-3 bg-gray-50 border-b border-gray-200">
                <h4 class="font-semibold text-gray-800">
                    Best <em>Predicted</em> Order So Far
                    <span class="font-normal text-sm ml-2 text-gray-600">
                        ({{ $best_order['track_count'] }} of {{ $songs->count() }} tracks sorted)
                    </span>
                </h4>
                <p class="text-xs text-gray-500 mt-1">This ordering will improve as you continue comparing</p>
            </div>

            <div class="p-4">
                <div class="space-y-1">
                    @foreach($best_order['songs'] as $index => $song)
                        <div class="flex items-center gap-2 p-2 rounded bg-white border border-gray-100">
                            <span class="w-6 h-6 flex items-center justify-center bg-gray-700 text-white text-xs font-bold rounded flex-shrink-0">
                                {{ $index + 1 }}
                            </span>
                            <div class="flex-1 min-w-0">
                                <x-songs.ranked-item :ranking="$ranking" :song="$song" />
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    @if(!$current_merge && !$best_order)
        <div class="text-center py-8 text-gray-500">
            <i class="fa-solid fa-sort text-4xl mb-4"></i>
            <p>Start comparing to see your ordering take shape!</p>
        </div>
    @endif

    @if($current_merge)
        <div class="border border-gray-200 bg-gray-50 rounded-lg overflow-hidden">
            <div class="px-4 py-3">
                <label class="flex items-center cursor-pointer select-none">
                    <input 
                        type="checkbox" 
                        wire:model.live="showAdvancedStats"
                        class="w-4 h-4 text-purple-600 bg-gray-100 border-gray-300 rounded-sm focus:ring-purple-500"
                    >
                    <span class="ml-2 text-sm font-medium text-gray-700">Show Advanced Merge Details</span>
                </label>
            </div>

            @if($showAdvancedStats)
                <div class="border-t border-gray-200 bg-white">
                    <div class="px-4 py-3 bg-purple-100 border-b border-purple-200">
                        <h4 class="font-semibold text-purple-800">
                            Currently Merging
                            <span class="font-normal text-sm ml-2">
                                ({{ $current_merge['left_count'] }} tracks + {{ $current_merge['right_count'] }} tracks)
                            </span>
                        </h4>
                    </div>

                    <div class="p-4">
                        @if($current_merge['ranked_songs']->isNotEmpty())
                            <div class="mb-4">
                                <h5 class="text-sm font-medium text-gray-600 mb-2">Already Ranked in This Merge:</h5>
                                <div class="space-y-1">
                                    @foreach($current_merge['ranked_songs'] as $index => $song)
                                        <div class="flex items-center gap-2 p-2 rounded bg-green-50 border border-green-100">
                                            <span class="w-6 h-6 flex items-center justify-center bg-green-500 text-white text-xs font-bold rounded flex-shrink-0">
                                                {{ $index + 1 }}
                                            </span>
                                            <div class="flex-1 min-w-0">
                                                <x-songs.ranked-item :ranking="$ranking" :song="$song" />
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        @if($current_merge['left_songs']->isNotEmpty() || $current_merge['right_songs']->isNotEmpty())
                            <div>
                                <h5 class="text-sm font-medium text-gray-600 mb-2">Awaiting Comparison:</h5>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <span class="text-xs font-medium text-purple-600 mb-1 block">Left Side</span>
                                        <div class="space-y-1">
                                            @foreach($current_merge['left_songs'] as $index => $song)
                                                <div class="flex items-center gap-2 p-2 rounded bg-yellow-50 border border-yellow-200 {{ $index === 0 ? 'ring-2 ring-yellow-400' : '' }}">
                                                    @if($index === 0)
                                                        <span class="w-5 h-5 flex items-center justify-center bg-yellow-400 text-white text-xs rounded flex-shrink-0">
                                                            <i class="fa-solid fa-arrow-right"></i>
                                                        </span>
                                                    @endif
                                                    <div class="flex-1 min-w-0">
                                                        <x-songs.ranked-item :ranking="$ranking" :song="$song" />
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                    <div>
                                        <span class="text-xs font-medium text-purple-600 mb-1 block">Right Side</span>
                                        <div class="space-y-1">
                                            @foreach($current_merge['right_songs'] as $index => $song)
                                                <div class="flex items-center gap-2 p-2 rounded bg-yellow-50 border border-yellow-200 {{ $index === 0 ? 'ring-2 ring-yellow-400' : '' }}">
                                                    @if($index === 0)
                                                        <span class="w-5 h-5 flex items-center justify-center bg-yellow-400 text-white text-xs rounded flex-shrink-0">
                                                            <i class="fa-solid fa-arrow-right"></i>
                                                        </span>
                                                    @endif
                                                    <div class="flex-1 min-w-0">
                                                        <x-songs.ranked-item :ranking="$ranking" :song="$song" />
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    @endif
</div>