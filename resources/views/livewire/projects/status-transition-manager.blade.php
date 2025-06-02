<div>
    <x-card class="mb-4">
        <h2 class="text-lg font-bold mb-2">Status Transitions</h2>
        <p class="text-sm text-gray-600 mb-4">Define which status transitions are allowed in this project. Click on a status to manage its transitions.</p>
        
        <div class="flex flex-wrap gap-4 mb-6">
            @foreach($statuses as $status)
                <div class="p-2 rounded-lg shadow-sm border border-gray-200 cursor-pointer"
                     style="border-left: 4px solid {{ $status->color }};"
                     wire:key="status-{{ $status->id }}">
                    <div class="font-medium">{{ $status->name }}</div>
                    
                    <div x-show="open" class="mt-3 bg-gray-50 p-3 rounded">
                        <div class="text-sm font-medium mb-2">Allow transitions to:</div>
                        <div class="space-y-2">
                            @foreach($statuses as $to)
                                @if($status->id !== $to->id)
                                    <label class="flex items-center space-x-2">
                                        <input type="checkbox"
                                               wire:click.stop="toggleTransition({{ $status->id }}, {{ $to->id }})"
                                               @checked($transitions->where('from_status_id', $status->id)->where('to_status_id', $to->id)->count() > 0)
                                               class="rounded"
                                               @click.stop
                                        />
                                        <span class="text-sm" style="color: {{ $to->color }};">{{ $to->name }}</span>
                                    </label>
                                @endif
                            @endforeach
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        
        <div class="bg-gray-50 p-4 rounded-lg">
            <h3 class="font-medium mb-3">Workflow Visualization</h3>
            
            <div class="flex flex-wrap gap-4 justify-center mb-6">
                @foreach($statuses as $status)
                    <div class="p-3 rounded-lg shadow bg-white border-2 text-center min-w-[120px]"
                         style="border-color: {{ $status->color }};">
                        <div class="font-medium">{{ $status->name }}</div>
                        
                        @if($transitions->where('from_status_id', $status->id)->count() > 0)
                            <div class="text-xs text-gray-500 mt-1">Transitions to:</div>
                            <div class="flex flex-wrap gap-1 justify-center mt-1">
                                @foreach($transitions->where('from_status_id', $status->id) as $transition)
                                    @php
                                        $toStatus = $statuses->firstWhere('id', $transition->to_status_id);
                                    @endphp
                                    <span class="inline-flex items-center px-2 py-1 text-xs rounded-full"
                                          style="background-color: {{ $toStatus->color }}; color: white;">
                                        {{ $toStatus->name }}
                                        <svg class="w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                                        </svg>
                                    </span>
                                @endforeach
                            </div>
                        @else
                            <div class="text-xs text-gray-500 mt-2">No outgoing transitions</div>
                        @endif
                    </div>
                @endforeach
            </div>
            
            <div class="text-center text-sm text-gray-600 mt-2">
                <p>This visualization shows which status can transition to which other statuses.</p>
            </div>
        </div>
    </x-card>
</div>
