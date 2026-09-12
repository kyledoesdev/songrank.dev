<?php

namespace App\Livewire\Tierlist\Concerns;

use App\Actions\Tierlists\StoreTierlist;
use App\Livewire\Concerns\InteractsWithAlerts;
use App\Livewire\Forms\TierlistForm;
use Illuminate\Support\Facades\Auth;

trait HasTierlistForm
{
    use InteractsWithAlerts;

    public TierlistForm $form;

    public function updatedFormCommentsEnabled($value): void
    {
        if (! $value || $value === '0') {
            $this->form->comments_replies_enabled = '0';
        }
    }

    public function tierlistLimitReached(): bool
    {
        return ! Auth::user()->canCreateTierlist($this->tierlistType());
    }

    /**
     * Backstop for the disabled search box: a request can still arrive from a
     * stale page, or from a session that spent its allowance in another tab.
     */
    protected function ensureCanCreateTierlist(): bool
    {
        if (! $this->tierlistLimitReached()) {
            return true;
        }

        $this->flashTierlistLimitReached(
            $this->tierlistType(),
            Auth::user()->tierlistLimit($this->tierlistType()),
        );

        return false;
    }

    public function confirmStartTierlist(): void
    {
        if (! $this->ensureCanCreateTierlist()) {
            return;
        }

        if ($this->bankCount() < 2) {
            $this->nothingToRank();

            return;
        }

        $count = $this->bankCount();
        $label = $this->tierlistType()->itemLabel();

        $this->confirmAction(
            action: 'startTierlist',
            title: 'Start this tier list?',
            message: "You're starting with {$count} {$label}. Once the board is built you won't be able to add any more — you can still move them between tiers, reorder them, and rename or recolour the tiers themselves.",
            confirmText: "Let's build it",
        );
    }

    /**
     * A board needs two entries before sorting them into tiers means anything.
     */
    public function startTierlist(): void
    {
        if (! $this->ensureCanCreateTierlist()) {
            return;
        }

        if ($this->bankCount() < 2) {
            $this->nothingToRank();

            return;
        }

        $tierlist = (new StoreTierlist)->handle(Auth::user(), [
            'type' => $this->tierlistType(),
            'entries' => $this->bank,
            'source' => $this->source(),
            'name' => $this->form->name,
            'is_public' => (bool) $this->form->is_public,
            'comments_enabled' => (bool) $this->form->comments_enabled,
            'comments_replies_enabled' => (bool) $this->form->comments_replies_enabled,
        ]);

        $this->redirect(route('tierlist', ['id' => $tierlist->getKey()]));
    }

    protected function resetTierlistForm(): void
    {
        $this->reset([
            'form.name',
            'form.is_public',
            'form.comments_enabled',
            'form.comments_replies_enabled',
        ]);
    }
}
