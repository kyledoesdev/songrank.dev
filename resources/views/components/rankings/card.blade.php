@props(['ranking'])

<div class="bg-white shadow-md rounded-xl cursor-pointer hover:shadow-lg transition-all duration-300 p-4" onclick="window.location.href='{{ route('ranking', ['id' => $ranking->getKey()]) }}'">
    <div class="flex gap-5">
        {{-- Cover + Spotify Logo --}}
        <div class="shrink-0">
            <img
                src="{{ $ranking->source?->cover() }}"
                class="w-24 h-24 sm:w-28 sm:h-28 rounded-xl border border-zinc-200 object-cover shadow-sm"
                alt="{{ $ranking->source?->name() }}"
            >
            <div class="mt-2 relative z-10">
                <x-spotify-logo :url="$ranking->source?->spotifyUrl()" />
            </div>
        </div>

        {{-- Content --}}
        <div class="flex-1 min-w-0">
            {{-- Title + Badge --}}
            <div class="flex items-center gap-2 flex-wrap mb-1">
                <h5 class="text-base md:text-lg font-bold truncate" title="{{ $ranking->name }}">
                    {{ Str::limit($ranking->name, 30) }}
                </h5>
                <span class="inline-flex items-center gap-1 text-[10px] px-2 py-0.5 rounded-full bg-{{ $ranking->type->color() }}-100 text-{{ $ranking->type->color() }}-700 whitespace-nowrap">
                    <i class="fa-solid {{ $ranking->type->icon() }}"></i>
                    {{ $ranking->type->label() }}
                </span>
            </div>

            {{-- Source Name --}}
            <p class="text-sm text-zinc-500 mb-3 truncate">
                {{ $ranking->source?->name() }}
            </p>

            {{-- Stats Pills --}}
            <div class="flex flex-wrap gap-2 mb-3">
                {{-- Top Song --}}
                <span class="inline-flex items-center gap-1.5 text-xs bg-zinc-100 text-zinc-600 px-2.5 py-1 rounded-lg">
                    <i class="fa-regular fa-star text-amber-500"></i>
                    {{ $ranking->is_ranked ? Str::limit($ranking->songs->first()?->title, 20) : 'N/A' }}
                </span>

                {{-- Track Count --}}
                <span class="inline-flex items-center gap-1.5 text-xs bg-zinc-100 text-zinc-600 px-2.5 py-1 rounded-lg">
                    <i class="fa-solid fa-hashtag text-zinc-400"></i>
                    {{ $ranking->songs_count }} {{ $ranking->type->itemLabel() }}
                </span>

                {{-- Status (only show if incomplete) --}}
                @unless ($ranking->is_ranked)
                    <span class="inline-flex items-center gap-1.5 text-xs px-2.5 py-1 rounded-lg bg-amber-50 text-amber-700">
                        <i class="fa-solid fa-spinner"></i>
                        In Progress
                    </span>
                @endunless
            </div>

            {{-- Footer: User + Time --}}
            <div class="flex items-center gap-3 text-xs text-zinc-400">
                @if (Route::currentRouteName() != 'profile')
                    <div class="relative z-10">
                        <a href="{{ route('profile', ['id' => $ranking->user->spotify_id]) }}" class="inline-flex items-center gap-1 hover:text-zinc-600 transition-colors">
                            <i class="fa-regular fa-user"></i>
                            {{ $ranking->user->name }}
                            <i class="fa fa-arrow-up-right-from-square text-blue-500"></i>
                        </a>
                    </div>
                    <span class="text-zinc-300">|</span>
                @endif
                <span title="{{ $ranking->formatted_completed_at }}">
                    <i class="fa-regular fa-clock mr-0.5"></i>
                    {{ $ranking->completed_at }}
                </span>
            </div>
        </div>
    </div>
</div>
