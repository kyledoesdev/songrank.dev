@props(['content'])

<section class="pt-4 pb-12 md:py-12 px-4">
    <div class="flex justify-center items-center gap-3 md:gap-4">
        <img src="{{ logo() }}" alt="{{ config('app.name') }} Logo" class="w-10 h-10 md:w-14 md:h-14 lg:w-16 lg:h-16 rounded-xl shadow-md">
        <h5 class="text-4xl md:text-6xl lg:text-7xl md:mb-2 font-bold">{{ $content->get('hero-title') }}</h5>
    </div>
    <div class="flex justify-center">
        <h5 class="text-xs mt-1 md:text-lg">{{ $content->get('hero-subtitle') }}</h5>
    </div>
    <div class="flex justify-center mt-1">
        <span>{{ $content->get('hero-credit') }}
            <a class="text-blue-600" href="https://bsky.app/profile/kyledoes.dev">
                {{ $content->get('hero-credit-name') }}
            </a>
        </span>
    </div>
</section>
