<?php

use App\Livewire\SessionToasts;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Livewire\Livewire;

use function Pest\Laravel\withSession;

describe('session flashes', function () {
    it('becomes a toast', function () {
        withSession(['success' => 'See ya next time!']);

        Livewire::test(SessionToasts::class)
            ->assertDispatched('toast-show', function (string $event, array $params): bool {
                return $params['slots']['text'] === 'See ya next time!'
                    && $params['dataset']['variant'] === 'success';
            });
    });

    it('collects validation errors into one danger toast', function () {
        $errors = new ViewErrorBag;
        $errors->put('default', new MessageBag(['error' => ['Your spotify token expired.']]));

        withSession(['errors' => $errors]);

        Livewire::test(SessionToasts::class)
            ->assertDispatched('toast-show', function (string $event, array $params): bool {
                return $params['slots']['heading'] === 'There were errors with your request'
                    && $params['dataset']['variant'] === 'danger';
            });
    });

    it('raises nothing when the session is quiet', function () {
        Livewire::test(SessionToasts::class)->assertNotDispatched('toast-show');
    });

    it('reaches the page on a real request', function () {
        withSession(['success' => 'See ya next time!'])
            ->get(route('welcome'))
            ->assertOk()
            ->assertSee('See ya next time!', escape: false);
    });
});
