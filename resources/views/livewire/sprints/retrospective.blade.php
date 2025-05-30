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

<div>
    <x-slot:title>Sprint Retrospective - {{ $sprint->name }}</x-slot:title>

    <div class="p-6">
        <div class="flex justify-between items-center mb-6">
            <div class="flex items-center gap-2">
                <x-button link="/projects/{{ $project->id }}/sprints/{{ $sprint->id }}" icon="o-arrow-left" class="btn-ghost btn-sm" />
                <h1 class="text-2xl font-bold text-primary">Sprint Retrospective: {{ $sprint->name }}</h1>
                <div class="badge {{ $sprint->is_completed ? 'badge-info' : ($sprint->is_active ? 'badge-success' : 'badge-warning') }}">
                    {{ $sprint->is_completed ? 'Completed' : ($sprint->is_active ? 'Active' : 'Planned') }}
                </div>
            </div>

            <div class="flex gap-2">
                <x-button link="/projects/{{ $project->id }}/sprints/{{ $sprint->id }}/report" label="View Report" icon="fas.chart-bar" class="btn-outline" />
                <x-button link="/projects/{{ $project->id }}/sprints/{{ $sprint->id }}/burndown" label="Burndown Chart" icon="fas.chart-line" class="btn-outline" />
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- What Went Well -->
            <div class="card bg-base-100 shadow-xl">
                <div class="card-body">
                    <h2 class="card-title text-success">
                        <x-icon name="o-check-circle" class="w-6 h-6" />
                        What Went Well
                    </h2>

                    <div class="mt-4 space-y-4">
                        @foreach($whatWentWell as $item)
                            <div class="bg-base-200 p-3 rounded-lg relative group">
                                <p>{{ $item['content'] }}</p>
                                <button
                                    wire:click="removeItem('went_well', '{{ $item['id'] }}')"
                                    class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity"
                                >
                                    <x-icon name="o-x-mark" class="w-4 h-4 text-error" />
                                </button>
                            </div>
                        @endforeach

                        @if(empty($whatWentWell))
                            <div class="text-center py-4 text-gray-500">
                                <p>No items added yet</p>
                            </div>
                        @endif

                        <div class="flex gap-2">
                            <x-input wire:model="newWentWell" placeholder="Add what went well..." class="w-full" />
                            <x-button wire:click="addWentWell" icon="o-plus" class="btn-success" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- What Could Be Improved -->
            <div class="card bg-base-100 shadow-xl">
                <div class="card-body">
                    <h2 class="card-title text-warning">
                        <x-icon name="o-arrow-trending-up" class="w-6 h-6" />
                        What Could Be Improved
                    </h2>

                    <div class="mt-4 space-y-4">
                        @foreach($whatCouldBeImproved as $item)
                            <div class="bg-base-200 p-3 rounded-lg relative group">
                                <p>{{ $item['content'] }}</p>
                                <button
                                    wire:click="removeItem('could_be_improved', '{{ $item['id'] }}')"
                                    class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity"
                                >
                                    <x-icon name="o-x-mark" class="w-4 h-4 text-error" />
                                </button>
                            </div>
                        @endforeach

                        @if(empty($whatCouldBeImproved))
                            <div class="text-center py-4 text-gray-500">
                                <p>No items added yet</p>
                            </div>
                        @endif

                        <div class="flex gap-2">
                            <x-input wire:model="newCouldBeImproved" placeholder="Add what could be improved..." class="w-full" />
                            <x-button wire:click="addCouldBeImproved" icon="o-plus" class="btn-warning" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Items -->
            <div class="card bg-base-100 shadow-xl">
                <div class="card-body">
                    <h2 class="card-title text-info">
                        <x-icon name="o-clipboard-document-check" class="w-6 h-6" />
                        Action Items
                    </h2>

                    <div class="mt-4 space-y-4">
                        @foreach($actionItems as $item)
                            <div class="bg-base-200 p-3 rounded-lg relative group">
                                <div class="flex items-start gap-2">
                                    <input
                                        type="checkbox"
                                        class="checkbox checkbox-info"
                                        wire:click="toggleActionItem('{{ $item['id'] }}')"
                                        @if($item['is_completed'] ?? false) checked @endif
                                    />
                                    <div class="flex-1">
                                        <p class="@if($item['is_completed'] ?? false) line-through text-gray-500 @endif">
                                            {{ $item['content'] }}
                                        </p>
                                        @if(!empty($item['assignee']))
                                            <div class="mt-1 flex items-center gap-1 text-sm text-gray-500">
                                                <x-icon name="o-user" class="w-4 h-4" />
                                                <span>{{ $item['assignee'] }}</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                                <button
                                    wire:click="removeItem('action_item', '{{ $item['id'] }}')"
                                    class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity"
                                >
                                    <x-icon name="o-x-mark" class="w-4 h-4 text-error" />
                                </button>
                            </div>
                        @endforeach

                        @if(empty($actionItems))
                            <div class="text-center py-4 text-gray-500">
                                <p>No action items added yet</p>
                            </div>
                        @endif

                        <div class="space-y-2">
                            <x-input wire:model="newActionItem" placeholder="Add action item..." class="w-full" />
                            <div class="flex gap-2">
                                <x-select
                                    wire:model="newActionItemAssignee"
                                    placeholder="Assign to..."
                                    :options="$users->pluck('name', 'name')->toArray()"
                                    class="w-full"
                                />
                                <x-button wire:click="addActionItem" icon="o-plus" class="btn-info" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Retrospective Tips -->
        <div class="card bg-base-100 shadow-xl mt-6">
            <div class="card-body">
                <h2 class="card-title">Retrospective Tips</h2>

                <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-success/10 p-4 rounded-lg">
                        <h3 class="font-bold text-success mb-2">What Went Well</h3>
                        <ul class="list-disc list-inside space-y-1 text-sm">
                            <li>Celebrate achievements and successes</li>
                            <li>Recognize good teamwork and collaboration</li>
                            <li>Identify processes that worked efficiently</li>
                            <li>Highlight positive outcomes and results</li>
                        </ul>
                    </div>

                    <div class="bg-warning/10 p-4 rounded-lg">
                        <h3 class="font-bold text-warning mb-2">What Could Be Improved</h3>
                        <ul class="list-disc list-inside space-y-1 text-sm">
                            <li>Identify bottlenecks and inefficiencies</li>
                            <li>Discuss communication challenges</li>
                            <li>Address technical issues or obstacles</li>
                            <li>Focus on constructive feedback, not blame</li>
                        </ul>
                    </div>

                    <div class="bg-info/10 p-4 rounded-lg">
                        <h3 class="font-bold text-info mb-2">Action Items</h3>
                        <ul class="list-disc list-inside space-y-1 text-sm">
                            <li>Create specific, measurable tasks</li>
                            <li>Assign clear ownership for each item</li>
                            <li>Set realistic timeframes for completion</li>
                            <li>Follow up on previous sprint's action items</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
