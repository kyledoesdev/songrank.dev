<?php

namespace App\Livewire\Reviews;

use App\Actions\Reviews\PublishReview;
use App\Actions\Reviews\UpdateReview;
use App\Livewire\Concerns\InteractsWithAlerts;
use App\Models\Review;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Where a draft gets written. The published review is a different view
 * entirely, the way a finished tier list is.
 */
class ReviewEditor extends Component
{
    use InteractsWithAlerts;

    /** Locked, so the ownership check in mount holds for the component's life. */
    #[Locked]
    public Review $review;

    public ?string $stars = null;

    public string $body = '';

    public function mount(Review $review): void
    {
        abort_unless($review->canBeEdited(), 403);

        $this->review = $review;
        $this->stars = is_null($review->stars) ? null : (string) $review->stars;
        $this->body = $review->body ?? '';
    }

    public function render()
    {
        return view('livewire.reviews.review-editor');
    }

    public function setStars(string $stars): void
    {
        $this->stars = $stars;

        $this->save();
    }

    public function clearStars(): void
    {
        $this->stars = null;

        $this->save();
    }

    /**
     * Saving a draft is quiet — it happens on every star click, and a dialog
     * each time would be unbearable.
     */
    public function save(): void
    {
        $this->validate([
            'stars' => ['nullable', 'numeric', 'min:0', 'max:10'],
        ]);

        (new UpdateReview)->handle($this->review, [
            'stars' => is_null($this->stars) ? null : (float) $this->stars,
            'body' => $this->body,
        ]);

        $this->review = $this->review->fresh();
    }

    public function confirmPublish(): void
    {
        if (blank(strip_tags($this->body))) {
            $this->flash(
                title: 'Write something first.',
                message: 'A review needs words. Say what you thought before you publish it.',
                icon: 'error',
            );

            return;
        }

        $this->confirmAction(
            action: 'publish',
            title: 'Publish this review?',
            message: $this->review->user->is_pro
                ? 'It will appear on your profile, and in the explore feed if you chose to share it. You can keep editing it afterwards.'
                : 'It will appear on your profile, and in the explore feed if you chose to share it. Only Song Rank Pro members can edit a review once it is published.',
            confirmText: 'Publish it',
        );
    }

    public function publish(): void
    {
        $this->save();

        (new PublishReview)->handle($this->review);

        $this->redirect(route('review', ['id' => $this->review->getKey()]));
    }
}
