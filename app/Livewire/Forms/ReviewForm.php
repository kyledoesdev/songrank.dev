<?php

namespace App\Livewire\Forms;

use Livewire\Attributes\Validate;
use Livewire\Form;

class ReviewForm extends Form
{
    /** An unnamed review takes the name of its subject. */
    #[Validate('string|nullable|max:60')]
    public string $name = '';

    #[Validate('required')]
    public $is_public = true;

    #[Validate('required')]
    public $comments_enabled = true;

    #[Validate('required')]
    public $comments_replies_enabled = true;

    public function updatedCommentsEnabled($value): void
    {
        if (! $value || $value === '0') {
            $this->comments_replies_enabled = '0';
        }
    }
}
