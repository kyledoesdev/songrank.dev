<div
    class="relative"
    :key="'card-'.$tierlist->getKey()"
>
    <x-tierlists.card :tierlist="$tierlist" />

    @if (auth()->id() === $tierlist->user_id && Route::currentRouteName() === 'profile')
        <div class="absolute top-2 right-2 flex flex-col space-y-1 mr-1 mt-1" :key="'tierlist-quick-actions-'.$tierlist->getKey()">
            <a
                class="text-gray-500 hover:text-green-600 transition-colors p-1 text-sm cursor-pointer"
                href="{{ route('tierlist.edit', ['id' => $tierlist->getKey()]) }}"
                title="Edit"
                @click="event.stopPropagation()"
            >
                <i class="fa fa-pencil text-sm md:text-lg"></i>
            </a>

            <button
                class="text-gray-500 hover:text-red-600 transition-colors p-1 text-sm cursor-pointer mt-1"
                @click="window.confirm({
                    title: 'Delete Tier List?',
                    message: 'Are you sure you want to delete this tier list?',
                    confirmText: 'Delete',
                    componentId: '{{ $this->getId() }}',
                    action: 'destroy',
                    styles: {
                        'confirm-btn': 'btn-danger m-2 p-2 text-white'
                    }
                })"
                title="Delete Tier List"
            >
                <i class="fa fa-trash text-sm md:text-lg"></i>
            </button>
        </div>
    @endif
</div>
