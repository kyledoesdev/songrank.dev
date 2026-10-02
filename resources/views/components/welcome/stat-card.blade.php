@props(['value', 'label', 'icon', 'color' => 'purple'])

@php
    $iconClasses = match ($color) {
        'green' => 'bg-green-400/20 text-green-600',
        'blue' => 'bg-blue-400/20 text-blue-600',
        default => 'bg-purple-400/20 text-purple-600',
    };
@endphp

<div {{ $attributes->class(['bg-white/15 backdrop-blur-sm rounded-2xl p-5 border border-white/20 shadow-lg text-center']) }}>
    <div class="w-12 h-12 mx-auto mb-3 rounded-full flex items-center justify-center {{ $iconClasses }}">
        <i class="fa-solid {{ $icon }} text-xl"></i>
    </div>
    <p class="text-3xl font-bold text-gray-900 tabular-nums">{{ number_format($value) }}+</p>
    <p class="text-sm text-gray-800/60 mt-1 font-medium">{{ $label }}</p>
</div>
