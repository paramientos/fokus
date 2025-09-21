<div class="bg-gradient-to-br from-base-100 to-base-200 min-h-screen">
    <x-slot:title>{{ $project->key }}-{{ $task->task_id }}: {{ $task->title }}</x-slot:title>

    <div class="max-w-7xl mx-auto p-6">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
            <div class="flex items-center gap-3">
                <x-button 
                    link="/projects/{{ $project->id }}/tasks" 
                    icon="fas.arrow-left" 
                    class="btn-ghost btn-sm hover:bg-base-200 transition-all duration-200"
                    tooltip="Back to Tasks"
                />
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <span class="text-xs font-mono bg-primary/10 text-primary px-2 py-1 rounded">
                            {{ $project->key }}-{{ $task->task_id }}
                        </span>
                        @if($task->status)
                            <div class="badge" style="background-color: {{ $task->status->color }}; color: white;">
                                {{ $task->status->name }}
                            </div>
                        @endif
                        @if($task->priority)
                            <div class="badge {{
                                $task->priority->value === 'high' ? 'badge-error' :
                                ($task->priority->value === 'medium' ? 'badge-warning' : 'badge-info')
                            }}">
                                @if($task->priority->value === 'high')
                                    <i class="fas fa-arrow-up mr-1"></i>
                                @elseif($task->priority->value === 'medium')
                                    <i class="fas fa-equals mr-1"></i>
                                @else
                                    <i class="fas fa-arrow-down mr-1"></i>
                                @endif
                                {{ ucfirst($task->priority->label()) }}
                            </div>
                        @endif
                    </div>
                    <h1 class="text-2xl font-bold text-primary">{{ $task->title }}</h1>
                </div>
            </div>

            <div class="flex flex-wrap gap-2">
                <x-button 
                    no-wire-navigate 
                    link="/projects/{{ $project->id }}/tasks/{{ $task->id }}/edit" 
                    label="Edit Task" 
                    icon="fas.pen" 
                    class="btn-outline btn-sm hover:bg-base-200 transition-all duration-200"
                    tooltip="Edit this task"
                />
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="md:col-span-2">
                <!-- Tabs -->
                <div class="bg-base-100 rounded-xl shadow-xl border border-base-300 overflow-hidden mb-6">
                    <x-tabs selected="details" class="p-0">
                        <x-tab name="details" label="Details" icon="fas.file-lines" wire:click="setActiveTab('details')">
                            <!-- Task Description -->
                            <div class="bg-base-100 p-6">
                                <div class="mb-6">
                                    <div class="flex items-center gap-3 mb-4">
                                        <span class="p-2 rounded-full bg-primary/10 text-primary">
                                            <i class="fas fa-align-left"></i>
                                        </span>
                                        <h2 class="text-xl font-semibold">Description</h2>
                                    </div>
                                    <div class="prose max-w-none">
                                        <x-markdown-viewer :content="$task->description" />
                                    </div>
                                </div>

                        <!-- Comments -->
                        <div class="mt-8">
                            <div class="flex items-center gap-3 mb-6">
                                <span class="p-2 rounded-full bg-primary/10 text-primary">
                                    <i class="fas fa-comments"></i>
                                </span>
                                <h2 class="text-xl font-semibold">Comments</h2>
                            </div>

                            <div class="space-y-6">
                                @forelse($task->comments as $comment)
                                    <div class="flex gap-4 bg-base-200/30 p-4 rounded-lg border border-base-300 hover:shadow-sm transition-all duration-200">
                                        <div class="avatar">
                                            <div class="w-10 h-10 rounded-full">
                                                @if($comment->user && $comment->user->avatar)
                                                    <img src="{{ $comment->user->avatar }}"
                                                         alt="{{ $comment->user->name }}"/>
                                                @else
                                                    <div class="bg-primary/10 text-primary rounded-full w-10 h-10 flex items-center justify-center">
                                                        <span class="font-medium">{{ $comment->user ? substr($comment->user->name, 0, 1) : 'U' }}</span>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="flex-1">
                                            <div class="flex flex-col sm:flex-row justify-between sm:items-center">
                                                <div class="font-medium text-primary/90">{{ $comment->user->name ?? 'Unknown User' }}</div>
                                                <div class="text-xs text-base-content/50 flex items-center gap-1">
                                                    <i class="fas fa-clock"></i>
                                                    <span>{{ $comment->created_at->format('M d, Y H:i') }}</span>
                                                </div>
                                            </div>
                                            <div class="mt-2 text-base-content/80">
                                                {{ $comment->content }}
                                            </div>
                                            @if($comment->user_id === auth()->id())
                                                <div class="mt-2 flex justify-end">
                                                    <button 
                                                        wire:click="deleteComment({{ $comment->id }})" 
                                                        class="text-xs text-error hover:text-error/80 flex items-center gap-1 transition-colors duration-200"
                                                    >
                                                        <i class="fas fa-trash"></i>
                                                        <span>Delete</span>
                                                    </button>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @empty
                                    <div class="flex flex-col items-center justify-center py-8 text-base-content/50 bg-base-200/30 rounded-lg border border-base-300">
                                        <i class="fas fa-comments text-3xl mb-2 text-base-content/30"></i>
                                        <p>No comments yet</p>
                                        <p class="text-xs mt-1">Be the first to comment on this task</p>
                                    </div>
                                @endforelse

                                <div class="mt-6 bg-base-200/30 p-4 rounded-lg border border-base-300">
                                    <form wire:submit="addComment">
                                        <div>
                                            <x-textarea 
                                                wire:model="newComment" 
                                                placeholder="Add a comment..." 
                                                rows="3"
                                                class="w-full focus:border-primary/50 transition-all duration-300"
                                            />
                                            @error('newComment')
                                                <div class="text-error text-sm mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="mt-3 flex justify-end">
                                            <x-button 
                                                type="submit" 
                                                label="Add Comment" 
                                                icon="fas.paper-plane"
                                                class="btn-primary hover:shadow-md transition-all duration-300"
                                            />
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    </x-tab>

                    <x-tab name="history" label="History" icon="fas.clock-rotate-left"
                           wire:click="setActiveTab('history')">
                        <!-- Timeline -->
                        <div class="bg-base-100 p-6">
                            <div class="flex items-center gap-3 mb-6">
                                <span class="p-2 rounded-full bg-primary/10 text-primary">
                                    <i class="fas fa-clock-rotate-left"></i>
                                </span>
                                <div>
                                    <h2 class="text-xl font-semibold">Activity Timeline</h2>
                                    <p class="text-sm text-base-content/70">All changes and updates for this task</p>
                                </div>
                            </div>

                            @if($task->activities->isEmpty())
                                <div class="flex flex-col items-center justify-center py-12 text-base-content/50 bg-base-200/30 rounded-lg border border-base-300">
                                    <i class="fas fa-history text-4xl mb-3 text-base-content/30"></i>
                                    <h3 class="text-lg font-medium text-base-content/80">No activity yet</h3>
                                    <p class="mt-1 text-sm text-center max-w-md">Activities will appear here when changes are made to this task.</p>
                                </div>
                            @else
                                <div class="relative">
                                    <!-- Timeline line -->
                                    <div class="absolute left-5 top-0 bottom-0 w-0.5 bg-primary/10"></div>

                                    <!-- Timeline items -->
                                    <ul class="space-y-6 relative">
                                        @foreach($task->latestActivities as $activity)
                                            <li class="ml-10 relative">
                                                <!-- Timeline dot with icon -->
                                                <div class="absolute -left-10 mt-1.5 flex items-center justify-center w-8 h-8 rounded-full border-4 border-base-100 {{ $loop->first ? 'bg-primary' : 'bg-base-200' }}">
                                                    <i class="{{ str_replace('o-', 'fas fa-', $activity->icon) }} {{ $loop->first ? 'text-white' : 'text-base-content/60' }}"></i>
                                                </div>

                                                <!-- Timeline content -->
                                                <div class="bg-base-100 border border-base-300 rounded-xl hover:shadow-md transition-all duration-200">
                                                    <div class="p-4">
                                                        <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-2">
                                                            <div class="flex items-center gap-3">
                                                                @if($activity->user)
                                                                    <div class="avatar">
                                                                        <div class="w-8 h-8 rounded-full">
                                                                            @if($activity->user->avatar)
                                                                                <img src="{{ $activity->user->avatar }}" alt="{{ $activity->user->name }}"/>
                                                                            @else
                                                                                <div class="bg-primary/10 text-primary rounded-full w-8 h-8 flex items-center justify-center">
                                                                                    <span class="font-medium">{{ substr($activity->user->name, 0, 1) }}</span>
                                                                                </div>
                                                                            @endif
                                                                        </div>
                                                                    </div>
                                                                    <span class="font-medium text-primary/90">{{ $activity->user->name }}</span>
                                                                @else
                                                                    <div class="bg-info/10 text-info rounded-full w-8 h-8 flex items-center justify-center">
                                                                        <i class="fas fa-robot"></i>
                                                                    </div>
                                                                    <span class="font-medium text-info">System</span>
                                                                @endif
                                                            </div>
                                                            <div class="text-xs text-base-content/50 flex items-center gap-1">
                                                                <i class="fas fa-clock"></i>
                                                                <span title="{{ $activity->created_at->format('Y-m-d H:i:s') }}">
                                                                    {{ $activity->created_at->diffForHumans() }}
                                                                </span>
                                                            </div>
                                                        </div>

                                                        <p class="mt-3 text-base-content/80">{{ $activity->description }}</p>

                                                        @if($activity->changes)
                                                            <div class="mt-3 p-3 bg-base-200/50 rounded-lg text-sm border border-base-300">
                                                                @foreach($activity->changes as $field => $change)
                                                                    <div class="mb-2">
                                                                        <span class="font-medium">{{ ucfirst(str_replace('_', ' ', $field)) }}:</span>
                                                                        <div class="flex items-center gap-2 mt-1 ml-2">
                                                                            <span class="line-through text-error/80">{{ $change['from'] }}</span>
                                                                            <i class="fas fa-arrow-right text-xs text-base-content/50"></i>
                                                                            <span class="text-success/80">{{ $change['to'] }}</span>
                                                                        </div>
                                                                    </div>
                                                                @endforeach
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        </div>
                    </x-tab>

                    <x-tab name="time-tracking" label="Time Tracking" icon="fas.clock"
                           wire:click="setActiveTab('time-tracking')">
                        <!-- Time Tracking -->
                        <div class="bg-base-100 p-6">
                            <div class="flex items-center gap-3 mb-6">
                                <span class="p-2 rounded-full bg-primary/10 text-primary">
                                    <i class="fas fa-stopwatch"></i>
                                </span>
                                <div>
                                    <h2 class="text-xl font-semibold">Time Tracking</h2>
                                    <p class="text-sm text-base-content/70">Track time spent on this task</p>
                                </div>
                            </div>

                            <div class="bg-base-200/30 p-6 rounded-xl border border-base-300 mb-6">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-6">
                                    <div class="bg-base-100 p-4 rounded-lg border border-base-300 hover:shadow-sm transition-all duration-200">
                                        <div class="flex items-center gap-3">
                                            <div class="p-3 rounded-full bg-primary/10 text-primary">
                                                <i class="fas fa-hourglass-half"></i>
                                            </div>
                                            <div>
                                                <p class="text-sm text-base-content/70">Time Spent</p>
                                                <p class="text-xl font-semibold">{{ $this->formatTime($task->time_spent) }}</p>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="bg-base-100 p-4 rounded-lg border border-base-300 hover:shadow-sm transition-all duration-200">
                                        <div class="flex items-center gap-3">
                                            <div class="p-3 rounded-full bg-primary/10 text-primary">
                                                <i class="fas fa-clock"></i>
                                            </div>
                                            <div>
                                                <p class="text-sm text-base-content/70">Time Estimate</p>
                                                <p class="text-xl font-semibold">{{ $this->formatTime($task->time_estimate) }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-6">
                                    <div class="flex justify-between mb-2">
                                        <span class="text-sm text-base-content/70">Progress</span>
                                        <span class="text-sm font-medium">{{ $this->getTimeProgressAttribute() }}%</span>
                                    </div>
                                    <div class="w-full h-3 bg-base-200 rounded-full overflow-hidden">
                                        <div class="h-full {{ $this->getTimeProgressAttribute() > 100 ? 'bg-warning' : 'bg-primary' }} transition-all duration-500" 
                                             style="width: {{ min($this->getTimeProgressAttribute(), 100) }}%"></div>
                                    </div>
                                </div>
                                
                                <div class="flex justify-end">
                                    <x-button 
                                        wire:click="openTimeTrackingModal" 
                                        label="Log Time" 
                                        icon="fas.stopwatch"
                                        class="btn-primary hover:shadow-md transition-all duration-300"
                                    />
                                </div>
                            </div>
                            
                            <!-- Time Logs History would go here -->
                        </div>
                    </x-tab>

                    <x-tab name="tags" label="Tags" icon="fas.tags" wire:click="setActiveTab('tags')">
                        <!-- Tags -->
                        <div class="bg-base-100 p-6">
                            <div class="flex items-center gap-3 mb-6">
                                <span class="p-2 rounded-full bg-primary/10 text-primary">
                                    <i class="fas fa-tags"></i>
                                </span>
                                <div>
                                    <h2 class="text-xl font-semibold">Tags</h2>
                                    <p class="text-sm text-base-content/70">Organize and categorize this task</p>
                                </div>
                            </div>

                            <div class="bg-base-200/30 p-6 rounded-xl border border-base-300">
                                <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-4">
                                    <div>
                                        <p class="text-sm font-medium mb-2">Assigned Tags</p>
                                        @if($task->tags->isEmpty())
                                            <div class="text-base-content/50 text-sm italic">No tags assigned to this task</div>
                                        @else
                                            <div class="flex flex-wrap gap-2">
                                                @foreach($task->tags as $tag)
                                                    <div class="badge badge-lg" style="background-color: {{ $tag->color }}; color: white;">
                                                        <i class="fas fa-tag mr-1 text-xs"></i>
                                                        {{ $tag->name }}
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                    <div>
                                        <x-button 
                                            wire:click="openTagsModal" 
                                            label="Manage Tags" 
                                            icon="fas.tags"
                                            class="btn-primary hover:shadow-md transition-all duration-300"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </x-tab>

                    <x-tab name="attachments" label="Attachments" icon="fas.paperclip"
                           wire:click="setActiveTab('attachments')">
                        <!-- Attachments -->
                        <div class="bg-base-100 p-6">
                            <div class="flex items-center justify-between gap-3 mb-6">
                                <div class="flex items-center gap-3">
                                    <span class="p-2 rounded-full bg-primary/10 text-primary">
                                        <i class="fas fa-paperclip"></i>
                                    </span>
                                    <div>
                                        <h2 class="text-xl font-semibold">Attachments</h2>
                                        <p class="text-sm text-base-content/70">Files attached to this task</p>
                                    </div>
                                </div>
                                <x-button 
                                    wire:click="openAttachmentModal" 
                                    label="Attach File" 
                                    icon="fas.paperclip"
                                    class="btn-primary hover:shadow-md transition-all duration-300"
                                />
                            </div>

                            @if($task->attachments->isEmpty())
                                <div class="flex flex-col items-center justify-center py-12 text-base-content/50 bg-base-200/30 rounded-lg border border-base-300">
                                    <i class="fas fa-file-upload text-4xl mb-3 text-base-content/30"></i>
                                    <h3 class="text-lg font-medium text-base-content/80">No attachments yet</h3>
                                    <p class="mt-1 text-sm text-center max-w-md mb-6">Upload files to share with your team</p>
                                    <x-button 
                                        wire:click="openAttachmentModal" 
                                        label="Upload File"
                                        icon="fas.cloud-upload-alt"
                                        class="btn-primary hover:shadow-md transition-all duration-300"
                                    />
                                </div>
                            @else
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                    @foreach($task->attachments as $attachment)
                                        <div class="bg-base-100 border border-base-300 rounded-lg hover:shadow-md transition-all duration-200">
                                            <div class="p-4">
                                                <div class="flex items-center gap-3 mb-3">
                                                    <div class="p-2 rounded-full bg-primary/10 text-primary text-xl">
                                                        <i class="{{ $attachment->icon_class }}"></i>
                                                    </div>
                                                    <div class="flex-grow">
                                                        <h3 class="font-medium text-sm">{{ Str::limit($attachment->filename, 20) }}</h3>
                                                        <p class="text-xs text-base-content/50">{{ $attachment->formatted_size }}</p>
                                                    </div>
                                                    <div class="dropdown dropdown-end">
                                                        <button class="btn btn-ghost btn-sm btn-circle hover:bg-base-200 transition-colors duration-200">
                                                            <i class="fas fa-ellipsis-v"></i>
                                                        </button>
                                                        <ul class="dropdown-content z-[1] menu p-2 shadow-lg bg-base-100 rounded-lg w-52 border border-base-300">
                                                            <li>
                                                                <a href="{{ asset('storage/' . $attachment->path) }}"
                                                                   target="_blank" class="hover:bg-base-200 transition-colors duration-200">
                                                                    <i class="fas fa-eye"></i> View
                                                                </a>
                                                            </li>
                                                            <li>
                                                                <a href="{{ asset('storage/' . $attachment->path) }}"
                                                                   download="{{ $attachment->filename }}" class="hover:bg-base-200 transition-colors duration-200">
                                                                    <i class="fas fa-download"></i> Download
                                                                </a>
                                                            </li>
                                                            @if($attachment->user_id === auth()->id() || auth()->user()->can('delete', $attachment))
                                                                <li>
                                                                    <a href="#"
                                                                       wire:click.prevent="deleteAttachment({{ $attachment->id }})" 
                                                                       class="text-error hover:bg-error/10 transition-colors duration-200">
                                                                        <i class="fas fa-trash"></i> Delete
                                                                    </a>
                                                                </li>
                                                            @endif
                                                        </ul>
                                                    </div>
                                                </div>

                                                @if($attachment->is_image)
                                                    <div class="mt-3 border border-base-300 rounded-lg overflow-hidden">
                                                        <img src="{{ asset('storage/' . $attachment->path) }}"
                                                             alt="{{ $attachment->filename }}"
                                                             class="w-full h-32 object-cover">
                                                    </div>
                                                @endif

                                                @if($attachment->description)
                                                    <div class="mt-3 text-xs text-base-content/70 bg-base-200/30 p-2 rounded-lg">
                                                        {{ $attachment->description }}
                                                    </div>
                                                @endif

                                                <div class="text-xs text-base-content/50 mt-3 flex items-center gap-1">
                                                    <i class="fas fa-user"></i>
                                                    <span>{{ $attachment->user->name ?? 'Unknown' }}</span>
                                                    <span class="mx-1">•</span>
                                                    <i class="fas fa-clock"></i>
                                                    <span>{{ $attachment->created_at->diffForHumans() }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </x-tab>

                    <x-tab name="files" label="Files" icon="fas.file" wire:click="setActiveTab('files')">
                        <div class="bg-base-100 p-6">
                            <div class="flex items-center gap-3 mb-6">
                                <span class="p-2 rounded-full bg-primary/10 text-primary">
                                    <i class="fas fa-file"></i>
                                </span>
                                <div>
                                    <h2 class="text-xl font-semibold">Files</h2>
                                    <p class="text-sm text-base-content/70">Manage files related to this task</p>
                                </div>
                            </div>
                            
                            <div>
                                <livewire:file-manager :fileable_type="'App\\Models\\Task'" :fileable_id="$task->id" />
                            </div>
                        </div>
                    </x-tab>

                    <x-tab name="dependencies" label="Dependencies" icon="fas.link"
                           wire:click="setActiveTab('dependencies')">
                        <!-- Dependencies -->
                        <div class="bg-base-100 p-6">
                            <div class="flex items-center justify-between gap-3 mb-6">
                                <div class="flex items-center gap-3">
                                    <span class="p-2 rounded-full bg-primary/10 text-primary">
                                        <i class="fas fa-link"></i>
                                    </span>
                                    <div>
                                        <h2 class="text-xl font-semibold">Dependencies</h2>
                                        <p class="text-sm text-base-content/70">Manage task relationships and dependencies</p>
                                    </div>
                                </div>
                                <x-button 
                                    wire:click="openDependencyModal" 
                                    label="Add Dependency" 
                                    icon="fas.link"
                                    class="btn-primary hover:shadow-md transition-all duration-300"
                                />
                            </div>
                            
                            <div class="space-y-6">
                                <!-- This task depends on -->
                                <div class="bg-base-200/30 p-6 rounded-xl border border-base-300">
                                    <h3 class="font-medium mb-3 flex items-center gap-2">
                                        <i class="fas fa-arrow-down text-primary"></i>
                                        <span>This task depends on</span>
                                    </h3>
                                    
                                    @if($task->dependencies->isEmpty())
                                        <div class="text-base-content/50 text-sm italic bg-base-100 p-4 rounded-lg border border-base-300">
                                            <div class="flex items-center gap-2">
                                                <i class="fas fa-info-circle"></i>
                                                <span>No dependencies</span>
                                            </div>
                                        </div>
                                    @else
                                        <div class="flex flex-wrap gap-2">
                                            @foreach($task->dependencies as $dependency)
                                                <div class="badge badge-lg badge-outline border-primary/30 bg-base-100 hover:border-primary transition-all duration-200 p-3">
                                                    <a href="{{ route('tasks.show', ['project' => $project, 'task' => $dependency]) }}"
                                                       class="flex items-center gap-2">
                                                        <span class="text-xs font-mono bg-primary/10 text-primary px-1 py-0.5 rounded">{{ $project->key }}-{{ $dependency->id }}</span>
                                                        <span class="font-medium">{{ $dependency->title }}</span>
                                                        <span class="text-xs text-base-content/70">({{ $this->getDependencyTypeLabel($dependency->pivot->type) }})</span>
                                                    </a>
                                                    <button 
                                                        wire:click="removeDependency({{ $dependency->id }})" 
                                                        class="ml-2 text-error hover:text-error/80 transition-colors duration-200"
                                                        title="Remove dependency"
                                                    >
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                                
                                <!-- Tasks that depend on this -->
                                <div class="bg-base-200/30 p-6 rounded-xl border border-base-300">
                                    <h3 class="font-medium mb-3 flex items-center gap-2">
                                        <i class="fas fa-arrow-up text-primary"></i>
                                        <span>Tasks that depend on this</span>
                                    </h3>
                                    
                                    @if($task->dependents->isEmpty())
                                        <div class="text-base-content/50 text-sm italic bg-base-100 p-4 rounded-lg border border-base-300">
                                            <div class="flex items-center gap-2">
                                                <i class="fas fa-info-circle"></i>
                                                <span>No dependent tasks</span>
                                            </div>
                                        </div>
                                    @else
                                        <div class="flex flex-wrap gap-2">
                                            @foreach($task->dependents as $dependent)
                                                <div class="badge badge-lg badge-outline border-primary/30 bg-base-100 hover:border-primary transition-all duration-200 p-3">
                                                    <a href="{{ route('tasks.show', ['project' => $project, 'task' => $dependent]) }}"
                                                       class="flex items-center gap-2">
                                                        <span class="text-xs font-mono bg-primary/10 text-primary px-1 py-0.5 rounded">{{ $project->key }}-{{ $dependent->id }}</span>
                                                        <span class="font-medium">{{ $dependent->title }}</span>
                                                        <span class="text-xs text-base-content/70">({{ $this->getDependencyTypeLabel($dependent->pivot->type) }})</span>
                                                    </a>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </x-tab>
                </x-tabs>
            </div>

            <div>
                <!-- Task Details -->
                <div class="bg-base-100 rounded-xl shadow-xl border border-base-300 overflow-hidden">
                    <div class="bg-primary/5 p-4 border-b border-base-300 flex items-center gap-3">
                        <span class="p-2 rounded-full bg-primary/10 text-primary">
                            <i class="fas fa-info-circle"></i>
                        </span>
                        <h2 class="text-lg font-semibold">Task Details</h2>
                    </div>

                    <div class="p-5 space-y-5">
                        <!-- Status, Type, Priority -->
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div class="bg-base-200/30 p-3 rounded-lg border border-base-300 hover:shadow-sm transition-all duration-200">
                                <p class="text-xs text-base-content/70 mb-1">Status</p>
                                @if($task->status)
                                    <div class="badge" style="background-color: {{ $task->status->color }}; color: white;">
                                        {{ $task->status->name }}
                                    </div>
                                @else
                                    <p class="italic text-base-content/50">No status</p>
                                @endif
                            </div>

                            <div class="bg-base-200/30 p-3 rounded-lg border border-base-300 hover:shadow-sm transition-all duration-200">
                                <p class="text-xs text-base-content/70 mb-1">Type</p>
                                <div class="badge bg-primary/10 text-primary border-0">
                                    <i class="fas fa-tasks mr-1"></i>
                                    {{ ucfirst($task->task_type->label() ?? 'Task') }}
                                </div>
                            </div>

                            <div class="bg-base-200/30 p-3 rounded-lg border border-base-300 hover:shadow-sm transition-all duration-200">
                                <p class="text-xs text-base-content/70 mb-1">Priority</p>
                                @if($task->priority)
                                    <div class="badge {{
                                        $task->priority->value === 'high' ? 'badge-error' :
                                        ($task->priority->value === 'medium' ? 'badge-warning' : 'badge-info')
                                    }}">
                                        @if($task->priority->value === 'high')
                                            <i class="fas fa-arrow-up mr-1"></i>
                                        @elseif($task->priority->value === 'medium')
                                            <i class="fas fa-equals mr-1"></i>
                                        @else
                                            <i class="fas fa-arrow-down mr-1"></i>
                                        @endif
                                        {{ ucfirst($task->priority->label()) }}
                                    </div>
                                @else
                                    <p class="italic text-base-content/50">No priority</p>
                                @endif
                            </div>
                        </div>

                        <!-- People -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="bg-base-200/30 p-4 rounded-lg border border-base-300 hover:shadow-sm transition-all duration-200">
                                <p class="text-xs text-base-content/70 mb-2">Assignee</p>
                                @if($task->user)
                                    <div class="flex items-center gap-3">
                                        <div class="bg-primary/10 text-primary rounded-full w-10 h-10 flex items-center justify-center">
                                            @if($task->user->avatar)
                                                <img src="{{ $task->user->avatar }}" alt="{{ $task->user->name }}" class="rounded-full"/>
                                            @else
                                                <span class="font-medium">{{ substr($task->user->name ?? 'U', 0, 1) }}</span>
                                            @endif
                                        </div>
                                        <span class="font-medium">{{ $task->user->name }}</span>
                                    </div>
                                @else
                                    <div class="flex items-center gap-2 text-base-content/50">
                                        <i class="fas fa-user-slash"></i>
                                        <span>Unassigned</span>
                                    </div>
                                @endif
                            </div>

                            <div class="bg-base-200/30 p-4 rounded-lg border border-base-300 hover:shadow-sm transition-all duration-200">
                                <p class="text-xs text-base-content/70 mb-2">Reporter</p>
                                @if($task->reporter)
                                    <div class="flex items-center gap-3">
                                        <div class="bg-info/10 text-info rounded-full w-10 h-10 flex items-center justify-center">
                                            @if($task->reporter->avatar)
                                                <img src="{{ $task->reporter->avatar }}" alt="{{ $task->reporter->name }}" class="rounded-full"/>
                                            @else
                                                <span class="font-medium">{{ substr($task->reporter->name ?? 'U', 0, 1) }}</span>
                                            @endif
                                        </div>
                                        <span class="font-medium">{{ $task->reporter->name }}</span>
                                    </div>
                                @else
                                    <div class="flex items-center gap-2 text-base-content/50">
                                        <i class="fas fa-question-circle"></i>
                                        <span>Unknown</span>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Planning -->
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            @if($task->story_points)
                                <div class="bg-base-200/30 p-3 rounded-lg border border-base-300 hover:shadow-sm transition-all duration-200">
                                    <p class="text-xs text-base-content/70 mb-1">Story Points</p>
                                    <div class="flex items-center gap-2">
                                        <i class="fas fa-chart-simple text-primary"></i>
                                        <span class="text-lg font-semibold">{{ $task->story_points }}</span>
                                    </div>
                                </div>
                            @endif

                            @if($task->sprint)
                                <div class="bg-base-200/30 p-3 rounded-lg border border-base-300 hover:shadow-sm transition-all duration-200">
                                    <p class="text-xs text-base-content/70 mb-1">Sprint</p>
                                    <div class="badge badge-outline border-primary/30 text-primary/80 hover:border-primary hover:text-primary transition-all duration-200">
                                        <i class="fas fa-flag mr-1 text-xs"></i> {{ $task->sprint->name }}
                                    </div>
                                </div>
                            @endif

                            @if($task->due_date)
                                <div class="bg-base-200/30 p-3 rounded-lg border border-base-300 hover:shadow-sm transition-all duration-200">
                                    <p class="text-xs text-base-content/70 mb-1">Due Date</p>
                                    <div class="flex items-center gap-2">
                                        <i class="fas fa-calendar-day text-{{ $task->due_date < now() ? 'error' : 'primary' }}"></i>
                                        <span class="{{ $task->due_date < now() ? 'text-error font-medium' : '' }}">{{ $task->due_date->format('M d, Y') }}</span>
                                    </div>
                                </div>
                            @endif
                        </div>

                        <!-- Dates -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="bg-base-200/30 p-3 rounded-lg border border-base-300 hover:shadow-sm transition-all duration-200">
                                <p class="text-xs text-base-content/70 mb-1">Created</p>
                                <div class="flex items-center gap-2">
                                    <i class="fas fa-plus-circle text-success"></i>
                                    <span>{{ $task->created_at->format('M d, Y H:i') }}</span>
                                </div>
                            </div>

                            <div class="bg-base-200/30 p-3 rounded-lg border border-base-300 hover:shadow-sm transition-all duration-200">
                                <p class="text-xs text-base-content/70 mb-1">Updated</p>
                                <div class="flex items-center gap-2">
                                    <i class="fas fa-edit text-info"></i>
                                    <span>{{ $task->updated_at->format('M d, Y H:i') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <x-modal wire:model="showTimeTrackingModal">
        <x-slot:title>Log Time</x-slot:title>
        <form wire:submit="addTimeTracking">
            <div class="space-y-4">
                <div>
                    <x-input label="Time Spent" id="timeSpent" type="number" wire:model="timeSpent" step="0.1" min="0"
                             class="block w-full"/>

                    <x-select id="timeUnit" wire:model="timeUnit" class="block w-full"
                              :options="[['id' => 'h', 'name' => 'Hours'],['id' => 'm', 'name' => 'Minutes']]"
                    />

                    @error('timeSpent')
                    <div class="text-error text-sm mt-1">{{ $message }}</div>
                    @enderror
                </div>
                <div>
                    <x-input label="Time Estimate" id="timeEstimate" type="number" wire:model="timeEstimate" step="0.1"
                             min="0"
                             class="block w-full"/>
                    <x-select id="timeUnit" wire:model="timeUnit" class="block w-full"
                              :options="[['id' => 'h', 'name' => 'Hours'],['id' => 'm', 'name' => 'Minutes']]"
                    />

                    @error('timeEstimate')
                    <div class="text-error text-sm mt-1">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            <div class="flex justify-end mt-4">
                <x-button type="submit" label="Log Time" icon="o-clock" class="btn-primary"/>
            </div>
        </form>
    </x-modal>

    <x-modal wire:model="showTagsModal">
        <x-slot:title>Manage Tags</x-slot:title>
        <form wire:submit="saveTags">
            <div class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <h3 class="font-medium mb-2">Create New Tag</h3>
                        <div class="space-y-3">
                            <div>
                                <x-input label="Tag Name" id="newTagName" type="text" wire:model="newTagName"
                                         class="block w-full" placeholder="Enter tag name"/>
                                @error('newTagName')
                                <div class="text-error text-sm mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1">Tag Color</label>
                                <div class="flex items-center gap-2">
                                    <input type="color" id="newTagColor" wire:model="newTagColor"
                                           class="w-10 h-10 rounded cursor-pointer"/>
                                    <x-input type="text" wire:model="newTagColor"
                                             class="block w-full" placeholder="#3498db"/>
                                </div>
                                @error('newTagColor')
                                <div class="text-error text-sm mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div>
                                <x-button type="button" wire:click="createTag" label="Create Tag" icon="o-tag"
                                          class="btn-primary w-full"/>
                            </div>
                        </div>
                    </div>

                    <div>
                        <h3 class="font-medium mb-2">Available Tags</h3>
                        <div class="border rounded-lg p-4 h-48 overflow-y-auto">
                            @if($this->availableTags->isEmpty())
                                <div class="text-center text-gray-500 py-4">
                                    No tags available for this project
                                </div>
                            @else
                                <div class="flex flex-wrap gap-2">
                                    @foreach($availableTags as $tag)
                                        <div class="badge cursor-pointer hover:opacity-80 transition-opacity"
                                             style="background-color: {{ $tag->color }}; color: {{ $this->getContrastColor($tag->color) }};"
                                             wire:click="toggleTag({{ $tag->id }})">
                                            {{ $tag->name }}
                                            @if(in_array($tag->id, $selectedTagIds))
                                                <i class="fas fa-check ml-1"></i>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div>
                    <h3 class="font-medium mb-2">Selected Tags</h3>
                    <div class="border rounded-lg p-4 min-h-12">
                        @if(empty($selectedTagIds))
                            <div class="text-center text-gray-500 py-2">
                                No tags selected
                            </div>
                        @else
                            <div class="flex flex-wrap gap-2">
                                @foreach($selectedTagIds as $tagId)
                                    @php
                                        $tag = $availableTags->firstWhere('id', $tagId);
                                    @endphp
                                    @if($tag)
                                        <div class="badge"
                                             style="background-color: {{ $tag->color }}; color: {{ $this->getContrastColor($tag->color) }};">
                                            {{ $tag->name }}
                                            <button type="button" class="ml-1" wire:click="toggleTag({{ $tag->id }})">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            <div class="flex justify-end mt-4">
                <x-button type="button" wire:click="closeTagsModal" label="Cancel" class="btn-ghost mr-2"/>
                <x-button type="submit" label="Save Tags" icon="o-tag" class="btn-primary"/>
            </div>
        </form>
    </x-modal>

    <x-modal wire:model="showAttachmentModal">
        <x-slot:title>Attach File</x-slot:title>
        <form wire:submit="uploadAttachment">
            <div class="space-y-4">
                <div
                    class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-primary transition-colors">
                    <div class="mb-4">
                        <i class="fas fa-cloud-upload-alt text-4xl text-gray-400"></i>
                    </div>
                    @if($attachmentFile)
                        <div class="text-sm text-success mb-2">
                            <i class="fas fa-check-circle"></i>
                            {{ $attachmentFile->getClientOriginalName() }}
                            ({{ round($attachmentFile->getSize() / 1024, 2) }} KB)
                        </div>
                    @endif
                    <div class="text-sm text-gray-500 mb-2">
                        Drag and drop your file here or click to browse
                    </div>
                    <x-input id="attachmentFile" type="file" wire:model="attachmentFile"
                             class="hidden" x-ref="fileInput"/>
                    <x-button type="button" @click="$refs.fileInput.click()" label="Browse Files" icon="o-folder-open"
                              class="btn-primary"/>
                    @error('attachmentFile')
                    <div class="text-error text-sm mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div>
                    <x-input label="Description (optional)" id="attachmentDescription" type="text"
                             wire:model="attachmentDescription"
                             class="block w-full" placeholder="Add a brief description of this file"/>
                    @error('attachmentDescription')
                    <div class="text-error text-sm mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="text-sm text-gray-500">
                    <ul class="list-disc list-inside">
                        <li>Maximum file size: 10MB</li>
                        <li>Supported file types: Images, documents, archives, etc.</li>
                    </ul>
                </div>
            </div>
            <div class="flex justify-end mt-4">
                <x-button type="button" wire:click="closeAttachmentModal" label="Cancel" class="btn-ghost mr-2"/>
                <x-button type="submit" label="Upload File" icon="fas.paperclip" class="btn-primary"
                          :disabled="!$attachmentFile"/>
            </div>
        </form>
    </x-modal>

    <x-modal wire:model="showDependencyModal">
        <x-slot:title>Add Dependency</x-slot:title>
        <form wire:submit="addDependency">
            <div class="space-y-4">
                <div>
                    <x-input label="Search Tasks" id="searchTask" type="text"
                             wire:model.live.debounce.300ms="searchTask"
                             class="block w-full" placeholder="Search by task title or ID"/>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Dependency Type</label>
                    <x-select id="dependencyType" wire:model="dependencyType" class="block w-full"
                              :options="[
                                      ['id' => 'blocks', 'name' => 'Blocks'],
                                      ['id' => 'is_blocked_by', 'name' => 'Is Blocked By'],
                                      ['id' => 'relates_to', 'name' => 'Relates To'],
                                      ['id' => 'duplicates', 'name' => 'Duplicates'],
                                      ['id' => 'is_duplicated_by', 'name' => 'Is Duplicated By']
                                  ]"
                    />
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Search Results</label>
                    @if($searchResults->isEmpty() && strlen($searchTask) >= 2)
                        <div class="text-sm text-gray-500">No tasks found</div>
                    @elseif(strlen($searchTask) < 2)
                        <div class="text-sm text-gray-500">Type at least 2 characters to search</div>
                    @else
                        <div class="space-y-2 mt-2 max-h-60 overflow-y-auto">
                            @foreach($searchResults as $result)
                                <div
                                    class="card bg-base-100 border border-gray-200 hover:border-primary transition-colors p-2 cursor-pointer {{ $selectedDependencyId == $result->id ? 'border-primary' : '' }}"
                                    wire:click="selectDependency({{ $result->id }})">
                                    <div class="flex justify-between items-center">
                                        <div>
                                            <div class="font-medium">#{{ $result->id }} {{ $result->title }}</div>
                                            <div
                                                class="text-xs text-gray-500">{{ Str::limit($result->description, 50) }}</div>
                                        </div>
                                        @if($selectedDependencyId == $result->id)
                                            <div class="text-primary">
                                                <i class="fas fa-check"></i>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
            <div class="flex justify-end mt-4">
                <x-button type="button" wire:click="closeDependencyModal" label="Cancel" class="btn-ghost mr-2"/>
                <x-button type="submit" label="Add Dependency" icon="o-link" class="btn-primary"
                          :disabled="!$selectedDependencyId"/>
            </div>
        </form>
    </x-modal>
</div>
