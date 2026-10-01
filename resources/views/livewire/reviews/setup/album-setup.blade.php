<div>
    @include('livewire.reviews.setup.partials.setup-header', [
        'type' => $type,
        'placeholder' => 'Search for an album... try Currents',
    ])

    @unless (! auth()->user()->canCreateReview())
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mt-4">
            <div class="lg:col-span-2 bg-white shadow-md rounded-xl p-4" x-auto-animate>
                @if ($this->hasSubject())
                    @include('livewire.reviews.setup.partials.subject-card', ['type' => $type])
                @elseif ($this->results()->isNotEmpty())
                    @include('livewire.reviews.setup.partials.search-results', ['type' => $type])
                @else
                    <div class="text-center py-12 text-zinc-500">
                        <i class="fa-solid {{ $type->icon() }} text-4xl text-zinc-300"></i>
                        <p class="mt-3 text-sm">Search above for the {{ $type->subjectLabel() }} you want to review.</p>
                    </div>
                @endif
            </div>

            <div class="bg-white shadow-md rounded-xl p-4">
                @include('livewire.reviews.setup.partials.review-form', [
                    'type' => $type,
                    'namePlaceholder' => 'Currents still holds up',
                ])
            </div>
        </div>
    @endunless
</div>
