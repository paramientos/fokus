<?php

namespace App\Livewire\Tags;

use App\Models\Project;
use App\Models\Tag;
use Livewire\Component;

class TagManager extends Component
{
    public $projectId;

    public $project;

    public $tags = [];

    public $newTagName = '';

    public $newTagColor = '#3498db';

    public $editingTagId = null;

    public $editTagName = '';

    public $editTagColor = '';

    public $showDeleteModal = false;

    public $tagToDelete = null;

    public function mount($projectId)
    {
        $this->projectId = $projectId;
        $this->project = Project::findOrFail($projectId);
        $this->loadTags();
    }

    public function loadTags()
    {
        $this->tags = Tag::where('project_id', $this->projectId)
            ->orderBy('name')
            ->get();
    }

    public function createTag()
    {
        $this->validate([
            'newTagName' => 'required|max:50|unique:tags,name,NULL,id,project_id,'.$this->projectId,
            'newTagColor' => 'required|regex:/^#[a-f0-9]{6}$/i',
        ], [
            'newTagName.required' => 'Tag name is required',
            'newTagName.unique' => 'This tag name already exists in this project',
            'newTagColor.required' => 'Tag color is required',
            'newTagColor.regex' => 'Invalid color format',
        ]);

        Tag::create([
            'name' => $this->newTagName,
            'color' => $this->newTagColor,
            'project_id' => $this->projectId,
        ]);

        $this->reset(['newTagName', 'newTagColor']);
        $this->newTagColor = '#3498db';
        $this->loadTags();

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Tag created successfully',
        ]);
    }

    public function startEditing($tagId)
    {
        $tag = Tag::findOrFail($tagId);
        $this->editingTagId = $tagId;
        $this->editTagName = $tag->name;
        $this->editTagColor = $tag->color;
    }

    public function cancelEditing()
    {
        $this->reset(['editingTagId', 'editTagName', 'editTagColor']);
    }

    public function updateTag()
    {
        $this->validate([
            'editTagName' => 'required|max:50|unique:tags,name,'.$this->editingTagId.',id,project_id,'.$this->projectId,
            'editTagColor' => 'required|regex:/^#[a-f0-9]{6}$/i',
        ], [
            'editTagName.required' => 'Tag name is required',
            'editTagName.unique' => 'This tag name already exists in this project',
            'editTagColor.required' => 'Tag color is required',
            'editTagColor.regex' => 'Invalid color format',
        ]);

        $tag = Tag::findOrFail($this->editingTagId);
        $tag->update([
            'name' => $this->editTagName,
            'color' => $this->editTagColor,
        ]);

        $this->reset(['editingTagId', 'editTagName', 'editTagColor']);
        $this->loadTags();

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Tag updated successfully',
        ]);
    }

    public function confirmDelete($tagId)
    {
        $this->tagToDelete = Tag::findOrFail($tagId);
        $this->showDeleteModal = true;
    }

    public function cancelDelete()
    {
        $this->reset(['showDeleteModal', 'tagToDelete']);
    }

    public function deleteTag()
    {
        if ($this->tagToDelete) {
            $this->tagToDelete->delete();
            $this->reset(['showDeleteModal', 'tagToDelete']);
            $this->loadTags();

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Tag deleted successfully',
            ]);
        }
    }

    public function render()
    {
        return view('livewire.tags.tag-manager');
    }
}
