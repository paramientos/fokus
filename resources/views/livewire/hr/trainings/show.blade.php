<?php

use Livewire\Volt\Component;
use App\Models\Training;
use Mary\Traits\Toast;

new class extends Component {
    use Toast;

    public Training $training;

    public function mount(Training $training)
    {
        // Check workspace access
        if ($training->workspace_id !== session('workspace_id')) {
            abort(403);
        }

        $this->training = $training;
    }

    public function deleteTraining()
    {
        $this->training->delete();
        $this->success('Training deleted successfully!');
        return redirect()->route('hr.trainings.index');
    }

    public function getStatusColor($status)
    {
        return match($status) {
            'upcoming' => 'bg-blue-100 text-blue-800',
            'ongoing' => 'bg-yellow-100 text-yellow-800',
            'completed' => 'bg-green-100 text-green-800',
            default => 'bg-gray-100 text-gray-800'
        };
    }

    public function getTrainingStatus()
    {
        $now = now();
        if ($now < $this->training->start_date) {
            return 'upcoming';
        } elseif ($now >= $this->training->start_date && $now <= $this->training->end_date) {
            return 'ongoing';
        } else {
            return 'completed';
        }
    }

    public function getTypeColor($type)
    {
        return match($type) {
            'online' => 'bg-blue-100 text-blue-800',
            'classroom' => 'bg-green-100 text-green-800',
            'workshop' => 'bg-purple-100 text-purple-800',
            'conference' => 'bg-orange-100 text-orange-800',
            default => 'bg-gray-100 text-gray-800'
        };
    }
};
?>

