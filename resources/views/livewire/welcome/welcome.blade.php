<div>
    <x-welcome.top-bar />
    <x-welcome.hero :content="$content" />

    <section>
        <div class="flex justify-center">
            <livewire:welcome.artist-slideshow />
        </div>
    </section>

    <section class="py-12 px-4">
        <livewire:welcome.stats />
    </section>

    <x-welcome.how-it-works :content="$content" />
    <x-welcome.everything-you-can-rank :content="$content" />
    <x-welcome.community :content="$content" />
    <x-welcome.pricing :content="$content" :features="$planFeatures" />
    {{-- <x-welcome.testimonials /> --}}
    <x-welcome.about-developer :content="$content" />
    <x-welcome.call-to-action :content="$content" />
</div>
