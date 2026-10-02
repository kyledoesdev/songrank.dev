<div class="max-w-7xl mx-auto">
    <div class="flex flex-wrap justify-center gap-4">
        @foreach ($stats as $stat)
            <x-welcome.stat-card
                :value="$stat['value']"
                :label="$stat['label']"
                :icon="$stat['icon']"
                :color="$stat['color']"
                class="basis-[calc(50%-0.5rem)] md:basis-[calc(33.333%-0.667rem)] lg:basis-0 lg:grow"
            />
        @endforeach
    </div>
</div>
