<?php

namespace App\Actions\Comments;

use Blaspsoft\Blasp\Facades\Blasp;
use Spatie\Comments\CommentTransformers\CommentTransformer;
use Spatie\Comments\Models\Comment;

final class FilterCommentProfanityAction implements CommentTransformer
{
    /**
     * Execute the action.
     */
    public function handle(Comment $comment): void
    {
        $comment->text = Blasp::english()->mask('*')->check($comment->original_text)->clean();
    }
}
