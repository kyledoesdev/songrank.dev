<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'spotify_id' => 'spotify-id-'.str()->random(32),
            'name' => fake()->username(),
            'email' => fake()->unique()->safeEmail(),
            'avatar' => 'https://www.gravatar.com/avatar/unknown?d=mp',
            'timezone' => fake()->timezone(),
            'ip_address' => fake()->localIpv4(),
            'external_token' => str()->random(32),
            'external_refresh_token' => str()->random(32),
            'is_dev' => false,
            'is_pro' => false,
        ];
    }

    public function configure()
    {
        return $this->afterCreating(function (User $user) {
            UserPreference::factory()->create([
                'user_id' => $user->getKey(),
            ]);
        });
    }
}
