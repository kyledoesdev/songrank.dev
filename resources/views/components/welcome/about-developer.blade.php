@props(['content'])

<section class="py-16 px-4">
    <div class="max-w-4xl mx-auto">
        <div class="bg-white/15 backdrop-blur-sm rounded-2xl p-8 md:p-12 border border-white/20 shadow-lg">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 items-center">
                <div class="flex justify-center">
                    <img src="{{ asset('images/me.jpg') }}" alt="Kyle" class="w-48 h-48 md:w-56 md:h-56 rounded-2xl border-4 border-gray-800 object-cover shadow-lg">
                </div>
                {{-- Bio --}}
                <div class="md:col-span-2 text-center md:text-left">
                    <h2 class="text-2xl md:text-3xl font-bold text-gray-900 mb-3">{{ $content->get('about-dev-title') }}</h2>
                    <p class="text-gray-800/70 leading-relaxed mb-4">
                        {{ $content->get('about-dev-text') }}
                    </p>
                    <div class="flex flex-wrap gap-3 justify-center md:justify-start">
                        <a href="https://kyledoes.dev" class="inline-flex items-center gap-2 px-4 py-2 bg-white/20 rounded-lg text-sm font-medium text-gray-900 hover:bg-white/30 transition-all" target="_blank">
                            <i class="fa-solid fa-globe"></i> My Website
                        </a>
                        <a target="_blank" href="https://github.com/kyledoesdev" class="inline-flex items-center gap-2 px-4 py-2 bg-white/20 rounded-lg text-sm font-medium text-gray-900 hover:bg-white/30 transition-all">
                            <i class="fa-brands fa-github"></i> GitHub
                        </a>
                        <a target="_blank" href="https://ko-fi.com/spacelampsix" class="inline-flex items-center gap-2 px-4 py-2 bg-white/20 rounded-lg text-sm font-medium text-gray-900 hover:bg-white/30 transition-all">
                            <i class="fa-solid fa-mug-hot"></i> Ko-fi
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
