@props(['content', 'features'])

<section id="pricing" class="py-16 px-4 scroll-mt-4">
    <div class="max-w-5xl mx-auto">
        <div class="text-center mb-8">
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900">{{ $content->get('pricing-title') }}</h2>
            <p class="mt-3 text-lg text-gray-800/60">{{ $content->get('pricing-subtitle') }}</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-stretch">
            <x-plans.card :plan="App\Enums\Billing\Plan::FREE" :features="$features->get('free', collect())">
                <x-slot:action>
                    <a href="{{ auth()->check() ? route('dashboard') : route('spotify.login') }}" class="btn-secondary w-full mx-0 my-0 py-3 inline-flex items-center justify-center gap-2 font-semibold text-zinc-800">
                        @if (auth()->user()?->is_pro)
                            <i class="fa-solid fa-house"></i>
                            Go to Dashboard
                        @else
                            <i class="fa-solid fa-seedling"></i>
                            Begin freely
                        @endif
                    </a>
                </x-slot:action>
            </x-plans.card>

            <x-plans.card :plan="App\Enums\Billing\Plan::PRO" :features="$features->get('pro', collect())">
                <x-slot:action>
                    @if (auth()->user()?->is_pro)
                        <a href="{{ route('billing') }}" class="btn-primary w-full mx-0 my-0 py-3 inline-flex items-center justify-center gap-2 font-semibold">
                            <i class="fa-solid fa-circle-check"></i>
                            You're Pro
                        </a>
                    @elseauth
                        <form method="POST" action="{{ route('billing.checkout') }}">
                            @csrf
                            <button type="submit" class="btn-primary w-full mx-0 my-0 py-3 inline-flex items-center justify-center gap-2 font-semibold">
                                <i class="fa-solid fa-star"></i>
                                Unlock forever
                            </button>
                        </form>
                    @else
                        <a href="{{ route('spotify.login', ['to' => 'billing']) }}" class="btn-primary w-full mx-0 my-0 py-3 inline-flex items-center justify-center gap-2 font-semibold">
                            <i class="fa-solid fa-star"></i>
                            Unlock forever
                        </a>
                    @endif
                </x-slot:action>
            </x-plans.card>
        </div>
    </div>
</section>
