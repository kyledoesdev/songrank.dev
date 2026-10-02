<?php

namespace Database\Factories;

use App\Enums\ReviewType;
use App\Models\Album;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Review>
 */
class ReviewFactory extends Factory
{
    public function definition(): array
    {
        $body = '<p>'.fake()->paragraph().'</p>';

        return [
            'user_id' => User::factory(),
            'type' => ReviewType::ALBUM->value,
            'subject_type' => (new Album)->getMorphClass(),
            'subject_id' => Album::factory(),
            'name' => fake()->sentence(3),
            'stars' => fake()->randomElement([0, 2.5, 5, 7.5, 8, 9.5, 10]),
            'body' => $body,
            'body_text' => strip_tags($body),
            'is_published' => false,
            'is_public' => false,
            'comments_enabled' => false,
            'comments_replies_enabled' => false,
            'published_at' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_published' => true,
            'published_at' => now(),
        ]);
    }

    public function public(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_public' => true,
        ]);
    }

    public function ofType(ReviewType $type): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => $type->value,
            'subject_type' => (new ($type->subjectModel()))->getMorphClass(),
            'subject_id' => ($type->subjectModel())::factory(),
        ]);
    }

    public function unscored(): static
    {
        return $this->state(fn (array $attributes): array => [
            'stars' => null,
        ]);
    }
}
