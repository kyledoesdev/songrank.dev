<div>
    @if ($open && $notification)
        <div
            class="fixed inset-0 z-50 flex items-center justify-center"
            x-data="{ shown: false }"
            x-init="$nextTick(() => shown = true)"
            @keydown.escape.window="shown = false; setTimeout(() => $wire.close(), 150)"
        >
            <div
                class="fixed inset-0 bg-black/50"
                x-show="shown"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                @click="shown = false; setTimeout(() => $wire.close(), 150)"
                style="display: none;"
            ></div>

            <div
                class="relative bg-white rounded-lg shadow-xl w-full max-w-md mx-4 p-6 z-10"
                x-show="shown"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95 translate-y-2"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                x-transition:leave-end="opacity-0 scale-95 translate-y-2"
                style="display: none;"
            >
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-semibold text-zinc-700 uppercase tracking-wider">Notification Details</h3>
                    <button
                        @click="shown = false; setTimeout(() => $wire.close(), 150)"
                        class="text-zinc-400 hover:text-zinc-600 cursor-pointer"
                        aria-label="Close"
                    >
                        <i class="fa fa-times"></i>
                    </button>
                </div>

                <x-notification-item :notification="$notification" :showViewBtn="false" />
            </div>
        </div>
    @endif
</div>
