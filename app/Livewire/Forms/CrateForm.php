<?php

namespace App\Livewire\Forms;

use Livewire\Attributes\Validate;
use Livewire\Form;

class CrateForm extends Form
{
    #[Validate('required|string|max:40')]
    public string $name = '';

    #[Validate('required|regex:/^#[0-9A-Fa-f]{6}$/')]
    public string $color = '';

    #[Validate('nullable|integer')]
    public ?int $crate_icon_id = null;
}
