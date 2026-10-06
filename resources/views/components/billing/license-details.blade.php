@props(['license'])

<div>
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
</div>
