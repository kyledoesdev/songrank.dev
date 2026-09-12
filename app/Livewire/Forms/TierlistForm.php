<?php

namespace App\Livewire\Forms;

use Livewire\Attributes\Validate;
use Livewire\Form;

class TierlistForm extends Form
{
    /** An unnamed list takes the name of its source, or of its type. */
    #[Validate('string|nullable|max:30')]
    public string $name = '';

    #[Validate('required')]
    public $is_public = true;

    #[Validate('required')]
    public $comments_enabled = true;

    #[Validate('required')]
    public $comments_replies_enabled = true;
}
