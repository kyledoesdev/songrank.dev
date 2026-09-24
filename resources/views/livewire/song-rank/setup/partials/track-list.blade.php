{{-- Livewire partial: expects $tracks, $type, and $keyPrefix; the rest are optional. --}}

@php
    $removed ??= collect();
    $title ??= null;
    $toggle ??= null;
    $searchPlaceholder ??= 'Search tracks...';
    $scroller ??= 'card-scroller';
    $albums ??= null;

    $tracks = collect($tracks);

    // Searching runs in Alpine rather than on the server: every row is its own nested Livewire
    // component, so a server side filter would re-render up to 500 of them per keystroke.
    $items = $tracks->map(fn ($song) => [
        'uuid' => $song['uuid'],
        'name' => Str::lower($song['name']),
    ])->values();
@endphp

<div
    x-data="{
        query: '',
        removed: @js($removed->values()),
        items: @js($items),
        matches(uuid, name) {
            if (this.removed.includes(uuid)) {
                return false;
            }

            return this.query === '' || name.includes(this.query.toLowerCase());
        },
        get visibleCount() {
            return this.items.filter(item => this.matches(item.uuid, item.name)).length;
        },
    }"
    @tracks-batch-removed.window="
        $event.detail.uuids.forEach(uuid => {
            if (! removed.includes(uuid)) {
                removed.push(uuid);
            }
        });
    "
    @track-removed.window="
        if (! removed.includes($event.detail.uuid)) {
            removed.push($event.detail.uuid);
        }
    "
    @tracks-batch-restored.window="
        $event.detail.uuids.forEach(uuid => {
            const idx = removed.indexOf(uuid);
            if (idx !== -1) removed.splice(idx, 1);
        });
    "
    class="border border-gray-200 bg-white rounded-lg overflow-hidden"
>
    <x-card.header class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
        @if (filled($title))
            <div class="flex items-center gap-3">
                <h4 class="font-semibold text-gray-800">
                    {{ $title }}
                    <span class="font-normal text-sm text-gray-600">(<span x-text="visibleCount"></span>)</span>
                </h4>

                @if (filled($toggle))
                    <x-toggle-switch
                        wire:model.live="{{ $toggle }}"
                        x-data
                        x-on:change="window.showLoader()"
                    >
                        Include
                    </x-toggle-switch>
                @endif
            </div>
        @endif

        <div class="flex items-center gap-2">
            <div class="relative w-full sm:w-56">
                <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-zinc-400 text-sm"></i>
                <input
                    type="search"
                    x-model="query"
                    placeholder="{{ $searchPlaceholder }}"
                    class="w-full pl-9 pr-3 py-2 text-sm bg-white border border-gray-200 rounded-lg transition-all duration-300 focus:ring-2 focus:ring-blue-400"
                />
            </div>

            <button
                type="button"
                class="btn-primary p-2 text-sm"
                @click="$dispatch('open-album-modal')"
                title="Filters"
            >
                <i class="fa-solid fa-sliders"></i>
            </button>

            @if (count($this->removedTrackUuids) > 0)
                <button
                    type="button"
                    class="btn-secondary p-2 text-sm cursor-pointer"
                    @click="$dispatch('open-removed-tracks-modal')"
                    title="Removed tracks"
                >
                    <i class="fa-solid fa-trash-can"></i>
                </button>
            @endif
        </div>
    </x-card.header>

    <div class="p-4">
        <div class="grid grid-cols-1 lg:grid-cols-2 lg:gap-1 {{ $scroller }}">
            @foreach ($tracks as $song)
                <div
                    wire:key="{{ $keyPrefix }}-wrapper-{{ $song['uuid'] }}"
                    data-track-uuid="{{ $song['uuid'] }}"
                    x-data="{ show: false }"
                    x-init="setTimeout(() => show = true, {{ min($loop->index * 30, 300) }})"
                    x-show="show && matches('{{ $song['uuid'] }}', @js(Str::lower($song['name'])))"
                    x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0 transform translate-x-4"
                    x-transition:enter-end="opacity-100 transform translate-x-0"
                    x-transition:leave="transition ease-in duration-200"
                    x-transition:leave-start="opacity-100 transform translate-x-0"
                    x-transition:leave-end="opacity-0 transform -translate-x-4"
                >
                    <livewire:song-rank.song-list-item
                        wire:key="{{ $keyPrefix }}-item-{{ $song['uuid'] }}"
                        :song="$song"
                        :type="$type"
                    />
                </div>
            @endforeach
        </div>

        <p class="text-sm text-zinc-500" x-show="visibleCount === 0" x-cloak>
            No {{ $type->itemLabel() }} match your search.
        </p>
    </div>

    @include('livewire.song-rank.setup.partials.album-filters', ['albums' => $albums])

    @if (count($this->removedTrackUuids) > 0)
        @include('livewire.song-rank.setup.partials.removed-tracks', [
            'removedTracks' => $this->removedTracks(),
        ])
    @endif
</div>
