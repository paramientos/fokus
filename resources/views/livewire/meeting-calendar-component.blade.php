<div>
    <div class="card bg-base-100 shadow-xl">
        <div class="card-body">
            <div class="flex justify-between items-center mb-4">
                <h2 class="card-title">{{ $currentMonthName }}</h2>
                <div class="flex gap-2">
                    <x-button wire:click="previousMonth" icon="fas.chevron-left" class="btn-sm"></x-button>
                    <x-button wire:click="nextMonth" icon="fas.chevron-right" class="btn-sm"></x-button>
                </div>
            </div>
            
            <div class="flex justify-between items-center mb-4">
                <div class="w-1/2 mr-2">
                    <select wire:model.live="projectId" class="select select-bordered w-full">
                        <option value="">All Projects</option>
                        @foreach($projects as $project)
                            <option value="{{ $project->id }}">{{ $project->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="w-1/2 ml-2">
                    <select wire:model.live="selectedType" class="select select-bordered w-full">
                        @foreach($meetingTypes as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            
            <div class="grid grid-cols-7 gap-1">
                <div class="text-center font-semibold">Sun</div>
                <div class="text-center font-semibold">Mon</div>
                <div class="text-center font-semibold">Tue</div>
                <div class="text-center font-semibold">Wed</div>
                <div class="text-center font-semibold">Thu</div>
                <div class="text-center font-semibold">Fri</div>
                <div class="text-center font-semibold">Sat</div>
                
                @foreach($calendarDays as $day)
                    <div 
                        wire:click="{{ $day['date'] ? 'selectDate(\'' . $day['date'] . '\')' : '' }}"
                        class="h-24 {{ $day['isCurrentMonth'] ? ($day['isToday'] ? 'bg-blue-50' : 'bg-base-100') : 'bg-gray-100 opacity-50' }} 
                               {{ $day['isSelected'] ? 'border-2 border-primary' : 'border border-gray-200' }} 
                               rounded p-1 {{ $day['date'] ? 'cursor-pointer hover:bg-blue-50' : '' }} transition-colors"
                    >
                        @if($day['day'])
                            <div class="text-right {{ $day['isToday'] ? 'font-bold text-primary' : '' }}">
                                {{ $day['day'] }}
                            </div>
                            
                            @php
                                $dayMeetings = collect($day['meetings'])->take(3);
                            @endphp
                            
                            @foreach($dayMeetings as $meeting)
                                <div class="text-xs truncate 
                                    {{ match($meeting->meeting_type) {
                                        'daily' => 'bg-green-100 text-green-800',
                                        'planning' => 'bg-blue-100 text-blue-800',
                                        'retro' => 'bg-purple-100 text-purple-800',
                                        default => 'bg-gray-100 text-gray-800',
                                    } }} rounded px-1 py-0.5 mb-0.5"
                                >
                                    {{ $meeting->scheduled_at->format('H:i') }} {{ $meeting->title }}
                                </div>
                            @endforeach
                            
                            @if(count($day['meetings']) > 3)
                                <div class="text-xs text-gray-500">+{{ count($day['meetings']) - 3 }} more</div>
                            @endif
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    
    @if($selectedDate && $selectedDateMeetings->count() > 0)
        <div class="card bg-base-100 shadow-xl mt-6">
            <div class="card-body">
                <h2 class="card-title mb-4">
                    Meetings on {{ \Carbon\Carbon::parse($selectedDate)->format('F d, Y') }}
                </h2>
                
                <div class="space-y-4">
                    @foreach($selectedDateMeetings as $meeting)
                        <div class="border-l-4 
                            {{ match($meeting->meeting_type) {
                                'daily' => 'border-green-500',
                                'planning' => 'border-blue-500',
                                'retro' => 'border-purple-500',
                                default => 'border-gray-500',
                            } }} pl-4 py-2"
                        >
                            <div class="flex justify-between">
                                <div>
                                    <a href="/meetings/{{ $meeting->id }}" class="font-medium hover:text-primary">
                                        {{ $meeting->title }}
                                    </a>
                                    <div class="text-sm text-gray-500">
                                        {{ $meeting->scheduled_at->format('H:i') }} - 
                                        {{ $meeting->scheduled_at->addMinutes($meeting->duration)->format('H:i') }} 
                                        ({{ $meeting->duration }} min)
                                    </div>
                                </div>
                                <div>
                                    <x-badge :color="match($meeting->meeting_type) {
                                        'daily' => 'success',
                                        'planning' => 'info',
                                        'retro' => 'secondary',
                                        default => 'neutral',
                                    }">
                                        {{ ucfirst($meeting->meeting_type) }}
                                    </x-badge>
                                </div>
                            </div>
                            
                            <div class="mt-2 flex justify-between items-center">
                                <div class="text-sm">
                                    <span class="text-gray-500">Project:</span> 
                                    <a href="/projects/{{ $meeting->project_id }}" class="hover:text-primary">
                                        {{ $meeting->project->name }}
                                    </a>
                                </div>
                                <div class="flex gap-1">
                                    <x-button link="/meetings/{{ $meeting->id }}" icon="fas.eye" size="xs">View</x-button>
                                    @if($meeting->status === 'scheduled')
                                        <x-button link="/meetings/{{ $meeting->id }}" icon="fas.play" size="xs" color="success">Join</x-button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif
</div>
