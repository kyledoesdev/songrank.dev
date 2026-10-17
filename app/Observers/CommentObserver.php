<?php

namespace App\Observers;

use App\Models\Comment;
use App\Models\Notification;
use App\Models\Ranking;
use App\Models\Review;
use App\Models\Tierlist;
use App\Notifications\NewComment;

class CommentObserver
{
    public function created(Comment $comment): void
    {
        $commentable = $comment->commentable;

        if (! ($commentable instanceof Ranking || $commentable instanceof Tierlist || $commentable instanceof Review)) {
            return;
        }

        $owner = $commentable->user;

        if (! $owner || $owner->getKey() === $comment->commentator_id) {
            return;
        }

        $owner->notify(new NewComment($comment, $commentable));
    }

    public function deleted(Comment $comment): void
    {
        Notification::query()
            ->whereNull('read_at')
            ->where('type', NewComment::class)
            ->whereJsonContains('data->comment_id', $comment->getKey())
            ->delete();
    }
}
