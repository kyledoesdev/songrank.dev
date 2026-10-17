<?php

use App\Filament\Resources\EmailTemplates\Pages\ManageEmailTemplates;
use App\Jobs\SendEmailJob;
use App\Models\EmailTemplate;
use Filament\Actions\Testing\TestAction;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

describe('the send action', function () {
    it('queues the blast for that template', function () {
        Queue::fake();

        $template = EmailTemplate::create([
            'name' => 'Pro launch',
            'subject' => 'Song Rank Pro is here',
            'content' => 'Tier lists, reviews and the catalog.',
        ]);

        Livewire::actingAs(kyle())
            ->test(ManageEmailTemplates::class)
            ->callAction(TestAction::make('sendEmail')->table($template));

        Queue::assertPushed(SendEmailJob::class, 1);
    });
});
