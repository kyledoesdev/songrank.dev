<div {{ $attributes->merge(['class' => 'rounded-xl border border-purple-200 bg-purple-50 p-6 text-center']) }}>
    <i class="fa fa-solid fa-star text-2xl text-purple-400"></i>

    {{ $slot }}

    <div class="mt-4 flex flex-wrap items-center justify-center gap-2">
        <a href="{{ route('billing') }}" class="btn-primary p-3">
            <i class="fa fa-solid fa-star mr-1"></i>
            Go Pro &mdash; $10 once
        </a>

        {{ $actions ?? '' }}
    </div>
</div>
