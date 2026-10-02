<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">

        @head

        <script src="https://kit.fontawesome.com/07b7751319.js" crossorigin="anonymous"></script>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css'])
    </head>
    <body class="bg-transparent p-1">
        <a
            href="{{ route('tierlist', ['id' => $tierlist->getKey()]) }}"
            target="_top"
            class="block bg-white shadow-md rounded-xl overflow-hidden border border-zinc-200 hover:shadow-lg transition-shadow"
        >
            <div class="flex gap-4 p-4">
                <x-tierlists.mini-board :tierlist="$tierlist" large class="shrink-0 w-44 sm:w-52" />

                <div class="flex-1 min-w-0 flex flex-col">
                    <h1 class="text-base sm:text-lg font-bold text-zinc-800 truncate" title="{{ $tierlist->name }}">
                        {{ $tierlist->name }}
                    </h1>

                    <div class="flex flex-wrap gap-2 mt-2">
                        <span class="inline-flex items-center gap-1 text-[10px] px-2 py-0.5 rounded-full bg-{{ $tierlist->type->color() }}-100 text-{{ $tierlist->type->color() }}-700 whitespace-nowrap">
                            <i class="fa-solid {{ $tierlist->type->icon() }}"></i>
                            by {{ $tierlist->type->label() }}
                        </span>

                        <span class="inline-flex items-center gap-1 text-[10px] px-2 py-0.5 rounded-full bg-zinc-100 text-zinc-600 whitespace-nowrap">
                            <i class="fa-solid fa-hashtag text-zinc-400"></i>
                            {{ $tierlist->items_count }} {{ $tierlist->type->itemLabel() }}
                        </span>
                    </div>

                    <div class="mt-auto pt-3 flex items-center gap-2 text-xs text-zinc-500 min-w-0">
                        @if ($tierlist->user->avatar)
                            <img src="{{ $tierlist->user->avatar }}" alt="" class="h-5 w-5 rounded-full object-cover shrink-0">
                        @endif
                        <span class="truncate">{{ $tierlist->user->name }}</span>
                        <span class="ml-auto shrink-0 font-semibold text-purple-600">
                            {{ config('app.name') }}
                            <i class="fa-solid fa-arrow-up-right-from-square text-[10px]"></i>
                        </span>
                    </div>
                </div>
            </div>
        </a>
    </body>
</html>
