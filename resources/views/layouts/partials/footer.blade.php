<!-- Footer -->
<footer class="bg-gray-900/80 backdrop-blur-sm border-t border-gray-700/50">
    <div class="container mx-auto px-4 py-4 md:px-6 md:py-8">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 md:gap-8 text-center md:text-left">
            <!-- Brand -->
            <div class="flex flex-col items-center md:items-start gap-1 md:gap-2">
                <span class="text-base md:text-lg font-semibold bg-gradient-to-r from-purple-400 to-green-300 bg-clip-text text-transparent">
                    songrank.dev
                </span>
                <span class="text-xs md:text-sm text-gray-400">
                    &copy; {{ now()->format('Y') }} Built by
                    <a class="font-medium text-purple-400 hover:text-purple-300 transition-colors" href="https://kyledoes.dev">Kyle</a>
                </span>
            </div>

            <!-- Links -->
            <div class="flex flex-col items-center gap-1 md:gap-2">
                <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 hidden md:block mb-1">Resources</span>
                <div class="flex items-center space-x-4 md:space-x-6">
                    <a href="{{ route('about') }}" class="text-xs md:text-sm text-gray-400 hover:text-white transition-colors">About</a>
                    <a href="{{ route('faq') }}" class="text-xs md:text-sm text-gray-400 hover:text-white transition-colors">FAQ</a>
                    <a href="{{ route('terms') }}" class="text-xs md:text-sm text-gray-400 hover:text-white transition-colors">Terms</a>
                    <a href="{{ route('privacy') }}" class="text-xs md:text-sm text-gray-400 hover:text-white transition-colors">Privacy</a>
                </div>
            </div>

            <!-- Social -->
            <div class="flex flex-col items-center md:items-end gap-1 md:gap-2">
                <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 hidden md:block mb-1 self-stretch text-center md:text-end">Connect</span>
                <div class="flex items-center justify-center md:justify-end space-x-4 md:space-x-5">
                    <a target="_blank" href="https://bsky.app/profile/kyledoes.dev" class="text-gray-400 hover:text-purple-400 transition-colors" aria-label="Bluesky">
                        <i class="fa-brands fa-bluesky text-lg"></i>
                    </a>
                    <a target="_blank" href="https://discord.gg/zXe9kqyFEJ" class="text-gray-400 hover:text-purple-400 transition-colors" aria-label="Discord">
                        <i class="fa-brands fa-discord text-lg"></i>
                    </a>
                    <a target="_blank" href="https://github.com/kyledoesdev" class="text-gray-400 hover:text-purple-400 transition-colors" aria-label="Github">
                        <i class="fa-brands fa-github text-lg"></i>
                    </a>
                    <a target="_blank" href="https://twitch.tv/spacelampsix" class="text-gray-400 hover:text-purple-400 transition-colors" aria-label="Twitch">
                        <i class="fa-brands fa-twitch text-lg"></i>
                    </a>
                    <a target="_blank" href="https://x.com/kyledoesdev" class="text-gray-400 hover:text-purple-400 transition-colors" aria-label="X.com">
                        <i class="fa-brands fa-x-twitter text-lg"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</footer>