<div>
    @if (Auth::user()->is_pro)
        <div class="mt-2 bg-white shadow-lg p-4 md:p-6 rounded-xl">

            <!-- Plan Summary -->
            <div class="bg-white rounded-xl p-6 shadow-md border border-slate-100">
                <div class="flex items-center gap-2">
                    <h4 class="text-lg font-semibold text-slate-900">{{ config('app.name') }} Pro</h4>

                    <span class="inline-flex items-center gap-1 rounded-full bg-purple-100 px-2.5 py-0.5 text-xs font-medium text-purple-700">
                        <i class="fa fa-solid fa-star"></i>
                        Pro
                    </span>
                </div>

                <p class="text-sm text-slate-600 mt-1">
                    Thanks for supporting {{ config('app.name') }}. Everything we build for Pro is included on this license.
                </p>

                <p class="text-xs text-slate-500 mt-2 font-semibold">
                    Your license belongs to this account. Deleting your account ends it, and it cannot be transferred or restored.
                </p>
            </div>

            @if ($license)
                <!-- License details -->
                <div class="mt-4 bg-white rounded-xl p-6 shadow-md">
                    <h5 class="font-semibold mb-6">Your Purchase</h5>

                    <x-billing.license-details :license="$license" />
                </div>
            @endif
        </div>
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
                <div class="mt-6 bg-white rounded-xl p-6 shadow-md">
                    <h5 class="font-semibold mb-6">Your Previous Purchase</h5>

                    <x-billing.license-details :license="$license" />
                </div>
            @endif
        </div>
    @endif
</div>
