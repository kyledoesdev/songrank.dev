<x-app-layout>
    <div class="flex justify-center mt-4">
        <x-card class="w-full max-w-lg p-6 sm:p-10 text-center">
            @if ($unsubscribeUrl)
                <i class="fa-solid fa-envelope-open-text text-4xl text-primary"></i>

                <h1 class="mt-4 text-xl sm:text-2xl font-semibold text-zinc-800">
                    Unsubscribe from these emails?
                </h1>

                <p class="mt-3 text-sm sm:text-base text-slate-600">
                    You'll stop getting them. Emails about your own account, like receipts, still arrive.
                </p>

                <form method="POST" action="{{ $unsubscribeUrl }}" class="mt-6">
                    @csrf
                    <button type="submit" class="btn-primary p-2">
                        Unsubscribe
                    </button>
                </form>
            @else
                <i class="fa-solid fa-envelope-circle-check text-4xl text-primary"></i>

                <h1 class="mt-4 text-xl sm:text-2xl font-semibold text-zinc-800">
                    You're unsubscribed
                </h1>

                <p class="mt-3 text-sm sm:text-base text-slate-600">
                    You won't get these emails any more. You can turn them back on any time in your settings.
                </p>

                <a href="{{ route('settings') }}" class="btn-secondary inline-block p-2 mt-6">
                    <i class="fa fa-gear mr-1"></i>
                    Email settings
                </a>
            @endif
        </x-card>
    </div>
</x-app-layout>
