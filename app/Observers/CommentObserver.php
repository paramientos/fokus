<?php

namespace App\Observers;

use App\Models\Comment;
use App\Models\Activity;

class CommentObserver
{
    /**
     * Handle the Comment "created" event.
     */
    public function created(Comment $comment): void
    {
        Activity::create([
            'user_id' => auth()->id() ?? 1,
            'project_id' => $comment->task->project_id,
            'task_id' => $comment->task_id,
            'action' => 'comment_added',
            'description' => 'Comment added',
            'changes' => null,
        ]);
    }

    /**
     * Handle the Comment "updated" event.
     */
    public function updated(Comment $comment): void
    {
        if ($comment->isDirty('content')) {
            Activity::create([
                'user_id' => auth()->id() ?? 1,
                'project_id' => $comment->task->project_id,
                'task_id' => $comment->task_id,
                'action' => 'comment_updated',
                'description' => 'Comment updated',
                'changes' => [
                    'content' => [
                        'from' => $comment->getOriginal('content'),
                        'to' => $comment->content
                    ]
                ],
            ]);
        }
    }

    /**
     * Handle the Comment "deleted" event.
     */
    public function deleted(Comment $comment): void
    {
        Activity::create([
            'user_id' => auth()->id() ?? 1,
            'project_id' => $comment->task->project_id,
            'task_id' => $comment->task_id,
            'action' => 'comment_deleted',
            'description' => 'Comment deleted',
            'changes' => null,
        ]);
    }
}
