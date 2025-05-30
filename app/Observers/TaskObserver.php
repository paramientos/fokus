<?php

namespace App\Observers;

use App\Models\Task;
use App\Models\Activity;

class TaskObserver
{
    /**
     * Handle the Task "created" event.
     */
    public function created(Task $task): void
    {
        Activity::create([
            'user_id' => auth()->id() ?? 1,
            'project_id' => $task->project_id,
            'task_id' => $task->id,
            'action' => 'created',
            'description' => 'Task created',
            'changes' => null,
        ]);
    }

    /**
     * Handle the Task "updated" event.
     */
    public function updated(Task $task): void
    {
        $changes = [];
        $description = 'Task updated';
        
        // Durum değişikliği
        if ($task->isDirty('status_id')) {
            $oldStatus = \App\Models\Status::find($task->getOriginal('status_id'))?->name ?? 'Unknown';
            $newStatus = \App\Models\Status::find($task->status_id)?->name ?? 'Unknown';
            
            $changes['status'] = [
                'from' => $oldStatus,
                'to' => $newStatus
            ];
            
            $description = "Status changed from '{$oldStatus}' to '{$newStatus}'";
            $action = 'status_changed';
        }
        // Atama değişikliği
        elseif ($task->isDirty('user_id')) {
            $oldUser = \App\Models\User::find($task->getOriginal('user_id'))?->name ?? 'Unassigned';
            $newUser = \App\Models\User::find($task->user_id)?->name ?? 'Unassigned';
            
            $changes['assigned_to'] = [
                'from' => $oldUser,
                'to' => $newUser
            ];
            
            if (!$task->getOriginal('user_id')) {
                $description = "Assigned to {$newUser}";
                $action = 'assigned';
            } elseif (!$task->user_id) {
                $description = "Unassigned from {$oldUser}";
                $action = 'unassigned';
            } else {
                $description = "Reassigned from {$oldUser} to {$newUser}";
                $action = 'assigned';
            }
        }
        // Diğer değişiklikler
        else {
            $dirtyFields = $task->getDirty();
            foreach ($dirtyFields as $field => $value) {
                if (in_array($field, ['updated_at', 'remember_token'])) {
                    continue;
                }
                
                $changes[$field] = [
                    'from' => $task->getOriginal($field),
                    'to' => $value
                ];
            }
            $action = 'updated';
        }
        
        if (!empty($changes)) {
            Activity::create([
                'user_id' => auth()->id() ?? 1,
                'project_id' => $task->project_id,
                'task_id' => $task->id,
                'action' => $action,
                'description' => $description,
                'changes' => $changes,
            ]);
        }
    }

    /**
     * Handle the Task "deleted" event.
     */
    public function deleted(Task $task): void
    {
        Activity::create([
            'user_id' => auth()->id() ?? 1,
            'project_id' => $task->project_id,
            'task_id' => $task->id,
            'action' => 'deleted',
            'description' => 'Task deleted',
            'changes' => null,
        ]);
    }
}
