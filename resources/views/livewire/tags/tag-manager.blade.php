<?php
    new class extends Livewire\Component {

    }
?>

<div class="p-4">
    {{-- Etiket yöneticisi için görünüm dosyasını oluşturdum. Bu görünüm, etiketlerin oluşturulması, düzenlenmesi ve silinmesi için kullanıcı arayüzü sağlar. MaryUI bileşenleri kullanılarak modern ve futuristik bir tasarım oluşturuldu. --}}
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold">{{ $project->name }} - Tag Management</h1>
            <p class="text-gray-500">Create and manage tags for your project</p>
        </div>
        <div>
            <a href="{{ route('projects.show', $project->id) }}" class="btn btn-outline">
                <i class="fas fa-arrow-left mr-2"></i> Back to Project
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Create New Tag -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Create New Tag</h3>
            </div>
            <div class="card-body">
                <form wire:submit="createTag">
                    <div class="mb-4">
                        <label for="newTagName" class="block text-sm font-medium mb-1">Tag Name</label>
                        <input type="text" id="newTagName" wire:model="newTagName" class="input w-full" placeholder="Enter tag name">
                        @error('newTagName') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>
                    
                    <div class="mb-4">
                        <label for="newTagColor" class="block text-sm font-medium mb-1">Color</label>
                        <div class="flex items-center space-x-2">
                            <input type="color" id="newTagColor" wire:model="newTagColor" class="w-10 h-10 rounded cursor-pointer">
                            <input type="text" wire:model="newTagColor" class="input flex-1" placeholder="#3498db">
                        </div>
                        @error('newTagColor') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>
                    
                    <div class="flex justify-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-plus mr-2"></i> Create Tag
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Tag List -->
        <div class="card md:col-span-2">
            <div class="card-header">
                <h3 class="card-title">Project Tags</h3>
            </div>
            <div class="card-body">
                @if(count($tags) > 0)
                    <div class="overflow-x-auto">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Color</th>
                                    <th>Name</th>
                                    <th>Usage</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($tags as $tag)
                                    <tr wire:key="tag-{{ $tag->id }}">
                                        <td>
                                            <div class="w-6 h-6 rounded" style="background-color: {{ $tag->color }}"></div>
                                        </td>
                                        <td>
                                            @if($editingTagId === $tag->id)
                                                <input type="text" wire:model="editTagName" class="input input-sm w-full">
                                                @error('editTagName') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                            @else
                                                <span class="font-medium">{{ $tag->name }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge badge-outline">{{ $tag->tasks->count() }} tasks</span>
                                        </td>
                                        <td>
                                            @if($editingTagId === $tag->id)
                                                <div class="flex items-center space-x-2">
                                                    <input type="color" wire:model="editTagColor" class="w-6 h-6 rounded cursor-pointer">
                                                    @error('editTagColor') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                                    
                                                    <button wire:click="updateTag" class="btn btn-sm btn-primary">
                                                        <i class="fas fa-save"></i>
                                                    </button>
                                                    
                                                    <button wire:click="cancelEditing" class="btn btn-sm btn-outline">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </div>
                                            @else
                                                <div class="flex items-center space-x-2">
                                                    <button wire:click="startEditing({{ $tag->id }})" class="btn btn-sm btn-outline">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    
                                                    <button wire:click="confirmDelete({{ $tag->id }})" class="btn btn-sm btn-outline btn-error">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-8 text-gray-500">
                        <i class="fas fa-tags text-4xl mb-2"></i>
                        <p>No tags created yet. Create your first tag to get started.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
    
    <!-- Delete Confirmation Modal -->
    @if($showDeleteModal)
        <div class="modal modal-open">
            <div class="modal-box">
                <h3 class="font-bold text-lg">Delete Tag</h3>
                <p class="py-4">
                    Are you sure you want to delete the tag "{{ $tagToDelete->name }}"?
                    @if($tagToDelete->tasks->count() > 0)
                        <span class="block mt-2 text-red-500">
                            This tag is used by {{ $tagToDelete->tasks->count() }} tasks. Deleting it will remove the tag from all tasks.
                        </span>
                    @endif
                </p>
                <div class="modal-action">
                    <button wire:click="deleteTag" class="btn btn-error">Yes, Delete</button>
                    <button wire:click="cancelDelete" class="btn btn-outline">Cancel</button>
                </div>
            </div>
        </div>
    @endif
</div>
