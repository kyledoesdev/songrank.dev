@props([
    'code',
    'heading',
    'message',
    'showSupport' => false,
])

@php
    /**
     * Livewire renders failed responses inside a full screen modal iframe, so
     * the navigation, footer and support bubble would only be noise in there.
     */
    $isLivewireRequest = request()->hasHeader('X-Livewire');

    $homeRoute = auth()->check() ? route('dashboard') : route('welcome');
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        @head

        <!-- Fonts -->
        <script src="https://kit.fontawesome.com/07b7751319.js" crossorigin="anonymous"></script>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=fredoka:400,500,600" rel="stylesheet">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @if ($isLivewireRequest)
            @vite(['resources/css/app.css'])
        @else
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif
    </head>
    <body class="flex flex-col min-h-screen gradient-background">
        <main class="flex-1" id="app">
            <div class="container mx-auto p-4">
                @unless ($isLivewireRequest)
                    <livewire:navigation />
                @endunless

                <div class="flex justify-center mt-4">
                    <div class="bg-white rounded-lg shadow-md w-full max-w-2xl p-6 sm:p-10 text-center">
                        {{-- Status code --}}
                        <p class="mt-6">
                            <span class="inline-block text-6xl sm:text-7xl font-bold bg-gradient-to-r from-purple-500 to-green-400 bg-clip-text text-transparent">
                                {{ $code }}
                            </span>
                        </p>

                        {{-- Little equalizer, just for fun --}}
                        <div class="mt-4 flex items-end justify-center gap-1 h-6" aria-hidden="true">
                            <span class="w-1.5 h-3 rounded-full bg-primary animate-bounce"></span>
                            <span class="w-1.5 h-5 rounded-full bg-secondary animate-bounce [animation-delay:150ms]"></span>
                            <span class="w-1.5 h-4 rounded-full bg-primary animate-bounce [animation-delay:300ms]"></span>
                            <span class="w-1.5 h-6 rounded-full bg-secondary animate-bounce [animation-delay:450ms]"></span>
                            <span class="w-1.5 h-3 rounded-full bg-primary animate-bounce [animation-delay:600ms]"></span>
                        </div>

                        <h1 class="mt-6 text-xl sm:text-2xl font-semibold text-zinc-800">
                            {{ $heading }}
                        </h1>

                        <p class="mt-3 text-sm sm:text-base text-slate-600 max-w-md mx-auto">
                            {{ $message }}
                        </p>

                        {{ $slot }}

                        {{-- Actions --}}
                        <div class="mt-8 flex flex-wrap items-center justify-center gap-1">
                            @if ($isLivewireRequest)
                                <button type="button" class="btn-primary p-2" onclick="window.top.location.reload()">
                                    <i class="fa fa-rotate-right mr-1"></i>
                                    Try that again
                                </button>
                                <a href="{{ $homeRoute }}" class="btn-secondary p-2">
                                    <i class="fa fa-house mr-1"></i>
                                    {{ auth()->check() ? 'My rankings' : 'Take me home' }}
                                </a>
                            @else
                                <a href="{{ $homeRoute }}" class="btn-primary p-2">
                                    <i class="fa fa-house mr-1"></i>
                                    {{ auth()->check() ? 'My rankings' : 'Take me home' }}
                                </a>
                                <a href="{{ route('explore') }}" class="btn-secondary p-2">
                                    <i class="fa fa-compass mr-1"></i>
                                    Explore rankings
                                </a>
                            @endif
                        </div>

                        {{-- Support --}}
                        @if ($showSupport)
                            <div class="mt-8 pt-6 border-t border-primary-soft">
                                <p class="text-sm text-slate-600">
                                    Still stuck? We would love to hear about it.
                                </p>

                                <div class="mt-2 flex flex-wrap items-center justify-center gap-1">
                                    @if (auth()->check() && ! $isLivewireRequest)
                                        <button
                                            type="button"
                                            class="btn-helper p-2"
                                            x-data
                                            x-on:click="document.querySelector('.spatie-support-bubble__button button')?.click()"
                                        >
                                            <i class="fa fa-comment-dots mr-1"></i>
                                            Open the support bubble
                                        </button>
                                    @endif

                                    <a href="https://discord.gg/zXe9kqyFEJ" target="_blank" class="btn-helper p-2">
                                        <i class="fa-brands fa-discord mr-1"></i>
                                        Ask on Discord
                                    </a>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </main>

        @unless ($isLivewireRequest)
            <footer class="bg-dark text-light">
                @include('layouts.partials.footer')

                @auth
                    <x-support-bubble />
                @endauth
            </footer>
        @endunless
    </body>
</html>
