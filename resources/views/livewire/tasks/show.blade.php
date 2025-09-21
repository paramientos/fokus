<div>
    <x-slot:title>{{ $project->key }}-{{ $task->id }}: {{ $task->title }}</x-slot:title>

    <div class="p-6">
        <div class="flex justify-between items-center mb-6">
            <div class="flex items-center gap-2">
                <x-button link="/projects/{{ $project->id }}/tasks" icon="o-arrow-left" class="btn-ghost btn-sm"/>
                <h1 class="text-2xl font-bold text-primary">{{ get_task_with_id($task) }}</h1>
            </div>

            <div class="flex gap-2">
                <x-button no-wire-navigate link="/projects/{{ $project->id }}/tasks/{{ $task->id }}/edit" label="Edit" icon="o-pencil"
                          class="btn-outline"/>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="md:col-span-2">
                <!-- Tabs -->
                <x-tabs selected="details">
                    <x-tab name="details" label="Details" icon="o-document-text" wire:click="setActiveTab('details')">

                        <!-- Task Description -->
                        <div class="card bg-base-100 shadow-xl mb-6">
                            <div class="card-body">
                                <h2 class="card-title mb-4">Description</h2>
                                <x-markdown-viewer :content="$task->description" />
                            </div>
                        </div>

                        <!-- Comments -->
                        <div class="card bg-base-100 shadow-xl">
                            <div class="card-body">
                                <h2 class="card-title">Comments</h2>

                                <div class="space-y-6 mt-4">
                                    @forelse($task->comments as $comment)
                                        <div class="flex gap-4">
                                            <div class="avatar">
                                                <div class="w-10 h-10 rounded-full">
                                                    @if($comment->user && $comment->user->avatar)
                                                        <img src="{{ $comment->user->avatar }}"
                                                             alt="{{ $comment->user->name }}"/>
                                                    @else
                                                        <div
                                                            class="bg-primary text-white flex items-center justify-center">
                                                            {{ $comment->user ? substr($comment->user->name, 0, 1) : 'U' }}
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="flex-1">
                                                <div class="flex justify-between items-center">
                                                    <div
                                                        class="font-medium">{{ $comment->user->name ?? 'Unknown User' }}</div>
                                                    <div class="text-xs text-gray-500">
                                                        {{ $comment->created_at->format('M d, Y H:i') }}
                                                    </div>
                                                    @if($comment->user_id === auth()->id())
                                                        <button wire:click="deleteComment({{ $comment->id }})" class="btn btn-xs btn-ghost text-error"><x-icon name="o-trash"/></button>
                                                    @endif
                                                </div>
                                                <div class="mt-2">
                                                    {{ $comment->content }}
                                                </div>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="text-center py-4">
                                            <p class="text-gray-500">No comments yet.</p>
                                        </div>
                                    @endforelse

                                    <div class="mt-6">
                                        <form wire:submit="addComment">
                                            <div>
                                                <x-textarea wire:model="newComment" placeholder="Add a comment..."
                                                            rows="3"/>
                                                @error('newComment')
                                                <div class="text-error text-sm mt-1">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="mt-2 flex justify-end">
                                                <x-button type="submit" label="Add Comment" icon="o-paper-airplane"
                                                          class="btn-primary"/>
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
                        <div class="card bg-base-100 shadow-xl">
                            <div class="card-body">
                                <div class="flex items-center gap-4 mb-6">
                                    <x-icon name="fas.clock-rotate-left" class="w-8 h-8 text-primary"/>
                                    <div>
                                        <h2 class="text-xl font-bold">Activity Timeline</h2>
                                        <p class="text-gray-500">All changes and updates for this task</p>
                                    </div>
                                </div>

                                @if($task->activities->isEmpty())
                                    <div class="py-10 text-center">
                                        <x-icon name="fas.clock" class="w-16 h-16 mx-auto text-gray-400"/>
                                        <h3 class="mt-4 text-lg font-medium text-gray-900">No activity yet</h3>
                                        <p class="mt-1 text-sm text-gray-500">Activities will appear here when changes
                                            are made to this task.</p>
                                    </div>
                                @else
                                    <div class="relative">
                                        <!-- Timeline line -->
                                        <div class="absolute left-5 top-0 bottom-0 w-0.5 bg-gray-200"></div>

                                        <!-- Timeline items -->
                                        <ul class="space-y-6 relative">
                                            @foreach($task->latestActivities as $activity)
                                                <li class="ml-10 relative">
                                                    <!-- Timeline dot with icon -->
                                                    <div
                                                        class="absolute -left-10 mt-1.5 flex items-center justify-center w-8 h-8 rounded-full border-4 border-white {{ $loop->first ? 'bg-primary' : 'bg-base-200' }}">
                                                        <x-icon name="{{ $activity->icon }}"
                                                                class="w-4 h-4 {{ $loop->first ? 'text-white' : 'text-gray-500' }}"/>
                                                    </div>

                                                    <!-- Timeline content -->
                                                    <div
                                                        class="card bg-base-100 border border-gray-100 hover:shadow-md transition-shadow">
                                                        <div class="card-body p-4">
                                                            <div class="flex justify-between items-start">
                                                                <div class="flex items-center gap-3">
                                                                    @if($activity->user)
                                                                        <div class="avatar">
                                                                            <div class="w-8 h-8 rounded-full">
                                                                                @if($activity->user->avatar)
                                                                                    <img
                                                                                        src="{{ $activity->user->avatar }}"
                                                                                        alt="{{ $activity->user->name }}"/>
                                                                                @else
                                                                                    <div
                                                                                        class="bg-primary text-white flex items-center justify-center">
                                                                                        {{ substr($activity->user->name, 0, 1) }}
                                                                                    </div>
                                                                                @endif
                                                                            </div>
                                                                        </div>
                                                                        <span
                                                                            class="font-medium">{{ $activity->user->name }}</span>
                                                                    @else
                                                                        <span class="font-medium">System</span>
                                                                    @endif
                                                                </div>
                                                                <div class="text-xs text-gray-500">
                                                                    <span
                                                                        title="{{ $activity->created_at->format('Y-m-d H:i:s') }}">
                                                                        {{ $activity->created_at->diffForHumans() }}
                                                                    </span>
                                                                </div>
                                                            </div>

                                                            <p class="mt-2">{{ $activity->description }}</p>

                                                            @if($activity->changes)
                                                                <div class="mt-3 p-3 bg-base-200 rounded-lg text-sm">
                                                                    @foreach($activity->changes as $field => $change)
                                                                        <div class="mb-1">
                                                                            <span class="font-medium">{{ ucfirst(str_replace('_', ' ', $field)) }}:</span>
                                                                            <span
                                                                                class="line-through text-error">{{ $change['from'] }}</span>
                                                                            <x-icon name="fas.arrow-right"
                                                                                    class="w-3 h-3 inline mx-1"/>
                                                                            <span
                                                                                class="text-success">{{ $change['to'] }}</span>
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
                        </div>
                    </x-tab>

                    <x-tab name="time-tracking" label="Time Tracking" icon="o-clock"
                           wire:click="setActiveTab('time-tracking')">
                        <!-- Time Tracking -->
                        <div class="card bg-base-100 shadow-xl">
                            <div class="card-body">
                                <h2 class="card-title">Time Tracking</h2>
                                <div class="flex justify-between items-center mb-4">
                                    <div>
                                        <p class="text-sm text-gray-500">Time Spent</p>
                                        <p class="mt-1">{{ $this->formatTime($task->time_spent) }}</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-500">Time Estimate</p>
                                        <p class="mt-1">{{ $this->formatTime($task->time_estimate) }}</p>
                                    </div>
                                </div>
                                <div class="progress h-4 mb-4">
                                    <div class="progress-bar" role="progressbar"
                                         style="width: {{ $this->getTimeProgressAttribute() }}%"
                                         aria-valuenow="{{ $this->getTimeProgressAttribute() }}" aria-valuemin="0"
                                         aria-valuemax="100"></div>
                                </div>
                                <div class="flex justify-end">
                                    <x-button wire:click="openTimeTrackingModal" label="Log Time" icon="o-clock"
                                              class="btn-primary"/>
                                </div>
                            </div>
                        </div>
                    </x-tab>

                    <x-tab name="tags" label="Tags" icon="o-tag" wire:click="setActiveTab('tags')">
                        <!-- Tags -->
                        <div class="card bg-base-100 shadow-xl">
                            <div class="card-body">
                                <h2 class="card-title">Tags</h2>
                                <div class="flex justify-between items-center mb-4">
                                    <div>
                                        <p class="text-sm text-gray-500">Assigned Tags</p>
                                        <div class="flex gap-2 mt-1">
                                            @foreach($task->tags as $tag)
                                                <div class="badge"
                                                     style="background-color: {{ $tag->color }}">{{ $tag->name }}</div>
                                            @endforeach
                                        </div>
                                    </div>
                                    <div>
                                        <x-button wire:click="openTagsModal" label="Manage Tags" icon="o-tag"
                                                  class="btn-primary"/>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </x-tab>

                    <x-tab name="attachments" label="Attachments" icon="fas.paperclip"
                           wire:click="setActiveTab('attachments')">
                        <!-- Attachments -->
                        <div class="card bg-base-100 shadow-xl">
                            <div class="card-body">
                                <h2 class="card-title flex justify-between items-center">
                                    <span>Attachments</span>
                                    <x-button wire:click="openAttachmentModal" label="Attach File" icon="fas.paperclip"
                                              class="btn-primary"/>
                                </h2>

                                @if($task->attachments->isEmpty())
                                    <div class="text-center py-6">
                                        <div class="text-4xl mb-2 text-gray-400">
                                            <i class="fas fa-file-upload"></i>
                                        </div>
                                        <p class="text-gray-500">No attachments yet</p>
                                        <x-button wire:click="openAttachmentModal" label="Upload File"
                                                  icon="fas.paperclip"
                                                  class="btn-primary mt-4"/>
                                    </div>
                                @else
                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mt-4">
                                        @foreach($task->attachments as $attachment)
                                            <div class="card bg-base-100 border hover:shadow-md transition-shadow">
                                                <div class="card-body p-4">
                                                    <div class="flex items-center gap-2 mb-2">
                                                        <div class="text-2xl">
                                                            <i class="{{ $attachment->icon_class }}"></i>
                                                        </div>
                                                        <div class="flex-grow">
                                                            <h3 class="font-medium text-sm">{{ Str::limit($attachment->filename, 20) }}</h3>
                                                            <p class="text-xs text-gray-500">{{ $attachment->formatted_size }}</p>
                                                        </div>
                                                        <div class="dropdown dropdown-end">
                                                            <button class="btn btn-ghost btn-sm btn-circle">
                                                                <i class="fas fa-ellipsis-v"></i>
                                                            </button>
                                                            <ul class="dropdown-content z-[1] menu p-2 shadow bg-base-100 rounded-box w-52">
                                                                <li>
                                                                    <a href="{{ asset('storage/' . $attachment->path) }}"
                                                                       target="_blank">
                                                                        <i class="fas fa-eye"></i> View
                                                                    </a>
                                                                </li>
                                                                <li>
                                                                    <a href="{{ asset('storage/' . $attachment->path) }}"
                                                                       download="{{ $attachment->filename }}">
                                                                        <i class="fas fa-download"></i> Download
                                                                    </a>
                                                                </li>
                                                                @if($attachment->user_id === auth()->id() || auth()->user()->can('delete', $attachment))
                                                                    <li>
                                                                        <a href="#"
                                                                           wire:click.prevent="deleteAttachment({{ $attachment->id }})">
                                                                            <i class="fas fa-trash text-error"></i>
                                                                            Delete
                                                                        </a>
                                                                    </li>
                                                                @endif
                                                            </ul>
                                                        </div>
                                                    </div>

                                                    @if($attachment->is_image)
                                                        <div class="mt-2">
                                                            <img src="{{ asset('storage/' . $attachment->path) }}"
                                                                 alt="{{ $attachment->filename }}"
                                                                 class="w-full h-32 object-cover rounded">
                                                        </div>
                                                    @endif

                                                    @if($attachment->description)
                                                        <div class="mt-2 text-xs text-gray-600">
                                                            {{ $attachment->description }}
                                                        </div>
                                                    @endif

                                                    <div class="text-xs text-gray-500 mt-2 flex items-center gap-1">
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
                        </div>
                    </x-tab>

                    <x-tab name="files" label="Files" icon="fas.file" wire:click="setActiveTab('files')">
                        <div class="mt-4">
                            <livewire:file-manager :fileable_type="'App\\Models\\Task'" :fileable_id="$task->id" />
                        </div>
                    </x-tab>

                    <x-tab name="dependencies" label="Dependencies" icon="o-link"
                           wire:click="setActiveTab('dependencies')">
                        <!-- Dependencies -->
                        <div class="card bg-base-100 shadow-xl">
                            <div class="card-body">
                                <h2 class="card-title">Dependencies</h2>
                                <div class="flex justify-between items-center mb-4">
                                    <div>
                                        <p class="text-sm text-gray-500">This task depends on</p>
                                        <div class="flex flex-wrap gap-2 mt-1">
                                            @foreach($task->dependencies as $dependency)
                                                <div class="badge badge-outline">
                                                    <a href="{{ route('tasks.show', ['project' => $project, 'task' => $dependency]) }}"
                                                       class="flex items-center gap-1">
                                                        <span class="text-xs">#{{ $dependency->id }}</span>
                                                        <span>{{ $dependency->title }}</span>
                                                        <span class="text-xs">({{ $this->getDependencyTypeLabel($dependency->pivot->type) }})</span>
                                                    </a>
                                                    <button wire:click="removeDependency({{ $dependency->id }})"
                                                            class="ml-2">
                                                        <i class="fas fa-times text-error"></i>
                                                    </button>
                                                </div>
                                            @endforeach

                                            @if($task->dependencies->isEmpty())
                                                <div class="text-sm text-gray-500">No dependencies</div>
                                            @endif
                                        </div>

                                        <p class="text-sm text-gray-500 mt-4">Tasks that depend on this</p>
                                        <div class="flex flex-wrap gap-2 mt-1">
                                            @foreach($task->dependents as $dependent)
                                                <div class="badge badge-outline">
                                                    <a href="{{ route('tasks.show', ['project' => $project, 'task' => $dependent]) }}"
                                                       class="flex items-center gap-1">
                                                        <span class="text-xs">#{{ $dependent->id }}</span>
                                                        <span>{{ $dependent->title }}</span>
                                                        <span class="text-xs">({{ $this->getDependencyTypeLabel($dependent->pivot->type) }})</span>
                                                    </a>
                                                </div>
                                            @endforeach

                                            @if($task->dependents->isEmpty())
                                                <div class="text-sm text-gray-500">No dependent tasks</div>
                                            @endif
                                        </div>
                                    </div>
                                    <div>
                                        <x-button wire:click="openDependencyModal" label="Add Dependency" icon="o-link"
                                                  class="btn-primary"/>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </x-tab>
                </x-tabs>
            </div>

            <div>
                <!-- Task Details -->
                <div class="card bg-base-100 shadow-xl">
                    <div class="card-body">
                        <h2 class="card-title">Details</h2>

                        <div class="space-y-4 mt-4">
                            <div>
                                <p class="text-sm text-gray-500">Status</p>
                                @if($task->status)
                                    <div class="badge mt-1" style="background-color: {{ $task->status->color }}">
                                        {{ $task->status->name }}
                                    </div>
                                @else
                                    <p class="italic text-gray-500 mt-1">No status</p>
                                @endif
                            </div>

                            <div>
                                <p class="text-sm text-gray-500">Type</p>
                                <div class="badge mt-1">
                                    {{ ucfirst($task->task_type->label() ?? 'Task') }}
                                </div>
                            </div>

                            <div>
                                <p class="text-sm text-gray-500">Priority</p>
                                @if($task->priority)
                                    <div class="badge mt-1 {{
                                        $task->priority === 'high' ? 'badge-error' :
                                        ($task->priority === 'medium' ? 'badge-warning' : 'badge-info')
                                    }}">
                                        {{ ucfirst($task->priority->label()) }}
                                    </div>
                                @else
                                    <p class="italic text-gray-500 mt-1">No priority</p>
                                @endif
                            </div>

                            @if($task->story_points)
                                <div>
                                    <p class="text-sm text-gray-500">Story Points</p>
                                    <div class="badge badge-outline mt-1">{{ $task->story_points }}</div>
                                </div>
                            @endif

                            <div>
                                <p class="text-sm text-gray-500">Assignee</p>
                                @if($task->user)
                                    <div class="flex items-center gap-2 mt-1">
                                        <div class="avatar">
                                            <div class="w-10 h-10 rounded-full">
                                                @if($task->user->avatar)
                                                    <img src="{{ $task->user->avatar }}"
                                                         alt="{{ $task->user->name }}"/>
                                                @else
                                                    <div
                                                        class="bg-neutral text-neutral-content rounded-full w-6">
                                                        <span>{{ substr($task->user->name ?? 'U', 0, 1) }}</span>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                        <span>{{ $task->user->name }}</span>
                                    </div>
                                @else
                                    <p class="italic text-gray-500 mt-1">Unassigned</p>
                                @endif
                            </div>

                            <div>
                                <p class="text-sm text-gray-500">Reporter</p>
                                @if($task->reporter)
                                    <div class="flex items-center gap-2 mt-1">
                                        <div class="avatar">
                                            <div class="w-10 h-10 rounded-full">
                                                @if($task->reporter->avatar)
                                                    <img src="{{ $task->reporter->avatar }}"
                                                         alt="{{ $task->reporter->name }}"/>
                                                @else
                                                    <div
                                                        class="bg-neutral text-neutral-content rounded-full w-6">
                                                        <span>{{ substr($task->reporter->name ?? 'U', 0, 1) }}</span>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                        <span>{{ $task->reporter->name }}</span>
                                    </div>
                                @else
                                    <p class="italic text-gray-500 mt-1">Unknown</p>
                                @endif
                            </div>

                            @if($task->sprint)
                                <div>
                                    <p class="text-sm text-gray-500">Sprint</p>
                                    <div class="badge badge-outline mt-1">
                                        {{ $task->sprint->name }}
                                    </div>
                                </div>
                            @endif

                            @if($task->due_date)
                                <div>
                                    <p class="text-sm text-gray-500">Due Date</p>
                                    <p class="mt-1">{{ $task->due_date->format('M d, Y') }}</p>
                                </div>
                            @endif

                            <div>
                                <p class="text-sm text-gray-500">Created</p>
                                <p class="mt-1">{{ $task->created_at->format('M d, Y H:i') }}</p>
                            </div>

                            <div>
                                <p class="text-sm text-gray-500">Updated</p>
                                <p class="mt-1">{{ $task->updated_at->format('M d, Y H:i') }}</p>
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
