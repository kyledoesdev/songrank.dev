<?php

namespace App\Livewire\Reviews\Concerns;

use App\Actions\Reviews\StoreReview;
use App\Livewire\Forms\ReviewForm;
use Illuminate\Support\Facades\Auth;

trait HasReviewForm
{
    use HasReviewFlashErrors;

    public ReviewForm $form;

    /**
     * Backstop for the disabled search box: a request can still arrive from a
     * stale page, or from a session that spent its allowance in another tab.
     */
    protected function ensureCanCreateReview(): bool
    {
        if (Auth::user()->canCreateReview()) {
            return true;
        }

        $this->flashReviewLimitReached(Auth::user()->reviewLimit());

        return false;
    }

    public function confirmStartReview(): void
    {
        if (! $this->ensureCanCreateReview()) {
            return;
        }

        if (! $this->hasSubject()) {
            $this->nothingToReview();

            return;
        }

        $this->confirmAction(
            action: 'startReview',
            title: 'Start this review?',
            message: "You're about to write about {$this->subjectName()}. It stays a draft until you publish it, and you can change your mind about the subject right up until then.",
            confirmText: "Let's write it",
        );
    }

    public function startReview(): void
    {
        if (! $this->ensureCanCreateReview()) {
            return;
        }

        if (! $this->hasSubject()) {
            $this->nothingToReview();

            return;
        }

        $review = (new StoreReview)->handle(Auth::user(), [
            'type' => $this->reviewType(),
            'subject' => $this->subject,
            'name' => $this->form->name,
            'is_public' => (bool) $this->form->is_public,
            'comments_enabled' => (bool) $this->form->comments_enabled,
            'comments_replies_enabled' => (bool) $this->form->comments_replies_enabled,
        ]);

        $this->redirect(route('review', ['id' => $review->getKey()]));
    }

    protected function resetReviewForm(): void
    {
        $this->reset([
            'form.name',
            'form.is_public',
            'form.comments_enabled',
            'form.comments_replies_enabled',
        ]);
    }
}
