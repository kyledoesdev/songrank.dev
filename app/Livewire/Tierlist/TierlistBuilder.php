<?php

namespace App\Livewire\Tierlist;

use App\Actions\Tierlists\CompleteTierlist;
use App\Actions\Tierlists\MoveTierlistItem;
use App\Actions\Tierlists\Tiers\DestroyTier;
use App\Actions\Tierlists\Tiers\ReorderTiers;
use App\Actions\Tierlists\Tiers\StoreTier;
use App\Actions\Tierlists\Tiers\UpdateTier;
use App\Livewire\Concerns\InteractsWithAlerts;
use App\Models\Tier;
use App\Models\Tierlist;
use Livewire\Attributes\Locked;
use Livewire\Component;

class TierlistBuilder extends Component
{
    use InteractsWithAlerts;

    /** Locked, so the ownership check in mount holds for the component's life. */
    #[Locked]
    public Tierlist $tierlist;

    /** Off by default: every player is a cross-origin document to build. */
    public bool $showEmbeds = false;

    public ?int $editingTierId = null;

    public string $tierName = '';

    public string $tierColor = '';

    public function mount(Tierlist $tierlist): void
    {
        abort_unless($tierlist->canBeEdited(), 403);

        $this->tierlist = $tierlist;
    }

    public function render()
    {
        $this->tierlist->load('tiers.items.entryable');

        return view('livewire.tierlist.tierlist-builder', [
            'bank' => $this->tierlist->bank,
            'tiers' => $this->tierlist->placementTiers(),
        ]);
    }

    // -- The board --

    /** wire:sort's handler: the entry, the slot, and the tier it landed in. */
    public function moveItem(int $itemId, int $position, int $tierId): void
    {
        (new MoveTierlistItem)->handle($this->tierlist, [
            'item_id' => $itemId,
            'position' => $position,
            'tier_id' => $tierId,
        ]);
    }

    public function canFinish(): bool
    {
        return $this->tierlist->bankIsEmpty();
    }

    /** Counted off the board already loaded rather than asking again. */
    public function entryCount(): int
    {
        return $this->tierlist->tiers->sum(fn (Tier $tier) => $tier->items->count());
    }

    public function bankCount(): int
    {
        return $this->tierlist->bank->items->count();
    }

    // -- Tiers --

    public function addTier(): void
    {
        $ceiling = config('tierlists.max_tiers');

        if ($this->tierlist->placementTiers()->count() >= $ceiling) {
            $this->flash(
                title: 'That is as many tiers as a list takes.',
                message: "A tier list can hold {$ceiling} tiers. Rename one you aren't using instead.",
                icon: 'error',
            );

            return;
        }

        (new StoreTier)->handle($this->tierlist, ['name' => 'New', 'color' => '#D4D4D8']);
    }

    public function editTier(int $tierId): void
    {
        $tier = $this->tier($tierId);

        $this->editingTierId = $tier->getKey();
        $this->tierName = $tier->name;
        $this->tierColor = $tier->color;
    }

    public function saveTier(): void
    {
        $this->validate([
            'tierName' => ['required', 'string', 'max:20'],
            'tierColor' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);

        (new UpdateTier)->handle($this->tier($this->editingTierId), [
            'name' => $this->tierName,
            'color' => $this->tierColor,
        ]);

        $this->cancelEdit();
    }

    public function cancelEdit(): void
    {
        $this->reset(['editingTierId', 'tierName', 'tierColor']);
    }

    public function deleteTier(int $tierId): void
    {
        $deleted = (new DestroyTier)->handle($this->tierlist, $this->tier($tierId));

        if (! $deleted) {
            $this->flash(
                title: 'A tier list needs somewhere to place things.',
                message: 'This is the last tier left. Add another before removing this one.',
                icon: 'error',
            );

            return;
        }

        $this->cancelEdit();
    }

    public function moveTier(int $tierId, string $direction): void
    {
        (new ReorderTiers)->handle($this->tierlist, $this->tier($tierId), $direction);
    }

    // -- Finishing --

    public function confirmFinish(): void
    {
        $message = auth()->user()->is_pro
            ? "You can come back and rearrange it whenever you like — publishing just means it's finished enough to share."
            : "Once published, only Song Rank Pro members can rearrange a finalized tier list.";

        $this->confirmAction(
            action: 'finish',
            title: 'Publish this tier list?',
            message: $message,
            confirmText: 'Publish it',
        );
    }

    public function finish(): void
    {
        $published = (new CompleteTierlist)->handle($this->tierlist);

        if (! $published) {
            $this->flash(
                title: 'Still '.$this->bankCount().' to place.',
                message: 'Every entry has to sit in a tier before the list can be published.',
                icon: 'error',
            );

            return;
        }

        $this->redirect(route('tierlist', ['id' => $this->tierlist->getKey()]));
    }

    /**
     * Scoped, so a tier from another list is a 404 and the holding area is not
     * something anybody edits, moves or deletes.
     */
    private function tier(int $tierId): Tier
    {
        return $this->tierlist->tiers()
            ->where('is_bank', false)
            ->findOrFail($tierId);
    }
}
