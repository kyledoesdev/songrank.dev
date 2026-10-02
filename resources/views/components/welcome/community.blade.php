@props(['content'])

<section class="py-16 px-4">
    <div class="max-w-6xl mx-auto">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 items-center">
            <div>
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-6">{{ $content->firstWhere('slug', 'community-title')->text }}</h2>
                <div class="space-y-6">
                    <div class="flex gap-4 items-start">
                        <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-white/20 flex items-center justify-center">
                            <i class="fa-solid fa-globe text-lg text-gray-900"></i>
                        </div>
                        <div>
                            <h4 class="font-bold text-gray-900">{{ $content->firstWhere('slug', 'community-explore-title')->text }}</h4>
                            <p class="text-gray-800/60">{{ $content->firstWhere('slug', 'community-explore-text')->text }}</p>
                        </div>
                    </div>
                    <div class="flex gap-4 items-start">
                        <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-white/20 flex items-center justify-center">
                            <i class="fa-solid fa-comments text-lg text-gray-900"></i>
                        </div>
                        <div>
                            <h4 class="font-bold text-gray-900">{{ $content->firstWhere('slug', 'community-comment-title')->text }}</h4>
                            <p class="text-gray-800/60">{{ $content->firstWhere('slug', 'community-comment-text')->text }}</p>
                        </div>
                    </div>
                    <div class="flex gap-4 items-start">
                        <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-white/20 flex items-center justify-center">
                            <i class="fa-solid fa-face-smile text-lg text-gray-900"></i>
                        </div>
                        <div>
                            <h4 class="font-bold text-gray-900">{{ $content->firstWhere('slug', 'community-react-title')->text }}</h4>
                            <p class="text-gray-800/60">{{ $content->firstWhere('slug', 'community-react-text')->text }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-white/10 shadow-xl overflow-hidden">
                <img src="{{ asset('images/ranking.gif') }}" alt="{{ config('app.name') }} ranking demo" class="w-full h-auto">
            </div>
        </div>
    </div>
</section>
