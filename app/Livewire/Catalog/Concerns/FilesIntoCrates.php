<?php

namespace App\Livewire\Catalog\Concerns;

use App\Actions\Catalog\SyncEntityCrates;
use App\Contracts\CrateEntity;
use Illuminate\Support\Facades\Auth;

/**
 * For a setup, whose record does not exist until it is created: the crate
 * picker binds what was ticked here, and the record is filed once it exists.
 */
trait FilesIntoCrates
{
    /** @var list<int|string> */
    public array $crateIds = [];

    protected function fileIntoCrates(CrateEntity $entity): void
    {
        if (empty($this->crateIds)) {
            return;
        }

        (new SyncEntityCrates)->handle(Auth::user(), $entity, $this->crateIds);
    }
}
