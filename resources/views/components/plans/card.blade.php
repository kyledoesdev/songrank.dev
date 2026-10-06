@props(['plan', 'features', 'highlighted' => $plan === App\Enums\Billing\Plan::PRO])

<div {{ $attributes->class([
    'relative flex flex-col bg-white shadow-md rounded-xl p-6 md:p-8',
    'ring-2 ring-purple-400' => $highlighted,
]) }}>
    @if ($highlighted)
        <span class="absolute -top-3 left-1/2 -translate-x-1/2 inline-flex items-center gap-1 rounded-full bg-helper px-3 py-0.5 text-xs font-semibold text-zinc-800 shadow-md">
            <i class="fa-solid fa-star"></i>
            Best value
        </span>
    @endif

    <div class="flex items-center gap-3">
        <div @class([
            'w-10 h-10 rounded-xl flex items-center justify-center',
            'bg-primary-muted text-purple-600' => $highlighted,
            'bg-zinc-100 text-zinc-600' => ! $highlighted,
        ])>
            <i class="fa-solid {{ $plan->icon() }}"></i>
        </div>
        <h3 class="text-xl font-bold text-zinc-800">{{ $plan->label() }}</h3>
    </div>

    <p class="mt-3 text-sm text-zinc-500">{{ $plan->tagline() }}</p>

    <p class="mt-6 flex flex-wrap items-center gap-x-3 gap-y-1">
        <span @class([
            'text-5xl font-extrabold tabular-nums tracking-tight',
            'bg-linear-to-r from-purple-500 to-green-400 bg-clip-text text-transparent' => $highlighted,
            'text-zinc-800' => ! $highlighted,
        ])>{{ $plan->price() }}</span>
        <span @class([
            'font-script inline-block whitespace-nowrap',
            'text-xl text-purple-500' => $highlighted,
            'text-xl text-zinc-600' => ! $highlighted,
        ])>{{ $plan->billingPeriod() }}</span>
    </p>

    <ul class="mt-6 space-y-3 grow">
        @foreach ($features as $feature)
            <li @class(['flex items-start gap-3 text-sm', 'text-zinc-700' => $feature->is_included, 'text-zinc-400' => ! $feature->is_included])>
                <i @class([
                    'fa-solid mt-0.5 w-4 text-center',
                    'fa-check text-green-500' => $feature->is_included,
                    'fa-xmark text-zinc-300' => ! $feature->is_included,
                ])></i>
                <div>
                    <span @class(['line-through' => ! $feature->is_included])>{{ $feature->renderedLabel() }}</span>
                    @if ($feature->is_coming_soon)
                        <span class="ml-1 rounded-full bg-zinc-100 px-2 py-0.5 text-[0.625rem] font-semibold uppercase tracking-wide text-zinc-500 whitespace-nowrap">Soon</span>
                    @endif
                    @if ($detail = $feature->renderedDetail())
                        <p class="text-xs text-zinc-400">{{ $detail }}</p>
                    @endif
                </div>
            </li>
        @endforeach
    </ul>

    @isset($action)
        <div class="mt-8">
            {{ $action }}
        </div>
    @endisset
</div>
