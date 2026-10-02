<?php

namespace App\Actions\Reviews;

use App\Enums\ReviewType;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class StoreReview
{
    /**
     * @param  array{type: ReviewType, subject: array<string, mixed>, name: ?string, is_public: bool, comments_enabled: bool, comments_replies_enabled: bool}  $attributes
     */
    public function handle(User $user, array $attributes): Review
    {
        $type = $attributes['type'];

        $subject = (new ResolveReviewSubject)->handle($type, $attributes['subject']);

        return DB::transaction(fn () => Review::create([
            'user_id' => $user->getKey(),
            'type' => $type->value,
            'subject_type' => $subject->getMorphClass(),
            'subject_id' => $subject->getKey(),
            'name' => Str::limit($this->name($attributes, $subject), 60, ''),
            'is_public' => $attributes['is_public'],
            'comments_enabled' => $attributes['comments_enabled'],
            'comments_replies_enabled' => $attributes['comments_replies_enabled'],
        ]));
    }

    /**
     * An unnamed review borrows the name of what it is about.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function name(array $attributes, Model $subject): string
    {
        return filled($attributes['name']) ? $attributes['name'] : $subject->name().' Review';
    }
}
