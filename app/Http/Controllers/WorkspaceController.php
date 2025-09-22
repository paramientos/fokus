<?php

namespace App\Http\Controllers;

use App\Models\Workspace;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class WorkspaceController extends Controller
{
    /**
     * @throws \Throwable
     */
    public function destroy(Workspace $workspace)
    {
        // Check if user is the owner of the workspace
        if ($workspace->owner_id !== Auth::id()) {
            return redirect()->back()->with('error', 'You do not have permission to delete this workspace.');
        }

        // Begin transaction to ensure all related data is deleted properly
        DB::beginTransaction();

        try {
            // Delete all projects and related data
            foreach ($workspace->projects as $project) {
                // Delete project files if any
                // This would need to be expanded based on your actual file storage structure
                Storage::deleteDirectory('projects/'.$project->id);

                // Delete the project (cascading deletes should handle related records)
                $project->delete();
            }

            // Remove all workspace members
            $workspace->members()->detach();

            // Delete any pending invitations
            $workspace->invitations()->delete();

            // Delete the workspace itself
            $workspace->delete();

            DB::commit();

            // Redirect to workspaces index since this workspace no longer exists
            return redirect()->route('workspaces.index')
                ->with('success', 'Workspace and all associated data have been permanently deleted.');

        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()
                ->with('error', 'Failed to delete workspace: '.$e->getMessage());
        }
    }

    public function export(Workspace $workspace)
    {
        // Check if user is a member of the workspace
        if (!$workspace->members()->where('user_id', Auth::id())->exists() && $workspace->owner_id !== Auth::id()) {
            return redirect()->back()->with('error', 'You do not have permission to export this workspace data.');
        }

        // Generate a filename for the export
        $filename = 'workspace_'.$workspace->id.'_export_'.date('Y-m-d_H-i-s').'.zip';

        return response()->streamDownload(function () use ($workspace) {
            $zip = new ZipArchive;
            $tempFile = tempnam(sys_get_temp_dir(), 'workspace_export_');

            if ($zip->open($tempFile, ZipArchive::CREATE) === true) {
                // Export workspace details as JSON
                $workspaceData = [
                    'id' => $workspace->id,
                    'name' => $workspace->name,
                    'description' => $workspace->description,
                    'created_at' => $workspace->created_at->toIso8601String(),
                    'updated_at' => $workspace->updated_at->toIso8601String(),
                    'owner' => [
                        'id' => $workspace->owner->id,
                        'name' => $workspace->owner->name,
                        'email' => $workspace->owner->email,
                    ],
                    'members' => $workspace->members->map(function ($member) {
                        return [
                            'id' => $member->id,
                            'name' => $member->name,
                            'email' => $member->email,
                            'role' => $member->pivot->role,
                        ];
                    })->toArray(),
                ];

                $zip->addFromString('workspace.json', json_encode($workspaceData, JSON_PRETTY_PRINT));

                // Export projects
                $projects = $workspace->projects;

                if ($projects->count() > 0) {
                    $zip->addEmptyDir('projects');

                    foreach ($projects as $project) {
                        $projectDir = 'projects/'.$project->id.'_'.$project->key;
                        $zip->addEmptyDir($projectDir);

                        // Project details
                        $projectData = [
                            'id' => $project->id,
                            'name' => $project->name,
                            'key' => $project->key,
                            'description' => $project->description,
                            'created_at' => $project->created_at->toIso8601String(),
                            'updated_at' => $project->updated_at->toIso8601String(),
                        ];

                        $zip->addFromString($projectDir.'/project.json', json_encode($projectData, JSON_PRETTY_PRINT));

                        // Export tasks
                        if ($project->tasks()->count() > 0) {
                            $zip->addEmptyDir($projectDir.'/tasks');

                            $tasks = $project->tasks()->with(['user', 'status', 'tags', 'attachments'])->get();
                            $tasksData = $tasks->map(function ($task) {
                                return [
                                    'id' => $task->id,
                                    'title' => $task->title,
                                    'description' => $task->description,
                                    'status' => $task->status ? $task->status->name : null,
                                    'assignee' => $task->user ? [
                                        'id' => $task->user->id,
                                        'name' => $task->user->name,
                                        'email' => $task->user->email,
                                    ] : null,
                                    'priority' => $task->priority,
                                    'due_date' => $task->due_date ? $task->due_date->toIso8601String() : null,
                                    'created_at' => $task->created_at->toIso8601String(),
                                    'updated_at' => $task->updated_at->toIso8601String(),
                                    'tags' => $task->tags->map(function ($tag) {
                                        return [
                                            'id' => $tag->id,
                                            'name' => $tag->name,
                                            'color' => $tag->color,
                                        ];
                                    })->toArray(),
                                ];
                            })->toArray();

                            $zip->addFromString($projectDir.'/tasks/tasks.json', json_encode($tasksData, JSON_PRETTY_PRINT));
                        }

                        // Export sprints
                        if ($project->sprints()->count() > 0) {
                            $zip->addEmptyDir($projectDir.'/sprints');

                            $sprints = $project->sprints()->get();
                            $sprintsData = $sprints->map(function ($sprint) {
                                return [
                                    'id' => $sprint->id,
                                    'name' => $sprint->name,
                                    'goal' => $sprint->goal,
                                    'start_date' => $sprint->start_date ? $sprint->start_date->toIso8601String() : null,
                                    'end_date' => $sprint->end_date ? $sprint->end_date->toIso8601String() : null,
                                    'status' => $sprint->status,
                                    'created_at' => $sprint->created_at->toIso8601String(),
                                    'updated_at' => $sprint->updated_at->toIso8601String(),
                                ];
                            })->toArray();

                            $zip->addFromString($projectDir.'/sprints/sprints.json', json_encode($sprintsData, JSON_PRETTY_PRINT));
                        }
                    }
                }

                $zip->close();

                readfile($tempFile);
                unlink($tempFile);
            }
        }, $filename, [
            'Content-Type' => 'application/zip',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }
}
