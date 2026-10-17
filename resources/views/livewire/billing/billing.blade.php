<div>
    @if (Auth::user()->is_pro)
        <x-card class="mt-2 p-4 md:p-6">

            <!-- Plan Summary -->
            <x-card>
                <x-card.header class="flex items-center gap-2">
                    <h4 class="text-lg font-semibold text-slate-900">{{ config('app.name') }} Pro</h4>

                    <span class="inline-flex items-center gap-1 rounded-full bg-purple-100 px-2.5 py-0.5 text-xs font-medium text-purple-700">
                        <i class="fa fa-solid fa-star"></i>
                        Pro
                    </span>
                </x-card.header>

                <div class="p-4">
                    <p class="text-sm text-slate-600">
                        Thanks for supporting {{ config('app.name') }}. Everything built for Pro is included on this license.
                    </p>

                    <p class="text-xs text-slate-500 mt-2 font-semibold">
                        Your license belongs to this account.
                    </p>
                </div>
            </x-card>

            @if ($license)
                <!-- License details -->
                <x-billing.license-details :license="$license" title="Your Purchase" class="mt-4" />
            @endif
        </x-card>
    @else
        <div class="mt-2 max-w-5xl mx-auto">
            <div class="text-center my-6">
                <h4 class="text-2xl font-bold text-zinc-800">You're on the Free Plan</h4>
                <p class="mt-2 text-zinc-600">One payment unlocks Pro for good. No subscription.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-stretch">
                <x-plans.card :plan="App\Enums\Billing\Plan::FREE" :features="$features->get('free', collect())">
                    <x-slot:action>
                        <div class="w-full py-3 inline-flex items-center justify-center gap-2 rounded-md bg-zinc-100 text-sm font-semibold text-zinc-500">
                            <i class="fa-solid fa-circle-check"></i>
                            Your current plan
                        </div>
                    </x-slot:action>
                </x-plans.card>

                <x-plans.card :plan="App\Enums\Billing\Plan::PRO" :features="$features->get('pro', collect())">
                    <x-slot:action>
                        <form method="POST" action="{{ route('billing.checkout') }}">
                            @csrf
                            <button type="submit" class="btn-primary w-full mx-0 my-0 py-3 inline-flex items-center justify-center gap-2 font-semibold">
                                <i class="fa-solid fa-star"></i>
                                Unlock forever
                            </button>
                        </form>
                    </x-slot:action>
                </x-plans.card>
            </div>

            @if ($license)
                <!-- A past purchase that no longer grants Pro, e.g. refunded -->
                <x-billing.license-details :license="$license" title="Your Previous Purchase" class="mt-6" />
            @endif
        </div>
    @endif
</div>
