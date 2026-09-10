<div>
    <div class="mt-2 bg-white shadow-lg p-4 md:p-6 rounded-xl">

        <!-- Plan Summary -->
        <div class="bg-white rounded-xl p-6 shadow-md border border-slate-100">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2">
                        <h4 class="text-lg font-semibold text-slate-900">
                            {{ Auth::user()->is_pro ? 'Song Rank Pro' : 'Free Plan' }}
                        </h4>

                        @if (Auth::user()->is_pro)
                            <span class="inline-flex items-center gap-1 rounded-full bg-purple-100 px-2.5 py-0.5 text-xs font-medium text-purple-700">
                                <i class="fa fa-solid fa-star"></i>
                                Pro
                            </span>
                        @endif
                    </div>

                    <p class="text-sm text-slate-600 mt-1">
                        @if (Auth::user()->is_pro)
                            Thanks for supporting SongRank. Everything we build for Pro is included on this license.
                        @endif
                    </p>

                    @if (Auth::user()->is_pro)
                        <p class="text-xs text-slate-500 mt-2 font-semibold">
                            Your license belongs to this account. Deleting your account ends it, and it cannot be transferred or restored.
                        </p>
                    @endif
                </div>

                @unless (Auth::user()->is_pro)
                    <form method="POST" action="{{ route('billing.checkout') }}">
                        @csrf
                        <button type="submit" class="btn-primary p-3 text-center whitespace-nowrap cursor-pointer">
                            <i class="fa fa-solid fa-star mr-1"></i>
                            Go Pro &mdash; $10 once
                        </button>
                    </form>
                @endunless
            </div>
        </div>

        <div class="mt-4">

            <!-- License details -->
            <div class="bg-white rounded-xl p-6 shadow-md">
                <div class="flex items-center mb-6">
                    <h5 class="font-semibold">{{ $license ? 'Your Purchase' : 'What Pro Includes' }}</h5>
                </div>

                @if ($license)
                    <dl class="space-y-3 text-sm">
                        <div class="flex items-start justify-between gap-4">
                            <dt class="text-slate-600">License ID</dt>
                            <dd class="font-mono text-xs text-slate-900 break-all text-right">{{ $license->uuid }}</dd>
                        </div>

                        <div class="flex items-center justify-between gap-4">
                            <dt class="text-slate-600">Status</dt>
                            <dd class="font-medium text-slate-900">
                                <i class="fa fa-solid {{ $license->status->icon() }} mr-1"></i>
                                {{ $license->status->label() }}
                            </dd>
                        </div>

                        @if ($license->purchased_at)
                            <div class="flex items-center justify-between gap-4">
                                <dt class="text-slate-600">Purchased</dt>
                                <dd class="font-medium text-slate-900">
                                    {{ $license->purchased_at->timezone(auth()->user()->timezone ?? config('app.timezone'))->format('M j, Y g:i A') }}
                                </dd>
                            </div>
                        @endif

                        @if ($license->isPaid())
                            <div class="flex items-center justify-between gap-4">
                                <dt class="text-slate-600">Paid</dt>
                                <dd class="font-medium text-slate-900 tabular-nums">{{ $license->formattedTotal() }}</dd>
                            </div>

                            @if ($license->amount_tax > 0)
                                <div class="flex items-center justify-between gap-4">
                                    <dt class="text-slate-600">Includes tax</dt>
                                    <dd class="text-slate-900 tabular-nums">{{ $license->formattedTax() }}</dd>
                                </div>
                            @endif

                            @if ($license->amount_refunded > 0)
                                <div class="flex items-center justify-between gap-4">
                                    <dt class="text-slate-600">Refunded</dt>
                                    <dd class="font-medium text-slate-900 tabular-nums">{{ $license->formattedRefunded() }}</dd>
                                </div>
                            @endif
                        @else
                            <div class="flex items-center justify-between gap-4">
                                <dt class="text-slate-600">Source</dt>
                                <dd class="font-medium text-slate-900">{{ $license->source->label() }}</dd>
                            </div>
                        @endif
                    </dl>

                    @if ($license->isPaid())
                        <div class="flex flex-wrap gap-2 mt-6">
                            @if ($license->hosted_invoice_url)
                                <a href="{{ $license->hosted_invoice_url }}" target="_blank" rel="noopener" class="text-sm px-3 py-2 rounded-lg bg-slate-50 border border-slate-200 hover:border-slate-300">
                                    <i class="fa fa-solid fa-file-invoice mr-1"></i> Invoice
                                </a>
                            @endif

                            @if ($license->invoice_pdf_url)
                                <a href="{{ $license->invoice_pdf_url }}" target="_blank" rel="noopener" class="text-sm px-3 py-2 rounded-lg bg-slate-50 border border-slate-200 hover:border-slate-300">
                                    <i class="fa fa-solid fa-file-pdf mr-1"></i> PDF
                                </a>
                            @endif

                            @unless ($license->hasReceipt())
                                <button wire:click="refreshReceipt" wire:loading.attr="disabled" class="text-sm px-3 py-2 rounded-lg bg-slate-50 border border-slate-200 hover:border-slate-300">
                                    <i class="fa fa-solid fa-rotate mr-1"></i> Fetch receipt
                                </button>
                            @endunless
                        </div>
                    @endif
                @else
                    {{-- Feature list intentionally empty until the Pro feature set is decided. --}}
                    <p class="text-sm text-slate-600">
                        One payment of $10. No subscription, and every Pro feature we build is included.
                    </p>
                @endif
            </div>
        </div>
    </div>
</div>
