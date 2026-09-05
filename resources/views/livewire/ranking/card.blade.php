<div
    class="relative"
    :key="'card-'.$ranking->getKey()"
>
    <x-rankings.card :ranking="$ranking" />

    @if (auth()->id() === $ranking->user_id && Route::currentRouteName() === 'profile')
        <div class="absolute top-2 right-2 flex flex-col space-y-1 mr-1 mt-1" :key="'ranking-quick-actions-'.$ranking->getKey()">
            <a 
                class="text-gray-500 hover:text-green-600 transition-colors p-1 text-sm cursor-pointer"
                href="{{ route('rank.edit', ['id' => $ranking->getKey() ]) }}"
                title="Edit"
                @click="event.stopPropagation()"
            >
                <i class="fa fa-pencil text-sm md:text-lg"></i>
            </a>
            
            <button 
                class="text-gray-500 hover:text-red-600 transition-colors p-1 text-sm cursor-pointer mt-1"
                @click="window.confirm({
                    title: 'Delete Ranking?',
                    message: 'Are you sure you want to delete this ranking?',
                    confirmText: 'Delete',
                    componentId: '{{ $this->getId() }}',
                    action: 'destroy',
                    styles: {
                        'confirm-btn': 'btn-danger m-2 p-2 text-white'
                    }
                })"
                title="Delete Ranking"
            >
                <i class="fa fa-trash text-sm md:text-lg"></i>
            </button>

            @if ($ranking->is_ranked)
                <button 
                    class="text-gray-500 hover:text-purple-600 transition-colors p-1 text-sm cursor-pointer mt-1"
                    wire:click="download"
                    title="Download"
                >
                    <i class="fa fa-file-csv text-sm lg:text-lg"></i>
                </button>
            @endif
        </div>
    @endif
</div>