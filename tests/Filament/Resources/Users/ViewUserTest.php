<?php

use App\Filament\Resources\Users\Pages\ViewUser;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Livewire\Livewire;

describe('view user page', function () {
    test('offers a way through to the edit page', function () {
        $user = User::factory()->createOne();

        Livewire::actingAs(kyle())
            ->test(ViewUser::class, ['record' => $user->getKey()])
            ->assertOk()
            ->assertSee(UserResource::getUrl('edit', ['record' => $user]));
    });

    test('links a buyer through to their Stripe customer', function () {
        $buyer = User::factory()->createOne(['stripe_id' => 'cus_test_buyer']);
        $neverCheckedOut = User::factory()->createOne();

        Livewire::actingAs(kyle())
            ->test(ViewUser::class, ['record' => $buyer->getKey()])
            ->assertSee('https://dashboard.stripe.com/test/customers/cus_test_buyer');

        Livewire::actingAs(kyle())
            ->test(ViewUser::class, ['record' => $neverCheckedOut->getKey()])
            ->assertDontSee('https://dashboard.stripe.com');
    });
});
