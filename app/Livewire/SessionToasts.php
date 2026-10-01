<?php

namespace App\Livewire;

use Flux\Flux;
use Livewire\Component;

/**
 * Turns whatever the last request flashed to the session into a Flux toast.
 *
 * Mounted once in the layout. Flux::toast() needs a live Livewire component to
 * dispatch from, which a full page load otherwise has nowhere to find.
 */
class SessionToasts extends Component
{
    public function mount(): void
    {
        if (session()->has('success')) {
            Flux::toast(text: session('success'), variant: 'success');
        }

        $errors = session('errors')?->getBag('default');

        if ($errors?->any()) {
            Flux::toast(
                text: implode("\n", $errors->all()),
                heading: 'There were errors with your request',
                variant: 'danger',
            );
        }
    }

    public function render()
    {
        return view('livewire.session-toasts');
    }
}
