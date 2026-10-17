<section class="py-16 px-4">
    <div class="max-w-6xl mx-auto">
        <div class="text-center mb-12">
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900">What People Are Saying</h2>
            <p class="mt-3 text-lg text-gray-800/60">Real users, real rankings</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            @for ($i = 0; $i < 3; $i++)
                <div class="bg-white/15 backdrop-blur-sm rounded-2xl p-8 border border-white/20 shadow-lg">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded-full bg-gray-900/20 flex items-center justify-center">
                            <i class="fa-solid fa-user text-gray-600"></i>
                        </div>
                        <div>
                            <p class="font-semibold text-gray-900 text-sm">Testimonial Placeholder</p>
                            <p class="text-xs text-gray-800/50">@username</p>
                        </div>
                    </div>
                    <p class="text-gray-800/60 italic">"This is a placeholder for a future testimonial from a real {{ config('app.name') }} user."</p>
                </div>
            @endfor
        </div>
    </div>
</section>
