<?php

namespace App\Notifications;

use App\Models\Comment;
use App\Models\Ranking;
use App\Models\Review;
use App\Models\Tierlist;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewComment extends Notification
{
    use Queueable;

    public function __construct(
        public Comment $comment,
        public Ranking|Tierlist|Review $commentable,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'entity' => [
                'id' => $this->commentable->getKey(),
                'name' => $this->commentable->commentableName(),
            ],
            'url' => $this->comment->commentUrl(),
            'message' => $this->comment->original_text,
            'user_name' => $this->comment->commentator?->name,
            'avatar' => $this->comment->commentator?->avatar,
            'comment_id' => $this->comment->getKey(),
        ];
    }
}
