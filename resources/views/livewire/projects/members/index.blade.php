<?php

new class extends Livewire\Volt\Component {
    public \App\Models\Project $project;
}
?>

<div>
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold">{{ $project->name }} - Team Members</h1>
            <p class="text-gray-500">Manage project team members and their roles</p>
        </div>
        <div>
            <x-button link="{{ route('projects.show', $project) }}" icon="fas.arrow-left" class="btn-outline">
                Back to Project
            </x-button>
        </div>
    </div>

    <livewire:projects.team-members :project="$project"/>
</div>
