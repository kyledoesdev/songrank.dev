<?php

namespace App\Livewire\Reviews;

use App\Models\Review;
use App\Seo\ReviewPage;
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

        if (! $this->review->is_published) {
            $this->redirect(route('review.edit', ['id' => $this->review->getKey()]));

            return;
        }

        Head::title($this->review->name);

        if ($this->review->is_public) {
            (new ReviewPage)->handle($this->review);
        }
    }

    public function render()
    {
        return view('livewire.reviews.review-show', [
            'review' => $this->review,
        ]);
    }
}
