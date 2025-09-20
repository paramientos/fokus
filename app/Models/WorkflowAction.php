<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Mail;

/**
 *
 *
 * @property int $id
 * @property int $workflow_id
 * @property string $action_type
 * @property array<array-key, mixed>|null $action_config
 * @property int $order
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Workflow $workflow
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkflowAction newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkflowAction newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkflowAction query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkflowAction whereActionConfig($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkflowAction whereActionType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkflowAction whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkflowAction whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkflowAction whereOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkflowAction whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkflowAction whereWorkflowId($value)
 * @mixin \Eloquent
 */
class WorkflowAction extends Model
{
    use HasFactory,HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'workflow_id',
        'action_type',
        'action_config',
        'order',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'action_config' => 'array',
    ];

    /**
     * Get the workflow that owns the action.
     */
    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class);
    }

    /**
     * Execute the action for a given task.
     *
     * @param Task $task
     * @return void
     */
    public function execute(Task $task): void
    {
        match ($this->action_type) {
            'update_status' => $this->updateStatus($task),
            'update_priority' => $this->updatePriority($task),
            'assign_user' => $this->assignUser($task),
            'add_to_sprint' => $this->addToSprint($task),
            'send_notification' => $this->sendNotification($task),
            'add_comment' => $this->addComment($task),
            default => null,
        };
    }

    /**
     * Update the status of a task.
     *
     * @param Task $task
     * @return void
     */
    private function updateStatus(Task $task): void
    {
        $statusId = $this->action_config['status_id'] ?? null;

        if ($statusId && $statusId != $task->status_id) {
            $oldStatus = $task->status->name ?? 'Unknown';
            $task->update(['status_id' => $statusId]);
            $newStatus = Status::find($statusId)->name ?? 'Unknown';

            // Log the action
            Activity::create([
                'user_id' => auth()->id() ?? 1,
                'project_id' => $task->project_id,
                'task_id' => $task->id,
                'action' => 'status_changed',
                'description' => "Status automatically changed from {$oldStatus} to {$newStatus} by workflow",
                'changes' => [
                    'status' => [
                        'old' => $oldStatus,
                        'new' => $newStatus,
                    ],
                ],
            ]);
        }
    }

    /**
     * Update the priority of a task.
     *
     * @param Task $task
     * @return void
     */
    private function updatePriority(Task $task): void
    {
        $priority = $this->action_config['priority'] ?? null;

        if ($priority && $priority != $task->priority) {
            $oldPriority = $task->priority->label() ?? 'Unknown';
            $task->update(['priority' => $priority]);

            // Log the action
            Activity::create([
                'user_id' => auth()->id() ?? 1,
                'project_id' => $task->project_id,
                'task_id' => $task->id,
                'action' => 'updated',
                'description' => "Priority automatically changed to {$priority} by workflow",
                'changes' => [
                    'priority' => [
                        'old' => $oldPriority,
                        'new' => $priority,
                    ],
                ],
            ]);
        }
    }

    /**
     * Assign a user to a task.
     *
     * @param Task $task
     * @return void
     */
    private function assignUser(Task $task): void
    {
        $userId = $this->action_config['user_id'] ?? null;

        if ($userId && $userId != $task->user_id) {
            $oldUser = $task->user->name ?? 'Unassigned';
            $task->update(['user_id' => $userId]);
            $newUser = User::find($userId)->name ?? 'Unknown';

            // Log the action
            Activity::create([
                'user_id' => auth()->id() ?? 1,
                'project_id' => $task->project_id,
                'task_id' => $task->id,
                'action' => 'assigned',
                'description' => "Task automatically assigned to {$newUser} by workflow",
                'changes' => [
                    'assignee' => [
                        'old' => $oldUser,
                        'new' => $newUser,
                    ],
                ],
            ]);
        }
    }

    /**
     * Add a task to a sprint.
     *
     * @param Task $task
     * @return void
     */
    private function addToSprint(Task $task): void
    {
        $sprintId = $this->action_config['sprint_id'] ?? null;

        if ($sprintId && $sprintId != $task->sprint_id) {
            $oldSprint = $task->sprint->name ?? 'None';
            $task->update(['sprint_id' => $sprintId]);
            $newSprint = Sprint::find($sprintId)->name ?? 'Unknown';

            // Log the action
            Activity::create([
                'user_id' => auth()->id() ?? 1,
                'project_id' => $task->project_id,
                'task_id' => $task->id,
                'sprint_id' => $sprintId,
                'action' => 'sprint_added',
                'description' => "Task automatically added to sprint {$newSprint} by workflow",
                'changes' => [
                    'sprint' => [
                        'old' => $oldSprint,
                        'new' => $newSprint,
                    ],
                ],
            ]);
        }
    }

    /**
     * Send a notification about a task.
     *
     * @param Task $task
     * @return void
     */
    private function sendNotification(Task $task): void
    {
        $recipients = $this->action_config['recipients'] ?? [];
        $subject = $this->action_config['subject'] ?? 'Task Update Notification';
        $message = $this->action_config['message'] ?? 'A task has been updated.';

        // Replace placeholders in the message
        $message = str_replace(
            ['{task_id}', '{task_title}', '{project_name}', '{status}', '{assignee}'],
            [$task->id, $task->title, $task->project->name, $task->status->name ?? 'Unknown', $task->user->name ?? 'Unassigned'],
            $message
        );

        foreach ($recipients as $recipient) {
            // In a real application, you would send an actual email or notification
            // For demonstration purposes, we'll just log it
            Activity::create([
                'user_id' => auth()->id() ?? 1,
                'project_id' => $task->project_id,
                'task_id' => $task->id,
                'action' => 'notification_sent',
                'description' => "Notification sent to {$recipient} by workflow",
            ]);
        }
    }

    /**
     * Add a comment to a task.
     *
     * @param Task $task
     * @return void
     */
    private function addComment(Task $task): void
    {
        $content = $this->action_config['content'] ?? '';

        if (!empty($content)) {
            // Replace placeholders in the content
            $content = str_replace(
                ['{task_id}', '{task_title}', '{project_name}', '{status}', '{assignee}'],
                [$task->id, $task->title, $task->project->name, $task->status->name ?? 'Unknown', $task->user->name ?? 'Unassigned'],
                $content
            );

            Comment::create([
                'content' => $content,
                'task_id' => $task->id,
                'user_id' => auth()->id() ?? 1, // System user or workflow user
            ]);

            // Log the action
            Activity::create([
                'user_id' => auth()->id() ?? 1,
                'project_id' => $task->project_id,
                'task_id' => $task->id,
                'action' => 'comment_added',
                'description' => "Comment automatically added by workflow",
            ]);
        }
    }
}