<div>
    <x-header title="Training Details" separator progress-indicator>
        <x-slot:middle class="!justify-end">
            <x-button
                label="Back"
                icon="fas.arrow-left"
                link="/hr/trainings"
                class="btn-ghost"
            />
        </x-slot:middle>
    </x-header>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Training Overview -->
            <x-card title="Training Overview">
                <div class="space-y-4">
                    <div>
                        <h3 class="text-xl font-semibold text-gray-900">{{ $training->title }}</h3>
                        <div class="flex items-center gap-2 mt-2">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $this->getStatusColor($this->getTrainingStatus()) }}">
                                {{ ucwords($this->getTrainingStatus()) }}
                            </span>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $this->getTypeColor($training->type) }}">
                                {{ ucwords($training->type) }}
                            </span>
                            @if($training->is_mandatory)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                Mandatory
                            </span>
                            @endif
                        </div>
                    </div>

                    @if($training->description)
                    <div>
                        <h4 class="font-medium text-gray-900 mb-2">Description</h4>
                        <p class="text-gray-600">{{ $training->description }}</p>
                    </div>
                    @endif

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <h4 class="font-medium text-gray-900 mb-2">Provider</h4>
                            <p class="text-gray-600">{{ $training->provider }}</p>
                        </div>

                        <div>
                            <h4 class="font-medium text-gray-900 mb-2">Type</h4>
                            <p class="text-gray-600 capitalize">{{ $training->type }}</p>
                        </div>

                        <div>
                            <h4 class="font-medium text-gray-900 mb-2">Start Date</h4>
                            <p class="text-gray-600">{{ $training->start_date->format('M d, Y H:i') }}</p>
                        </div>

                        <div>
                            <h4 class="font-medium text-gray-900 mb-2">End Date</h4>
                            <p class="text-gray-600">{{ $training->end_date->format('M d, Y H:i') }}</p>
                        </div>

                        @if($training->location)
                        <div>
                            <h4 class="font-medium text-gray-900 mb-2">Location</h4>
                            <p class="text-gray-600">{{ $training->location }}</p>
                        </div>
                        @endif

                        <div>
                            <h4 class="font-medium text-gray-900 mb-2">Capacity</h4>
                            <p class="text-gray-600">{{ $training->capacity }} participants</p>
                        </div>
                    </div>

                    <!-- Duration Visualization -->
                    <div class="p-4 bg-blue-50 rounded-lg">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-medium text-blue-900">Training Duration</span>
                            <span class="text-sm text-blue-700">{{ $training->start_date->diffInDays($training->end_date) + 1 }} days</span>
                        </div>
                        <div class="flex items-center gap-2 text-sm text-blue-700">
                            <x-icon name="fas.calendar-alt" class="w-4 h-4" />
                            <span>{{ $training->start_date->format('M d') }} - {{ $training->end_date->format('M d, Y') }}</span>
                        </div>
                    </div>
                </div>
            </x-card>

            <!-- Prerequisites -->
            @if($training->prerequisites)
            <x-card title="Prerequisites">
                <div class="space-y-3">
                    @foreach(json_decode($training->prerequisites, true) as $prerequisite)
                    <div class="flex items-start gap-3 p-3 bg-yellow-50 rounded-lg">
                        <x-icon name="fas.exclamation-circle" class="w-5 h-5 text-yellow-600 mt-0.5" />
                        <div>
                            <p class="text-gray-900">{{ $prerequisite }}</p>
                        </div>
                    </div>
                    @endforeach
                </div>
            </x-card>
            @endif

            <!-- Training Progress -->
            <x-card title="Training Progress">
                <div class="space-y-4">
                    @php
                        $status = $this->getTrainingStatus();
                        $progress = 0;
                        if ($status === 'completed') {
                            $progress = 100;
                        } elseif ($status === 'ongoing') {
                            $totalDays = $training->start_date->diffInDays($training->end_date) + 1;
                            $daysPassed = $training->start_date->diffInDays(now()) + 1;
                            $progress = min(100, ($daysPassed / $totalDays) * 100);
                        }
                    @endphp

                    <div>
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-sm font-medium text-gray-700">Overall Progress</span>
                            <span class="text-sm font-medium text-gray-900">{{ round($progress) }}%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-3">
                            <div class="h-3 rounded-full {{ $progress >= 100 ? 'bg-green-500' : ($progress > 0 ? 'bg-blue-500' : 'bg-gray-400') }}"
                                 style="width: {{ $progress }}%"></div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="text-center p-4 bg-gray-50 rounded-lg">
                            <div class="text-2xl font-bold text-gray-900">{{ $training->start_date->diffInDays($training->end_date) + 1 }}</div>
                            <div class="text-sm text-gray-600">Total Days</div>
                        </div>
                        <div class="text-center p-4 bg-blue-50 rounded-lg">
                            <div class="text-2xl font-bold text-blue-600">
                                @if($status === 'upcoming')
                                    0
                                @elseif($status === 'ongoing')
                                    {{ $training->start_date->diffInDays(now()) + 1 }}
                                @else
                                    {{ $training->start_date->diffInDays($training->end_date) + 1 }}
                                @endif
                            </div>
                            <div class="text-sm text-gray-600">Days Completed</div>
                        </div>
                        <div class="text-center p-4 bg-green-50 rounded-lg">
                            <div class="text-2xl font-bold text-green-600">
                                @if($status === 'upcoming')
                                    {{ $training->start_date->diffInDays($training->end_date) + 1 }}
                                @elseif($status === 'ongoing')
                                    {{ now()->diffInDays($training->end_date) }}
                                @else
                                    0
                                @endif
                            </div>
                            <div class="text-sm text-gray-600">Days Remaining</div>
                        </div>
                    </div>
                </div>
            </x-card>

            <!-- Timeline -->
            <x-card title="Training Timeline">
                <div class="space-y-4">
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                            <x-icon name="fas.plus" class="w-4 h-4 text-blue-600" />
                        </div>
                        <div>
                            <h5 class="font-medium text-gray-900">Training Created</h5>
                            <p class="text-gray-600 text-sm">{{ $training->created_at->format('M d, Y H:i') }}</p>
                        </div>
                    </div>

                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 {{ $status === 'upcoming' ? 'bg-yellow-100' : 'bg-green-100' }} rounded-full flex items-center justify-center">
                            <x-icon name="fas.{{ $status === 'upcoming' ? 'clock' : 'play' }}" class="w-4 h-4 {{ $status === 'upcoming' ? 'text-yellow-600' : 'text-green-600' }}" />
                        </div>
                        <div>
                            <h5 class="font-medium text-gray-900">Training {{ $status === 'upcoming' ? 'Starts' : 'Started' }}</h5>
                            <p class="text-gray-600 text-sm">{{ $training->start_date->format('M d, Y H:i') }}</p>
                            @if($status === 'upcoming')
                            <p class="text-gray-600 text-sm">{{ $training->start_date->diffForHumans() }}</p>
                            @endif
                        </div>
                    </div>

                    @if($status !== 'upcoming')
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 {{ $status === 'completed' ? 'bg-green-100' : 'bg-yellow-100' }} rounded-full flex items-center justify-center">
                            <x-icon name="fas.{{ $status === 'completed' ? 'check' : 'clock' }}" class="w-4 h-4 {{ $status === 'completed' ? 'text-green-600' : 'text-yellow-600' }}" />
                        </div>
                        <div>
                            <h5 class="font-medium text-gray-900">Training {{ $status === 'completed' ? 'Completed' : 'Ends' }}</h5>
                            <p class="text-gray-600 text-sm">{{ $training->end_date->format('M d, Y H:i') }}</p>
                            @if($status === 'ongoing')
                            <p class="text-gray-600 text-sm">{{ $training->end_date->diffForHumans() }}</p>
                            @endif
                        </div>
                    </div>
                    @endif
                </div>
            </x-card>
        </div>

        <!-- Sidebar -->
        <div class="lg:col-span-1 space-y-6">
            <!-- Quick Actions -->
            <x-card title="Actions">
                <div class="space-y-3">
                    <x-button
                        label="Print Details"
                        icon="fas.print"
                        onclick="window.print()"
                        class="btn-outline w-full"
                    />

                    <x-button
                        label="Delete Training"
                        icon="fas.trash"
                        wire:click="deleteTraining"
                        wire:confirm="Are you sure you want to delete this training?"
                        class="btn-error w-full"
                        spinner="deleteTraining"
                    />
                </div>
            </x-card>

            <!-- Training Info -->
            <x-card title="Training Information">
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Created:</span>
                        <span class="font-medium">{{ $training->created_at->format('M d, Y') }}</span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-600">Updated:</span>
                        <span class="font-medium">{{ $training->updated_at->format('M d, Y') }}</span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-600">Duration:</span>
                        <span class="font-medium">{{ $training->start_date->diffInDays($training->end_date) + 1 }} days</span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-600">Capacity:</span>
                        <span class="font-medium">{{ $training->capacity }} people</span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-600">Type:</span>
                        <span class="font-medium capitalize">{{ $training->type }}</span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-600">Mandatory:</span>
                        <span class="font-medium {{ $training->is_mandatory ? 'text-red-600' : 'text-green-600' }}">
                            {{ $training->is_mandatory ? 'Yes' : 'No' }}
                        </span>
                    </div>

                    @if($training->prerequisites)
                    <div class="flex justify-between">
                        <span class="text-gray-600">Prerequisites:</span>
                        <span class="font-medium">{{ count(json_decode($training->prerequisites, true)) }}</span>
                    </div>
                    @endif
                </div>
            </x-card>

            <!-- Status Card -->
            <x-card title="Current Status">
                <div class="text-center">
                    <div class="w-16 h-16 {{ $this->getStatusColor($this->getTrainingStatus()) }} rounded-full mx-auto mb-3 flex items-center justify-center">
                        <x-icon name="fas.{{ $status === 'upcoming' ? 'clock' : ($status === 'ongoing' ? 'play' : 'check') }}" class="w-6 h-6" />
                    </div>
                    <h3 class="font-semibold text-lg capitalize">{{ $this->getTrainingStatus() }}</h3>
                    <p class="text-gray-600 text-sm mt-1">
                        @if($status === 'upcoming')
                            Starts {{ $training->start_date->diffForHumans() }}
                        @elseif($status === 'ongoing')
                            Ends {{ $training->end_date->diffForHumans() }}
                        @else
                            Completed {{ $training->end_date->diffForHumans() }}
                        @endif
                    </p>
                </div>
            </x-card>
        </div>
    </div>
</div>
