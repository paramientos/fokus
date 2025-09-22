<?php

namespace App\Livewire\Tasks;

use App\Models\Attachment;
use App\Models\Comment;
use App\Models\Tag;
use App\Models\Task;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;

class Show extends Component
{
    use WithFileUploads;

    public Task $task;

    public $project;

    public $comments = [];

    public $newComment = '';

    public $activeTab = 'details';

    // Attachment properties
    public $showAttachmentModal = false;

    #[Rule('file|max:10240')] // 10MB max
    public $attachmentFile = null;

    public $attachmentDescription = '';

    // Tag properties
    public $showTagsModal = false;

    public $availableTags = [];

    public $selectedTagIds = [];

    public $newTagName = '';

    public $newTagColor = '#3498db';

    // Dependency properties
    public $showDependencyModal = false;

    public $searchTask = '';

    public $searchResults = [];

    public $selectedDependencyId = null;

    public $dependencyType = 'blocks';

    // Time tracking properties
    public $showTimeTrackingModal = false;

    public $timeSpent = '';

    public $timeEstimate = '';

    public $timeUnit = 'h';

    public function mount(Task $task)
    {
        $this->task = $task;

        $this->project = $task->project;
        $this->searchResults = collect();

        $this->loadComments();
        $this->loadTags();
    }

    public function loadComments()
    {
        $this->comments = $this->task->comments()->with('user')->latest()->get();
    }

    public function loadTags()
    {
        $this->availableTags = Tag::where('project_id', $this->project->id)->get();
        $this->selectedTagIds = $this->task->tags->pluck('id')->toArray();

    }

    public function addComment()
    {
        $this->validate([
            'newComment' => 'required|min:3',
        ]);

        $this->task->comments()->create([
            'content' => $this->newComment,
            'user_id' => auth()->id(),
        ]);

        $this->newComment = '';
        $this->loadComments();
    }

    // Delete a comment (only by author)
    public function deleteComment($commentId)
    {
        $comment = Comment::find($commentId);

        if (!$comment) {
            return;
        }

        // Only allow the author to delete
        if ($comment->user_id !== auth()->id()) {
            return;
        }

        $comment->delete();

        $this->loadComments();
    }

    // Tag Management
    public function openTagsModal()
    {
        $this->loadTags();
        $this->showTagsModal = true;
    }

    public function closeTagsModal()
    {
        $this->showTagsModal = false;
        $this->resetTagForm();
    }

    public function resetTagForm()
    {
        $this->newTagName = '';
        $this->newTagColor = '#3498db';
    }

    public function createTag()
    {
        $this->validate([
            'newTagName' => 'required|min:2|max:20',
            'newTagColor' => 'required|regex:/^#[a-f0-9]{6}$/i',
        ]);

        $tag = Tag::create([
            'name' => $this->newTagName,
            'color' => $this->newTagColor,
            'project_id' => $this->project->id,
        ]);

        $this->availableTags = Tag::where('project_id', $this->project->id)->get();
        $this->resetTagForm();
    }

    public function toggleTag($tagId)
    {
        if (in_array($tagId, $this->selectedTagIds)) {
            $this->selectedTagIds = array_diff($this->selectedTagIds, [$tagId]);
        } else {
            $this->selectedTagIds[] = $tagId;
        }
    }

    public function saveTags()
    {
        $this->task->tags()->sync($this->selectedTagIds);
        $this->showTagsModal = false;
    }

    // Attachment Management
    public function openAttachmentModal()
    {
        $this->showAttachmentModal = true;
    }

    public function closeAttachmentModal()
    {
        $this->showAttachmentModal = false;
        $this->resetAttachmentForm();
    }

    public function resetAttachmentForm()
    {
        $this->attachmentFile = null;
        $this->attachmentDescription = '';
    }

    public function uploadAttachment()
    {
        $this->validate([
            'attachmentFile' => 'required|file|max:10240', // 10MB max
            'attachmentDescription' => 'nullable|string|max:255',
        ]);

        $fileSize = $this->attachmentFile->getSize();
        $workspace = $this->project->workspace;

        // Workspace depolama limiti kontrolü
        if (!$workspace->hasEnoughStorageSpace($fileSize)) {
            $this->error('Workspace storage limit reached. Please upgrade your plan or delete some files.');

            return;
        }

        $path = $this->attachmentFile->store('attachments', 'public');

        $this->task->attachments()->create([
            'filename' => $this->attachmentFile->getClientOriginalName(),
            'path' => $path,
            'mime_type' => $this->attachmentFile->getMimeType(),
            'size' => $fileSize,
            'description' => $this->attachmentDescription,
            'user_id' => auth()->id(),
        ]);

        // Depolama kullanımını güncelle
        $workspace->getStorageUsage()->addUsage($fileSize);

        $this->closeAttachmentModal();
    }

    public function deleteAttachment($attachmentId)
    {
        $attachment = Attachment::find($attachmentId);

        if (!$attachment) {
            return;
        }

        // Check if the current user is allowed to delete this attachment
        if ($attachment->user_id !== auth()->id() && !auth()->user()->can('delete', $attachment)) {
            return;
        }

        // Delete the file from storage
        if (\Storage::disk('public')->exists($attachment->path)) {
            \Storage::disk('public')->delete($attachment->path);
        }

        // Depolama kullanımını güncelle
        $workspace = $this->project->workspace;
        if ($workspace) {
            $workspace->getStorageUsage()->removeUsage($attachment->size);
        }

        // Delete the record
        $attachment->delete();
    }

