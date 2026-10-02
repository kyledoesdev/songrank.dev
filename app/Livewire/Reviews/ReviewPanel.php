<?php

namespace App\Livewire\Reviews;

use App\Models\Review;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

class ReviewPanel extends Component
{
    public function render()
    {
        return view('livewire.reviews.review-panel');
    }

    #[Computed]
    public function count(): int
    {
        return Review::query()->where('user_id', Auth::id())->count();
    }

    public function remaining(): ?int
    {
        $limit = Auth::user()->reviewLimit();

        return is_null($limit) ? null : max(0, $limit - $this->count);
    }
}
