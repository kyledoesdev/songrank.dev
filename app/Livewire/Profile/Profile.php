<?php

namespace App\Livewire\Profile;

use App\Models\Ranking;
use App\Models\User;
use Illuminate\Support\Str;
use Laravel\Head\Facades\Head;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class Profile extends Component
{
    public User $user;

    public function mount(string $id): void
    {
        $this->user = User::where('spotify_id', $id)->firstOrFail();

        Head::title($this->possessiveName().' Profile');

        session()->put(['profile_name' => $this->user->name]);
    }

    public function render()
    {
        return view('livewire.profile.profile', [
            'name' => $this->possessiveName(),
        ]);
    }

    private function possessiveName(): string
    {
        return Str::endsWith($this->user->name, 's')
            ? $this->user->name."'"
            : $this->user->name."'s";
    }

    #[Computed]
    #[On('rankings-updated')]
    public function rankings()
    {
        return Ranking::query()
            ->forProfilePage($this->user)
            ->get();
    }
}
