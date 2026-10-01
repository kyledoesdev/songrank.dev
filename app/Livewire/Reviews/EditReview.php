<?php

namespace App\Livewire\Reviews;

use App\Actions\Reviews\DestroyReview;
use App\Actions\Reviews\UpdateReview;
use App\Livewire\Concerns\InteractsWithAlerts;
use App\Livewire\Forms\ReviewForm;
use App\Models\Review;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class EditReview extends Component
{
    use InteractsWithAlerts;

    public Review $review;

    public ReviewForm $form;

    public function mount($id): void
    {
        $this->review = Review::query()
            ->with('user', 'subject')
            ->findOrFail($id);

        abort_unless($this->review->canBeEdited(), 404);

        $this->form->fill([
            'name' => $this->review->name,
            'is_public' => $this->review->is_public ? '1' : '0',
            'comments_enabled' => $this->review->comments_enabled ? '1' : '0',
            'comments_replies_enabled' => $this->review->comments_replies_enabled ? '1' : '0',
        ]);
    }

    public function render()
    {
        return view('livewire.reviews.edit-review', [
            'canEditBody' => ! $this->review->is_published || Auth::user()->is_pro,
        ]);
    }

    public function update(): void
    {
        $this->validate([
            'form.name' => ['required', 'string', 'max:60'],
            'form.is_public' => ['required'],
            'form.comments_enabled' => ['required'],
            'form.comments_replies_enabled' => ['required'],
        ]);

        (new UpdateReview)->handle($this->review, [
            'name' => $this->form->name,
            'is_public' => $this->form->is_public === '1' || $this->form->is_public === true,
            'comments_enabled' => $this->form->comments_enabled === '1' || $this->form->comments_enabled === true,
            'comments_replies_enabled' => $this->form->comments_replies_enabled === '1' || $this->form->comments_replies_enabled === true,
        ]);

        $this->flash('Review Updated!');
    }

    public function confirmDestroy(): void
    {
        $this->confirmAction(
            action: 'destroy',
            title: 'Delete this review?',
            message: 'This will remove it from your profile and the explore feed, and free up one of your slots. You cannot undo this.',
            confirmText: 'Delete it',
        );
    }

    public function destroy(): void
    {
        $user = $this->review->user;

        (new DestroyReview)->handle($this->review);

        session()->flash('success', 'Review removed successfully.');

        $this->redirect(route('profile', ['id' => $user->spotify_id]));
    }
}
