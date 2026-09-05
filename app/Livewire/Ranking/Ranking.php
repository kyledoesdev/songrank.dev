<?php

namespace App\Livewire\Ranking;

use App\Models\Ranking as RankingModel;
use Laravel\Head\Facades\Head;
use Livewire\Component;

class Ranking extends Component
{
    public ?RankingModel $ranking;

    public function mount($id)
    {
        $this->ranking = RankingModel::query()
            ->with(['user', 'songs', 'source', 'sortingState'])
            ->findOrFail($id);

        if (! $this->ranking->canBeSeen()) {
            abort(404);
        }

        Head::title($this->ranking->name);
    }

    public function render()
    {
        return view('livewire.ranking.ranking', [
            'ranking' => $this->ranking,
            'sortingState' => $this->ranking->sortingState,
        ]);
    }
}
