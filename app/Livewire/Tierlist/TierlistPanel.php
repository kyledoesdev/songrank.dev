<?php

namespace App\Livewire\Tierlist;

use App\Enums\TierlistType;
use App\Models\Tierlist;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

class TierlistPanel extends Component
{
    public function render()
    {
        return view('livewire.tierlist.tierlist-panel');
    }

    #[Computed]
    public function user(): User
    {
        return Auth::user();
    }

    /**
     * Every type's tally up front. Asking the allowance trait per type would
     * count the table once to answer canCreate and again to answer countFor,
     * six queries to draw three cards.
     *
     * @return Collection<string, int>
     */
    #[Computed]
    public function counts(): Collection
    {
        return Tierlist::query()->countsByTypeFor($this->user);
    }

    public function countFor(TierlistType $type): int
    {
        return (int) ($this->counts[$type->value] ?? 0);
    }

    public function limitFor(TierlistType $type): ?int
    {
        return $this->user->tierlistLimit($type);
    }

    public function canCreate(TierlistType $type): bool
    {
        $limit = $this->limitFor($type);

        return is_null($limit) || $this->countFor($type) < $limit;
    }
}
