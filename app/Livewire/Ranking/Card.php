<?php

namespace App\Livewire\Ranking;

use App\Actions\Rankings\DestroyRanking;
use App\Exports\SongExport;
use App\Livewire\Concerns\InteractsWithAlerts;
use App\Models\Ranking;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class Card extends Component
{
    use InteractsWithAlerts;

    public Ranking $ranking;

    public function render()
    {
        return view('livewire.ranking.card', ['ranking' => $this->ranking]);
    }

    public function destroy()
    {
        abort_unless(Auth::check() && $this->ranking->user_id == Auth::id(), 403);

        (new DestroyRanking)->handle(Auth::user(), $this->ranking);

        $this->dispatch('rankings-updated');

        $this->flash('Ranking Deleted!');
    }

    public function download(): BinaryFileResponse
    {
        abort_unless(Auth::check() && $this->ranking->user_id == Auth::id(), 403);

        /* yeah, this isn't what you should do here but ima do it anyway */
        $this->skipRender();

        return Excel::download(new SongExport($this->ranking->songs, $this->ranking->name), toSafeFilename($this->ranking->name).'.csv', \Maatwebsite\Excel\Excel::CSV);
    }
}
