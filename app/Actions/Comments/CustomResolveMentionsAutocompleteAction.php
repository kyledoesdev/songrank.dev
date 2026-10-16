<?php

namespace App\Actions\Comments;

use App\Models\User;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Spatie\Comments\Actions\ResolveMentionsAutocompleteAction;

final class CustomResolveMentionsAutocompleteAction extends ResolveMentionsAutocompleteAction
{
    /**
     * Spatie's search, people already in the thread first, less anyone who
     * has turned mentions off.
     *
     * @return User[]
     */
    public function execute(string $query, $commentable): array
    {
        $inThread = $this->mentionable($query)
            ->whereHas('commentatorComments', fn (Builder $comments) => $comments
                ->where('commentable_type', $commentable->getMorphClass())
                ->where('commentable_id', $commentable->getKey()))
            ->limit(10)
            ->get();

        $others = $this->mentionable($query)
            ->whereKeyNot($inThread->modelKeys())
            ->limit(10 - $inThread->count())
            ->get();

        return $inThread->merge($others)->values()->all();
    }

    private function mentionable(string $query): Builder
    {
        return User::query()
            ->where('name', 'like', "%{$query}%")
            ->whereKeyNot(auth()->id())
            ->whereHas('preferences', fn (Builder $preferences) => $preferences->where('enabled_comment_mentions', true));
    }
}
