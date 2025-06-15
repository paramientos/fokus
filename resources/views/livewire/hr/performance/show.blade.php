<?php

use Livewire\Volt\Component;
use App\Models\PerformanceReview;
use Mary\Traits\Toast;

new class extends Component {
    use Toast;

    public PerformanceReview $review;

    public function mount(PerformanceReview $review)
    {
        // Check workspace access
        if ($review->workspace_id !== session('workspace_id')) {
            abort(403);
        }

        $this->review = $review->load(['employee.user', 'reviewer.user']);
    }

    public function deleteReview()
    {
        $this->review->delete();
        $this->success('Performance review deleted successfully!');
        return redirect()->route('hr.performance.index');
    }

    public function getStatusColor($status)
    {
        return match($status) {
            'draft' => 'bg-gray-100 text-gray-800',
            'in_progress' => 'bg-blue-100 text-blue-800',
            'completed' => 'bg-green-100 text-green-800',
            'cancelled' => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-800'
        };
    }

    public function getRatingColor($rating)
    {
        if ($rating >= 4.5) return 'text-green-600';
        if ($rating >= 3.5) return 'text-blue-600';
        if ($rating >= 2.5) return 'text-yellow-600';
        return 'text-red-600';
    }
};
?>

<div>
    <x-header title="Performance Review Details" separator progress-indicator>
        <x-slot:middle class="!justify-end">
            <x-button
                label="Edit"
                icon="fas.edit"
                link="/hr/performance/{{ $review->id }}/edit"
                class="btn-primary"
            />
            <x-button
                label="Back"
                icon="fas.arrow-left"
                link="/hr/performance"
                class="btn-ghost"
            />
        </x-slot:middle>
    </x-header>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Review Overview -->
            <x-card title="Review Overview">
                <div class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <h4 class="font-medium text-gray-900 mb-2">Employee</h4>
                            <p class="text-gray-600">{{ $review->employee->user->name }}</p>
                        </div>

                        <div>
                            <h4 class="font-medium text-gray-900 mb-2">Reviewer</h4>
                            <p class="text-gray-600">{{ $review->reviewer->user->name }}</p>
                        </div>

                        <div>
                            <h4 class="font-medium text-gray-900 mb-2">Review Date</h4>
                            <p class="text-gray-600">{{ $review->review_date->format('M d, Y') }}</p>
                        </div>

                        <div>
                            <h4 class="font-medium text-gray-900 mb-2">Status</h4>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $this->getStatusColor($review->status) }}">
                                {{ ucwords(str_replace('_', ' ', $review->status)) }}
                            </span>
                        </div>
                    </div>

                    <div class="text-center p-6 bg-gray-50 rounded-lg">
                        <div class="text-4xl font-bold {{ $this->getRatingColor($review->overall_rating) }} mb-2">
                            {{ number_format($review->overall_rating, 1) }}
                        </div>
                        <div class="text-sm text-gray-600">Overall Rating</div>
                        <div class="flex justify-center mt-2">
                            @for($i = 1; $i <= 5; $i++)
                                <x-icon
                                    name="fas.star"
                                    class="w-5 h-5 {{ $i <= $review->overall_rating ? 'text-yellow-400' : 'text-gray-300' }}"
                                />
                            @endfor
                        </div>
                    </div>
                </div>
            </x-card>

            <!-- Goals -->
            @if($review->goals)
            <x-card title="Goals & Objectives">
                <div class="space-y-3">
                    @foreach(json_decode($review->goals, true) as $goal)
                    <div class="p-4 bg-gray-50 rounded-lg">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <h5 class="font-medium text-gray-900">{{ $goal['title'] }}</h5>
                                @if(isset($goal['description']))
                                <p class="text-gray-600 text-sm mt-1">{{ $goal['description'] }}</p>
                                @endif
                            </div>
                            @if(isset($goal['achieved']))
                            <span class="ml-4 inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $goal['achieved'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $goal['achieved'] ? 'Achieved' : 'Not Achieved' }}
                            </span>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
            </x-card>
            @endif

            <!-- Strengths -->
            @if($review->strengths)
            <x-card title="Strengths">
                <div class="space-y-3">
                    @foreach(json_decode($review->strengths, true) as $strength)
                    <div class="flex items-start gap-3 p-3 bg-green-50 rounded-lg">
                        <x-icon name="fas.check-circle" class="w-5 h-5 text-green-600 mt-0.5" />
                        <div>
                            <h5 class="font-medium text-gray-900">{{ $strength['title'] }}</h5>
                            @if(isset($strength['description']))
                            <p class="text-gray-600 text-sm mt-1">{{ $strength['description'] }}</p>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
            </x-card>
            @endif

            <!-- Improvement Areas -->
            @if($review->improvement_areas)
            <x-card title="Areas for Improvement">
                <div class="space-y-3">
                    @foreach(json_decode($review->improvement_areas, true) as $area)
                    <div class="flex items-start gap-3 p-3 bg-yellow-50 rounded-lg">
                        <x-icon name="fas.exclamation-triangle" class="w-5 h-5 text-yellow-600 mt-0.5" />
                        <div>
                            <h5 class="font-medium text-gray-900">{{ $area['title'] }}</h5>
                            @if(isset($area['description']))
                            <p class="text-gray-600 text-sm mt-1">{{ $area['description'] }}</p>
                            @endif
                            @if(isset($area['action_plan']))
                            <p class="text-blue-600 text-sm mt-2"><strong>Action Plan:</strong> {{ $area['action_plan'] }}</p>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
            </x-card>
            @endif

            <!-- Comments -->
            @if($review->comments)
            <x-card title="Additional Comments">
                <p class="text-gray-600">{{ $review->comments }}</p>
            </x-card>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="lg:col-span-1 space-y-6">
            <!-- Quick Actions -->
            <x-card title="Actions">
                <div class="space-y-3">
                    <x-button
                        label="Edit Review"
                        icon="fas.edit"
                        link="/hr/performance/{{ $review->id }}/edit"
                        class="btn-primary w-full"
                    />

                    <x-button
                        label="Print Review"
                        icon="fas.print"
                        onclick="window.print()"
                        class="btn-outline w-full"
                    />

                    <x-button
                        label="Delete Review"
                        icon="fas.trash"
                        wire:click="deleteReview"
                        wire:confirm="Are you sure you want to delete this performance review?"
                        class="btn-error w-full"
                        spinner="deleteReview"
                    />
                </div>
            </x-card>

            <!-- Review Info -->
            <x-card title="Review Information">
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Created:</span>
                        <span class="font-medium">{{ $review->created_at->format('M d, Y') }}</span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-600">Updated:</span>
                        <span class="font-medium">{{ $review->updated_at->format('M d, Y') }}</span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-600">Period:</span>
                        <span class="font-medium">{{ $review->review_date->format('M Y') }}</span>
                    </div>

                    @if($review->goals)
                    <div class="flex justify-between">
                        <span class="text-gray-600">Goals:</span>
                        <span class="font-medium">{{ count(json_decode($review->goals, true)) }}</span>
                    </div>
                    @endif

                    @if($review->strengths)
                    <div class="flex justify-between">
                        <span class="text-gray-600">Strengths:</span>
                        <span class="font-medium">{{ count(json_decode($review->strengths, true)) }}</span>
                    </div>
                    @endif

                    @if($review->improvement_areas)
                    <div class="flex justify-between">
                        <span class="text-gray-600">Improvements:</span>
                        <span class="font-medium">{{ count(json_decode($review->improvement_areas, true)) }}</span>
                    </div>
                    @endif
                </div>
            </x-card>

            <!-- Employee Info -->
            <x-card title="Employee Details">
                <div class="text-center mb-4">
                    <div class="w-16 h-16 bg-gray-300 rounded-full mx-auto mb-3 flex items-center justify-center">
                        <x-icon name="fas.user" class="w-6 h-6 text-gray-600" />
                    </div>
                    <h3 class="font-semibold">{{ $review->employee->user->name }}</h3>
                    <p class="text-gray-600 text-sm">{{ $review->employee->position }}</p>
                </div>

                <div class="space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Department:</span>
                        <span class="font-medium">{{ $review->employee->department }}</span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-600">Hire Date:</span>
                        <span class="font-medium">{{ $review->employee->hire_date->format('M d, Y') }}</span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-600">Status:</span>
                        <span class="font-medium capitalize text-green-600">{{ $review->employee->status }}</span>
                    </div>
                </div>
            </x-card>
        </div>
    </div>
</div>
