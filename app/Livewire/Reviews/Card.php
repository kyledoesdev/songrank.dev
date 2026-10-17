<?php

namespace App\Livewire\Reviews;

use App\Actions\Reviews\DestroyReview;
use App\Livewire\Concerns\InteractsWithAlerts;
use App\Models\Review;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Card extends Component
{
    use InteractsWithAlerts;

    public Review $review;

    public function render()
    {
        return view('livewire.reviews.card', ['review' => $this->review]);
    }

    public function destroy(): void
    {
        abort_unless(Auth::check() && $this->review->user_id == Auth::id(), 403);

        (new DestroyReview)->handle($this->review);

        $this->dispatch('reviews-updated');

        $this->flash('Review Deleted!');
    }
}