    // Dependency Management
    public function openDependencyModal()
    {
        $this->showDependencyModal = true;
    }

    public function closeDependencyModal()
    {
        $this->showDependencyModal = false;
        $this->resetDependencyForm();
    }

    public function resetDependencyForm()
    {
        $this->searchTask = '';
        $this->searchResults = collect();
        $this->selectedDependencyId = null;
        $this->dependencyType = 'blocks';
    }

    public function updatedSearchTask($value)
    {
        if (strlen($value) >= 2) {
            $this->searchResults = Task::where('project_id', $this->project->id)
                ->where('id', '!=', $this->task->id)
                ->where(function ($query) use ($value) {
                    $query->where('title', 'like', "%{$value}%")
                        ->orWhere('id', 'like', "%{$value}%");
                })
                ->limit(10)
                ->get();
        } else {
            $this->searchResults = collect();
        }
    }

    public function selectDependency($taskId)
    {
        $this->selectedDependencyId = $taskId;
    }

    public function addDependency()
    {
        $this->validate([
            'selectedDependencyId' => 'required|exists:tasks,id',
            'dependencyType' => 'required|in:blocks,is_blocked_by,relates_to,duplicates,is_duplicated_by',
        ]);

        // Check if this dependency already exists
        $exists = $this->task->dependencies()->where('dependency_id', $this->selectedDependencyId)
            ->where('type', $this->dependencyType)
            ->exists();

        if (!$exists) {
            $this->task->dependencies()->attach($this->selectedDependencyId, ['type' => $this->dependencyType]);
        }

        $this->closeDependencyModal();
    }

    public function removeDependency($dependencyId)
    {
        $this->task->dependencies()->detach($dependencyId);
    }

    // Time tracking operations
    public function openTimeTrackingModal()
    {
        $this->timeSpent = $this->task->time_spent ? ($this->task->time_spent / 60) : '';
        $this->timeEstimate = $this->task->time_estimate ? ($this->task->time_estimate / 60) : '';
        $this->timeUnit = 'h';
        $this->showTimeTrackingModal = true;
    }

    public function addTimeTracking()
    {
        $this->validate([
            'timeSpent' => 'nullable|numeric|min:0',
            'timeEstimate' => 'nullable|numeric|min:0',
        ]);

        // Convert to minutes based on unit
        $timeSpentMinutes = null;
        $timeEstimateMinutes = null;

        if ($this->timeSpent !== '') {
            $timeSpentMinutes = $this->timeUnit === 'h'
                ? $this->timeSpent * 60
                : $this->timeSpent;
        }

        if ($this->timeEstimate !== '') {
            $timeEstimateMinutes = $this->timeUnit === 'h'
                ? $this->timeEstimate * 60
                : $this->timeEstimate;
        }

        $this->task->update([
            'time_spent' => $timeSpentMinutes,
            'time_estimate' => $timeEstimateMinutes,
        ]);

        $this->showTimeTrackingModal = false;
    }

    public function getTimeProgressAttribute()
    {
        if (!$this->task->time_estimate || $this->task->time_estimate <= 0) {
            return 0;
        }

        $progress = ($this->task->time_spent / $this->task->time_estimate) * 100;

        return min(100, $progress);
    }

    // Utility methods
    public function setActiveTab($tab)
    {
        $this->activeTab = $tab;
    }

    /**
     * Format time in minutes to a human-readable format
     *
     * @param  int|null  $minutes
     * @return string
     */
    public function formatTime($minutes)
    {
        if (!$minutes) {
            return '0h';
        }

        $hours = floor($minutes / 60);
        $mins = $minutes % 60;

        if ($hours > 0 && $mins > 0) {
            return "{$hours}h {$mins}m";
        } elseif ($hours > 0) {
            return "{$hours}h";
        } else {
            return "{$mins}m";
        }
    }

    // Helper method to determine text color based on background color
    public function getContrastColor($hexColor)
    {
        // Remove # if present
        $hexColor = ltrim($hexColor, '#');

        // Convert to RGB
        $r = hexdec(substr($hexColor, 0, 2));
        $g = hexdec(substr($hexColor, 2, 2));
        $b = hexdec(substr($hexColor, 4, 2));

        // Calculate luminance
        $luminance = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;

        // Return black or white based on luminance
        return $luminance > 0.5 ? '#000000' : '#ffffff';
    }

    // Helper method to get human-readable dependency type labels
    public function getDependencyTypeLabel($type)
    {
        switch ($type) {
            case 'blocks':
                return 'Blocks';
            case 'is_blocked_by':
                return 'Is Blocked By';
            case 'relates_to':
                return 'Relates To';
            case 'duplicates':
                return 'Duplicates';
            case 'is_duplicated_by':
                return 'Is Duplicated By';
            default:
                return 'Unknown';
        }
    }

    public function render()
    {
        return view('livewire.tasks.show');
    }
}
