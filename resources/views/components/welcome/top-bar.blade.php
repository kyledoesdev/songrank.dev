<nav {{ $attributes->class(['flex flex-wrap items-center justify-center md:justify-end gap-2 px-4 pt-2']) }}>
    <a href="{{ route('explore') }}" class="inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-white/20 hover:bg-white/30 text-sm font-medium text-gray-900 transition-all">
        <i class="fa-solid fa-compass"></i>
        Explore
    </a>
    @auth
        <a href="{{ route('dashboard') }}" class="btn-animated inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-gray-900">
            <i class="fa-solid fa-house"></i>
            Dashboard
        </a>
    @else
        <a href="{{ route('spotify.login') }}" class="btn-animated inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-gray-900">
            <i class="fa-brands fa-spotify"></i>
            Sign in
        </a>
    @endauth
</nav>
