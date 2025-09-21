<?php

new class extends Livewire\Volt\Component {
    public $project;
    public $sprint;
    public $whatWentWell = [];
    public $whatCouldBeImproved = [];
    public $actionItems = [];
    public $newWentWell = '';
    public $newCouldBeImproved = '';
    public $newActionItem = '';
    public $newActionItemAssignee = '';

    public function mount($project, $sprint)
    {
        $this->project = \App\Models\Project::findOrFail($project);
        $this->sprint = \App\Models\Sprint::findOrFail($sprint);

        // Retrospektif verilerini yükle (sprint meta verilerinden)
        $retrospective = json_decode($this->sprint->meta_data['retrospective'] ?? '{}', true);
        $this->whatWentWell = $retrospective['what_went_well'] ?? [];
        $this->whatCouldBeImproved = $retrospective['what_could_be_improved'] ?? [];
        $this->actionItems = $retrospective['action_items'] ?? [];
    }

    public function addWentWell()
    {
        if (empty($this->newWentWell)) {
            return;
        }

        $this->whatWentWell[] = [
            'id' => uniqid(),
            'content' => $this->newWentWell,
            'created_by' => auth()->id(),
            'created_at' => now()->toDateTimeString(),
        ];

        $this->newWentWell = '';
        $this->saveRetrospective();
    }

    public function addCouldBeImproved()
    {
        if (empty($this->newCouldBeImproved)) {
            return;
        }

        $this->whatCouldBeImproved[] = [
            'id' => uniqid(),
            'content' => $this->newCouldBeImproved,
            'created_by' => auth()->id(),
            'created_at' => now()->toDateTimeString(),
        ];

        $this->newCouldBeImproved = '';
        $this->saveRetrospective();
    }

    public function addActionItem()
    {
        if (empty($this->newActionItem)) {
            return;
        }

        $this->actionItems[] = [
            'id' => uniqid(),
            'content' => $this->newActionItem,
            'assignee' => $this->newActionItemAssignee,
            'is_completed' => false,
            'created_by' => auth()->id(),
            'created_at' => now()->toDateTimeString(),
        ];

        $this->newActionItem = '';
        $this->newActionItemAssignee = '';
        $this->saveRetrospective();
    }

    public function removeItem($type, $id)
    {
        if ($type === 'went_well') {
            $this->whatWentWell = array_filter($this->whatWentWell, function ($item) use ($id) {
                return $item['id'] !== $id;
            });
        } elseif ($type === 'could_be_improved') {
            $this->whatCouldBeImproved = array_filter($this->whatCouldBeImproved, function ($item) use ($id) {
                return $item['id'] !== $id;
            });
        } elseif ($type === 'action_item') {
            $this->actionItems = array_filter($this->actionItems, function ($item) use ($id) {
                return $item['id'] !== $id;
            });
        }

        $this->saveRetrospective();
    }

    public function toggleActionItem($id)
    {
        foreach ($this->actionItems as &$item) {
            if ($item['id'] === $id) {
                $item['is_completed'] = !($item['is_completed'] ?? false);
                break;
            }
        }

        $this->saveRetrospective();
    }

    public function saveRetrospective()
    {
        // Sprint meta verilerini güncelle
        $metaData = $this->sprint->meta_data ?? [];
        $metaData['retrospective'] = [
            'what_went_well' => array_values($this->whatWentWell),
            'what_could_be_improved' => array_values($this->whatCouldBeImproved),
            'action_items' => array_values($this->actionItems),
            'updated_at' => now()->toDateTimeString(),
        ];

        $this->sprint->update([
            'meta_data' => $metaData,
        ]);

        session()->flash('message', 'Retrospective updated successfully!');
    }

    public function with(): array
    {
        // Sprint'e ait görevleri olan kullanıcıları getir
        $users = \App\Models\User::whereHas('tasks', function ($query) {
            $query->where('sprint_id', $this->sprint->id);
        })->orWhereHas('reportedTasks', function ($query) {
            $query->where('sprint_id', $this->sprint->id);
        })->orderBy('name')->get();

        return [
            'users' => $users,
        ];
    }
}

?>

