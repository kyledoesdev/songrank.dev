<?php

namespace App\Livewire\Reviews;

use App\Models\Review;
use Laravel\Head\Facades\Head;
use Livewire\Component;

class ReviewShow extends Component
{
    public Review $review;

    public function mount($id): void
    {
        $this->review = Review::query()
            ->with('user', 'subject')
            ->findOrFail($id);

        if (! $this->review->canBeSeen()) {
            abort(404);
        }

        Head::title($this->review->name);

        if ($this->review->is_published && $this->review->is_public) {
            $this->shareTags();
        }
    }

    public function render()
    {
        return view('livewire.reviews.review-show', [
            'review' => $this->review,
        ]);
    }

    /**
     * What a link to this review unfurls into. The score rides in the title
     * because that is the part people are sharing.
     */
    private function shareTags(): void
    {
        $title = $this->review->shareTitle();
        $description = $this->review->shareDescription();
        $cover = $this->review->subject?->cover();

        Head::description($description);

        Head::og(
            type: 'article',
            title: $title,
            description: $description,
            url: route('review', ['id' => $this->review->getKey()]),
        );

        Head::twitter(
            card: filled($cover) ? 'summary_large_image' : 'summary',
            title: $title,
            description: $description,
        );

        if (filled($cover)) {
            Head::ogImage($cover, alt: $this->review->subject?->name());
            Head::twitterImage($cover, alt: $this->review->subject?->name());
        }
    }
}
