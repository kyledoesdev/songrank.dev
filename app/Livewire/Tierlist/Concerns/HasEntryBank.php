<?php

namespace App\Livewire\Tierlist\Concerns;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

trait HasEntryBank
{
    /** The entries picked so far, in the order they were picked. */
    public Collection $bank;

    public ?Collection $searchResults = null;

    public string $searchTerm = '';

    public function initializeHasEntryBank(): void
    {
        $this->bank ??= collect();
    }

    // -- Reading the bank --

    /**
     * The current results, empty before the first search rather than null, so
     * blade never has to ask which it is holding.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function results(): Collection
    {
        return collect($this->searchResults);
    }

    public function bankCount(): int
    {
        return $this->bank->count();
    }

    public function bankLimit(): int
    {
        return Auth::user()->tierlistItemLimit();
    }

    public function bankIsFull(): bool
    {
        return $this->bankCount() >= $this->bankLimit();
    }

    public function roomLeft(): int
    {
        return max(0, $this->bankLimit() - $this->bankCount());
    }

    /**
     * Spotify's url segments are the same words as our type values, so an
     * entry never has to carry a link it can be asked for.
     */
    public function spotifyUrlFor(array $entry): string
    {
        return "https://open.spotify.com/{$this->tierlistType()->value}/{$entry['id']}";
    }

    /**
     * The credit under an entry's name. An artist tile needs none -- the name
     * is the artist. Blade should not be reaching into the array itself.
     */
    public function subtitleFor(array $entry): ?string
    {
        return $entry['artist_name'] ?? null;
    }

    public function holds(string $id): bool
    {
        return $this->bank->contains('id', $id);
    }

    // -- Filling it --

    public function addEntry(string $id): void
    {
        $entry = collect($this->searchResults)->firstWhere('id', $id);

        if (is_null($entry) || $this->holds($id)) {
            return;
        }

        if ($this->bankIsFull()) {
            $this->flashBankIsFull($this->bankLimit());

            return;
        }

        $this->bank->push($entry);
    }

    /**
     * Pours a whole import into the bank, keeping what fits and saying so when
     * the rest did not.
     */
    protected function addEntries(Collection $entries): void
    {
        $fresh = $entries->reject(fn (array $entry) => $this->holds($entry['id']))->values();
        $room = $this->roomLeft();

        if ($fresh->count() > $room) {
            $this->bank = $this->bank->concat($fresh->take($room))->values();
            $this->roomForOnlySome($room, $fresh->count() - $room);

            return;
        }

        $this->bank = $this->bank->concat($fresh)->values();
    }

    // -- Emptying it --

    public function removeEntry(string $id): void
    {
        $this->bank = $this->bank->reject(fn (array $entry) => $entry['id'] === $id)->values();
    }

    protected function resetBank(): void
    {
        $this->bank = collect();
        $this->searchResults = null;
    }
}
