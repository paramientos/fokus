<?php

use App\Models\Team;
use App\Models\Workspace;
use Livewire\Volt\Component;
use Mary\Traits\Toast;

new class extends Component {
    use Toast;

    public Workspace $workspace;
    public $teams;

    public function mount()
    {
        $this->teams = Team::where('workspace_id', $this->workspace->id)->get();
    }
}
?>

<div class="mx-auto max-w-5xl py-10">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-primary"><i class="fas fa-users mr-2"></i>Teams</h1>
        <a href="{{ route('workspaces.teams.create', $workspace->id) }}" class="btn btn-primary"><i
                class="fas fa-plus mr-1"></i>New Team</a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach($teams as $team)
            <x-card class="hover:shadow-lg transition-all duration-300">
                <div class="flex justify-between items-start mb-4">
                    <h3 class="text-lg font-bold">
                        <a href="{{ route('workspaces.teams.show', [$workspace->id, $team->id]) }}" class="text-primary hover:text-primary-focus">
                            {{ $team->name }}
                        </a>
                    </h3>
                    <span class="badge badge-primary">{{ $team->members->count() >0 ? $team->members->count() : 'No' }} members</span>
                </div>

                <p class="text-gray-600 mb-4 line-clamp-2">{{ $team->description ?: 'No description provided' }}</p>

                <div class="flex items-center justify-between mt-auto">
                    <div class="flex -space-x-2">
                        @foreach($team->members->take(3) as $member)
                            <div class="avatar">
                                <div class="w-8 h-8 rounded-full bg-primary text-white flex items-center justify-center ring-2 ring-white">
                                    {{ substr($member->user->name ?? 'U', 0, 1) }}
                                </div>
                            </div>
                        @endforeach

                        @if($team->members->count() > 3)
                            <div class="avatar">
                                <div class="w-8 h-8 rounded-full bg-base-300 text-base-content flex items-center justify-center ring-2 ring-white">
                                    +{{ $team->members->count() - 3 }}
                                </div>
                            </div>
                        @endif
                    </div>

                    <a href="{{ route('workspaces.teams.show', [$workspace->id, $team->id]) }}" class="btn btn-sm btn-ghost">
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </x-card>
        @endforeach
    </div>

    @if($teams->isEmpty())
        <div class="flex flex-col items-center justify-center py-12 text-center">
            <div class="text-6xl text-gray-300 mb-4">
                <i class="fas fa-users-slash"></i>
            </div>
            <h3 class="text-xl font-medium text-gray-500 mb-2">No teams yet</h3>
            <p class="text-gray-400 mb-6">Create your first team to start collaborating</p>
            <a href="{{ route('workspaces.teams.create', $workspace->id) }}" class="btn btn-primary">
                <i class="fas fa-plus mr-2"></i> Create Team
            </a>
        </div>
    @endif
</div>
