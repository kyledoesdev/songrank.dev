<?php

namespace App\Livewire\Reviews;

use App\Actions\Reviews\DestroyReview;
use App\Actions\Reviews\PublishReview;
use App\Actions\Reviews\UpdateReview;
use App\Livewire\Concerns\InteractsWithAlerts;
use App\Livewire\Forms\ReviewForm;
use App\Models\Review;
use Closure;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

class EditReview extends Component
{
    use InteractsWithAlerts;

    #[Locked]
    public Review $review;

    public ReviewForm $form;

    public ?float $stars = null;

    public string $body = '';

    public function mount($id): void
    {
        $this->review = Review::query()
            ->with('subject')
            ->findOrFail($id);

        abort_unless($this->review->canBeEdited(), 404);

        $this->form->fill([
            'name' => $this->review->name,
            'is_public' => $this->review->is_public ? '1' : '0',
            'comments_enabled' => $this->review->comments_enabled ? '1' : '0',
            'comments_replies_enabled' => $this->review->comments_replies_enabled ? '1' : '0',
        ]);

        $this->stars = $this->review->stars;
        $this->body = $this->review->body ?? '';
    }

    public function render()
    {
        return view('livewire.reviews.edit-review');
    }

    #[Computed]
    public function canEditContent(): bool
    {
        return ! $this->review->is_published || Auth::user()->is_pro;
    }

    public function setStars(float $stars): void
    {
        $this->stars = $stars;
    }

    public function clearStars(): void
    {
        $this->stars = null;
    }

    public function save(): void
    {
        $this->updateReview();

        $this->flash($this->review->is_published ? 'Changes saved.' : 'Draft saved.');
    }

    public function confirmPublish(): void
    {
        $this->validate();

        $this->confirmAction(
            action: 'publish',
            title: 'Publish this review?',
            message: Auth::user()->is_pro ? '' : "Once you publish it, you won't be able to change the review's content.",
            confirmText: 'Publish it',
        );
    }

    public function publish(): void
    {
        $this->updateReview();

        (new PublishReview)->handle($this->review);

        $this->redirect(route('review', ['id' => $this->review->getKey()]));
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
        (new DestroyReview)->handle($this->review);

        session()->flash('success', 'Review removed successfully.');

        $this->redirect(route('profile', ['id' => Auth::user()->spotify_id]));
    }

    private function updateReview(): void
    {
        $this->validate();

        $this->review = (new UpdateReview)->handle($this->review, [
            'name' => $this->form->name,
            'stars' => $this->canEditContent ? $this->stars : $this->review->stars,
            'body' => $this->canEditContent ? $this->body : $this->review->body,
            'is_public' => (bool) $this->form->is_public,
            'comments_enabled' => (bool) $this->form->comments_enabled,
            'comments_replies_enabled' => (bool) $this->form->comments_replies_enabled,
        ]);
    }

    protected function rules(): array
    {
        return [
            'form.name' => ['required', 'string', 'max:60'],
            'form.is_public' => ['required'],
            'form.comments_enabled' => ['required'],
            'form.comments_replies_enabled' => ['required'],
            'stars' => ['nullable', 'numeric', 'min:0', 'max:10'],
            'body' => ['required', function (string $attribute, string $value, Closure $fail): void {
                if (blank(strip_tags($value))) {
                    $fail('Write something before saving.');
                }
            }],
        ];
    }
}
