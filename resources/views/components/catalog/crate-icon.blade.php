@props(['crate'])

<div
    {{ $attributes->class(['shrink-0 rounded-xl flex items-center justify-center']) }}
    style="background-color: {{ $crate->color }}26; color: {{ $crate->color }}"
>
    <i class="fa-solid {{ $crate->iconClass() }}"></i>
</div>