<div class="bg-gradient-to-br from-base-100 to-base-200 min-h-screen">
    <x-slot:title>Sprint Retrospective - {{ $sprint->name }}</x-slot:title>

    <div class="max-w-7xl mx-auto p-6">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
            <div class="flex items-center gap-3">
                <x-button 
                    link="/projects/{{ $project->id }}/sprints/{{ $sprint->id }}" 
                    icon="fas.arrow-left" 
                    class="btn-ghost btn-sm hover:bg-base-200 transition-all duration-200"
                    tooltip="Back to Sprint"
                />
                <div>
                    <h1 class="text-2xl font-bold text-primary">Sprint Retrospective</h1>
                    <div class="flex items-center gap-2 text-base-content/70">
                        <span class="font-medium">{{ $sprint->name }}</span>
                        <div class="badge {{ $sprint->is_completed ? 'badge-info' : ($sprint->is_active ? 'badge-success' : 'badge-warning') }}">
                            @if($sprint->is_completed)
                                <i class="fas fa-check-circle mr-1"></i> Completed
                            @elseif($sprint->is_active)
                                <i class="fas fa-play-circle mr-1"></i> Active
                            @else
                                <i class="fas fa-clock mr-1"></i> Planned
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap gap-2">
                <x-button 
                    link="/projects/{{ $project->id }}/sprints/{{ $sprint->id }}/report" 
                    label="View Report" 
                    icon="fas.chart-bar" 
                    class="btn-outline btn-sm hover:bg-base-200 transition-all duration-200"
                    tooltip="Sprint Report"
                />
                <x-button 
                    link="/projects/{{ $project->id }}/sprints/{{ $sprint->id }}/burndown" 
                    label="Burndown Chart" 
                    icon="fas.chart-line" 
                    class="btn-outline btn-sm hover:bg-base-200 transition-all duration-200"
                    tooltip="Burndown Chart"
                />
            </div>
        </div>
        
        <!-- Retrospective Introduction -->
        <div class="bg-base-100 rounded-xl shadow-xl border border-base-300 overflow-hidden mb-6">
            <div class="bg-primary/5 p-4 border-b border-base-300 flex items-center gap-3">
                <span class="p-2 rounded-full bg-primary/10 text-primary">
                    <i class="fas fa-comments text-lg"></i>
                </span>
                <div>
                    <h2 class="text-xl font-semibold">Sprint Reflection</h2>
                    <p class="text-sm text-base-content/70">Reflect on what went well and what could be improved</p>
                </div>
            </div>
            
            <div class="p-6">
                <div class="flex items-center gap-4 mb-4">
                    <div class="p-3 rounded-full bg-primary/10 text-primary">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div>
                        <div class="text-sm text-base-content/70">Sprint Duration</div>
                        <p class="font-medium">
                            {{ ($sprint->start_date ?? $sprint->created_at)->format('M d, Y') }} - 
                            {{ ($sprint->end_date ?? ($sprint->start_date ?? $sprint->created_at)->copy()->addDays(14))->format('M d, Y') }}
                        </p>
                    </div>
                </div>
                
                <p class="text-base-content/80 italic border-l-4 border-primary/30 pl-4 py-1">
                    A retrospective is a meeting held after a sprint to discuss what went well, what could be improved, 
                    and what actions to take in the next sprint. It's a key part of continuous improvement.
                </p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- What Went Well -->
            <div class="bg-base-100 rounded-xl shadow-xl border border-base-300 overflow-hidden">
                <div class="bg-success/10 p-4 border-b border-base-300 flex items-center gap-3">
                    <span class="p-2 rounded-full bg-success/20 text-success">
                        <i class="fas fa-check-circle text-lg"></i>
                    </span>
                    <h2 class="text-xl font-semibold text-success">What Went Well</h2>
                </div>
                
                <div class="p-5">
                    <div class="space-y-4">
                        @if(empty($whatWentWell))
                            <div class="flex flex-col items-center justify-center py-8 text-base-content/50">
                                <i class="fas fa-lightbulb text-3xl mb-2 text-success/50"></i>
                                <p>No items added yet</p>
                                <p class="text-xs mt-1">Add your positive observations below</p>
                            </div>
                        @else
                            <div class="max-h-[350px] overflow-y-auto pr-1 space-y-3">
                                @foreach($whatWentWell as $item)
                                    <div class="bg-success/5 border border-success/20 p-4 rounded-lg relative group hover:shadow-sm transition-all duration-200">
                                        <p class="text-base-content/90">{{ $item['content'] }}</p>
                                        <div class="mt-2 flex justify-between items-center text-xs text-base-content/50">
                                            <span>{{ \Carbon\Carbon::parse($item['created_at'])->diffForHumans() }}</span>
                                        </div>
                                        <button
                                            wire:click="removeItem('went_well', '{{ $item['id'] }}')" 
                                            class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity p-1 hover:bg-error/10 rounded-full"
                                            title="Remove item"
                                        >
                                            <i class="fas fa-times text-error"></i>
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        <div class="pt-3 border-t border-base-300">
                            <div class="flex gap-2">
                                <x-input 
                                    wire:model="newWentWell" 
                                    placeholder="Add what went well..." 
                                    class="w-full focus:border-success/50 transition-all duration-300"
                                    icon="fas.thumbs-up"
                                />
                                <x-button 
                                    wire:click="addWentWell" 
                                    icon="fas.plus" 
                                    class="btn-success hover:shadow-md transition-all duration-300" 
                                    tooltip="Add item"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- What Could Be Improved -->
            <div class="bg-base-100 rounded-xl shadow-xl border border-base-300 overflow-hidden">
                <div class="bg-warning/10 p-4 border-b border-base-300 flex items-center gap-3">
                    <span class="p-2 rounded-full bg-warning/20 text-warning">
                        <i class="fas fa-arrow-trend-up text-lg"></i>
                    </span>
                    <h2 class="text-xl font-semibold text-warning">What Could Be Improved</h2>
                </div>
                
                <div class="p-5">
                    <div class="space-y-4">
                        @if(empty($whatCouldBeImproved))
                            <div class="flex flex-col items-center justify-center py-8 text-base-content/50">
                                <i class="fas fa-wrench text-3xl mb-2 text-warning/50"></i>
                                <p>No items added yet</p>
                                <p class="text-xs mt-1">Add your improvement suggestions below</p>
                            </div>
                        @else
                            <div class="max-h-[350px] overflow-y-auto pr-1 space-y-3">
                                @foreach($whatCouldBeImproved as $item)
                                    <div class="bg-warning/5 border border-warning/20 p-4 rounded-lg relative group hover:shadow-sm transition-all duration-200">
                                        <p class="text-base-content/90">{{ $item['content'] }}</p>
                                        <div class="mt-2 flex justify-between items-center text-xs text-base-content/50">
                                            <span>{{ \Carbon\Carbon::parse($item['created_at'])->diffForHumans() }}</span>
                                        </div>
                                        <button
                                            wire:click="removeItem('could_be_improved', '{{ $item['id'] }}')" 
                                            class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity p-1 hover:bg-error/10 rounded-full"
                                            title="Remove item"
                                        >
                                            <i class="fas fa-times text-error"></i>
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        <div class="pt-3 border-t border-base-300">
                            <div class="flex gap-2">
                                <x-input 
                                    wire:model="newCouldBeImproved" 
                                    placeholder="Add what could be improved..." 
                                    class="w-full focus:border-warning/50 transition-all duration-300"
                                    icon="fas.lightbulb"
                                />
                                <x-button 
                                    wire:click="addCouldBeImproved" 
                                    icon="fas.plus" 
                                    class="btn-warning hover:shadow-md transition-all duration-300" 
                                    tooltip="Add item"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Items -->
            <div class="bg-base-100 rounded-xl shadow-xl border border-base-300 overflow-hidden">
                <div class="bg-info/10 p-4 border-b border-base-300 flex items-center gap-3">
                    <span class="p-2 rounded-full bg-info/20 text-info">
                        <i class="fas fa-clipboard-check text-lg"></i>
                    </span>
                    <h2 class="text-xl font-semibold text-info">Action Items</h2>
                </div>
                
                <div class="p-5">
                    <div class="space-y-4">
                        @if(empty($actionItems))
                            <div class="flex flex-col items-center justify-center py-8 text-base-content/50">
                                <i class="fas fa-list-check text-3xl mb-2 text-info/50"></i>
                                <p>No action items added yet</p>
                                <p class="text-xs mt-1">Add tasks to address improvements</p>
                            </div>
                        @else
                            <div class="max-h-[350px] overflow-y-auto pr-1 space-y-3">
                                @foreach($actionItems as $item)
                                    <div class="bg-info/5 border border-info/20 p-4 rounded-lg relative group hover:shadow-sm transition-all duration-200 {{ ($item['is_completed'] ?? false) ? 'opacity-70' : '' }}">
                                        <div class="flex items-start gap-3">
                                            <div class="mt-0.5">
                                                <input
                                                    type="checkbox" 
                                                    class="checkbox checkbox-info" 
                                                    wire:click="toggleActionItem('{{ $item['id'] }}')" 
                                                    @if($item['is_completed'] ?? false) checked @endif
                                                />
                                            </div>
                                            <div class="flex-1">
                                                <p class="{{ ($item['is_completed'] ?? false) ? 'line-through text-base-content/50' : 'text-base-content/90' }}">
                                                    {{ $item['content'] }}
                                                </p>
                                                @if(!empty($item['assignee']))
                                                    <div class="mt-2 flex items-center gap-2 text-sm">
                                                        <div class="bg-info/10 text-info rounded-lg w-6 h-6 flex items-center justify-center">
                                                            <span class="text-xs font-medium">{{ substr($item['assignee'], 0, 1) }}</span>
                                                        </div>
                                                        <span class="text-base-content/70">{{ $item['assignee'] }}</span>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                        <button
                                            wire:click="removeItem('action_item', '{{ $item['id'] }}')" 
                                            class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity p-1 hover:bg-error/10 rounded-full"
                                            title="Remove item"
                                        >
                                            <i class="fas fa-times text-error"></i>
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        <div class="pt-3 border-t border-base-300 space-y-3">
                            <x-input 
                                wire:model="newActionItem" 
                                placeholder="Add action item..." 
                                class="w-full focus:border-info/50 transition-all duration-300"
                                icon="fas.list-check"
                            />
                            <div class="flex gap-2">
                                <x-select
                                    wire:model="newActionItemAssignee" 
                                    placeholder="Assign to..." 
                                    :options="$users->pluck('name', 'name')->toArray()"
                                    class="w-full focus:border-info/50 transition-all duration-300"
                                    icon="fas.user"
                                />
                                <x-button 
                                    wire:click="addActionItem" 
                                    icon="fas.plus" 
                                    class="btn-info hover:shadow-md transition-all duration-300" 
                                    tooltip="Add action item"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Retrospective Tips -->
        <div class="bg-base-100 rounded-xl shadow-xl border border-base-300 overflow-hidden mt-6">
            <div class="bg-primary/5 p-4 border-b border-base-300 flex items-center gap-3">
                <span class="p-2 rounded-full bg-primary/10 text-primary">
                    <i class="fas fa-lightbulb text-lg"></i>
                </span>
                <div>
                    <h2 class="text-xl font-semibold">Retrospective Tips</h2>
                    <p class="text-sm text-base-content/70">Best practices for effective sprint retrospectives</p>
                </div>
            </div>
            
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-success/5 border border-success/20 p-5 rounded-lg hover:shadow-md transition-all duration-300">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="p-2 rounded-full bg-success/10 text-success">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <h3 class="font-bold text-success">What Went Well</h3>
                        </div>
                        <ul class="space-y-2 text-sm">
                            <li class="flex items-start gap-2">
                                <i class="fas fa-star text-success mt-1"></i>
                                <span>Celebrate achievements and successes</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <i class="fas fa-users text-success mt-1"></i>
                                <span>Recognize good teamwork and collaboration</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <i class="fas fa-cogs text-success mt-1"></i>
                                <span>Identify processes that worked efficiently</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <i class="fas fa-chart-line text-success mt-1"></i>
                                <span>Highlight positive outcomes and results</span>
                            </li>
                        </ul>
                    </div>

                    <div class="bg-warning/5 border border-warning/20 p-5 rounded-lg hover:shadow-md transition-all duration-300">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="p-2 rounded-full bg-warning/10 text-warning">
                                <i class="fas fa-arrow-trend-up"></i>
                            </div>
                            <h3 class="font-bold text-warning">What Could Be Improved</h3>
                        </div>
                        <ul class="space-y-2 text-sm">
                            <li class="flex items-start gap-2">
                                <i class="fas fa-filter text-warning mt-1"></i>
                                <span>Identify bottlenecks and inefficiencies</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <i class="fas fa-comments text-warning mt-1"></i>
                                <span>Discuss communication challenges</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <i class="fas fa-bug text-warning mt-1"></i>
                                <span>Address technical issues or obstacles</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <i class="fas fa-comment text-warning mt-1"></i>
                                <span>Focus on constructive feedback, not blame</span>
                            </li>
                        </ul>
                    </div>

                    <div class="bg-info/5 border border-info/20 p-5 rounded-lg hover:shadow-md transition-all duration-300">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="p-2 rounded-full bg-info/10 text-info">
                                <i class="fas fa-clipboard-check"></i>
                            </div>
                            <h3 class="font-bold text-info">Action Items</h3>
                        </div>
                        <ul class="space-y-2 text-sm">
                            <li class="flex items-start gap-2">
                                <i class="fas fa-tasks text-info mt-1"></i>
                                <span>Create specific, measurable tasks</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <i class="fas fa-user-check text-info mt-1"></i>
                                <span>Assign clear ownership for each item</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <i class="fas fa-calendar text-info mt-1"></i>
                                <span>Set realistic timeframes for completion</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <i class="fas fa-history text-info mt-1"></i>
                                <span>Follow up on previous sprint's action items</span>
                            </li>
                        </ul>
                    </div>
                </div>
                
                <div class="mt-6 text-center text-sm text-base-content/70 border-t border-base-300 pt-4">
                    <p>Remember that the goal of a retrospective is to continuously improve your team's process and collaboration.</p>
                </div>
            </div>
        </div>
    </div>
</div>
    </div>
</div>
