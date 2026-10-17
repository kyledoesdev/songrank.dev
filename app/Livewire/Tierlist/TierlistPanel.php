<?php

namespace App\Livewire\Tierlist;

use App\Enums\TierlistType;
use App\Models\Tierlist;
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

    /**
     * @return Collection<string, int>
     */
    #[Computed]
    public function counts(): Collection
    {
        return Tierlist::query()->countsByTypeFor(Auth::user());
    }

    public function countFor(TierlistType $type): int
    {
        return (int) ($this->counts[$type->value] ?? 0);
    }

    public function limitFor(TierlistType $type): ?int
    {
        return Auth::user()->tierlistLimit($type);
    }

    public function canCreate(TierlistType $type): bool
    {
        $limit = $this->limitFor($type);

        return is_null($limit) || $this->countFor($type) < $limit;
    }

    public function canCreateAny(): bool
    {
        return collect(TierlistType::cases())->contains(fn (TierlistType $type) => $this->canCreate($type));
    }

    public function totalCount(): int
    {
        return $this->counts->sum();
    }

    public function totalLimit(): int|string
    {
        $limits = collect(TierlistType::cases())->map(fn (TierlistType $type) => $this->limitFor($type));

        if ($limits->contains(null)) {
            return 'unlimited';
        }

        return $limits->sum();
    }
}
