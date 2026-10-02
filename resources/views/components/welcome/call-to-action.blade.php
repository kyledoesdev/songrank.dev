@props(['content'])

<section class="pt-4 pb-20 px-4">
    <div class="max-w-3xl mx-auto text-center">
        <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">{{ $content->get('cta-title') }}</h2>
        <p class="text-lg text-gray-800/60 mb-8">{{ $content->get('cta-subtitle') }}</p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="{{ auth()->check() ? route('dashboard') : route('spotify.login') }}"
               class="btn-animated inline-flex items-center justify-center gap-2 px-10 py-4 text-lg font-bold text-gray-900 rounded-xl shadow-lg hover:shadow-xl transition-shadow cursor-pointer">
                <i class="fa-solid fa-play"></i>
                @auth
                    Go to Dashboard
                @else
                    Get Started
                @endauth
            </a>
            <a href="{{ route('explore') }}"
               class="inline-flex items-center justify-center gap-2 px-10 py-4 text-lg font-semibold rounded-xl bg-white/20 backdrop-blur-sm border border-white/30 text-gray-900 hover:bg-white/30 transition-all cursor-pointer">
                <i class="fa-solid fa-compass"></i>
                Explore
            </a>
        </div>

        @guest
            <p class="mt-6 mx-auto max-w-xs text-center text-[0.6875rem] leading-relaxed text-gray-800/60">
                You sign in with Spotify. By continuing you agree to our
                <a href="{{ route('terms') }}" class="underline underline-offset-2 hover:text-gray-900 transition-colors">Terms of Service</a>
                and
                <a href="{{ route('privacy') }}" class="underline underline-offset-2 hover:text-gray-900 transition-colors">Privacy Policy</a>.
            </p>
        @endguest
    </div>
</section>
