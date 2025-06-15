<?php

use App\Models\Project;
use App\Services\HealthNotificationService;
use App\Services\HealthRecommendationService;
use App\Services\ProjectHealthService;
use Livewire\Volt\Component;
use Mary\Traits\Toast;

new class extends Component {
    use Toast;

    public Project $project;
    public array $healthData = [];
    public array $recommendations = [];
    public array $quickWins = [];
    public array $insights = [];
    public bool $showRecommendations = true;
    public bool $showInsights = false;

    // Alert management
    public $selectedAlert = null;
    public bool $showAlertModal = false;

    // Notification preferences
    public bool $emailNotifications = true;
    public bool $criticalAlerts = true;
    public bool $dailyDigest = false;
    public bool $weeklyReport = false;

    public function mount(Project $project)
    {
        $this->project = $project;
        $this->loadHealthData();
        $this->loadRecommendations();
    }

    public function loadHealthData()
    {
        $healthService = new ProjectHealthService();
        $this->healthData = $healthService->calculateHealthScore($this->project);
    }

    public function loadRecommendations()
    {
        $recommendationService = new HealthRecommendationService();
        $this->recommendations = $recommendationService->generateRecommendations($this->project);
        $this->quickWins = $recommendationService->getQuickWins($this->project);
        $this->insights = $recommendationService->getActionableInsights($this->project);
    }

    public function refreshData()
    {
        $this->loadHealthData();
        $this->loadRecommendations();
        $this->success('Health data refreshed successfully!');
    }

   /* public function resolveAlert($alertId, $resolution = null)
    {
        $alert = $this->project->alerts()->findOrFail($alertId);
        $alert->resolve(auth()->user(), $resolution);

        $this->loadHealthData();
        $this->success('Alert resolved successfully!');
    }*/

    public function sendTestNotification()
    {
        $notificationService = new HealthNotificationService();
        $notificationService->sendCriticalAlerts($this->project);
        $this->success('Test notification sent!');
    }

    public function updateNotificationPreferences()
    {
        // Save notification preferences to user settings or project settings
        $this->success('Notification preferences updated!');
    }

    public function toggleRecommendations()
    {
        $this->showRecommendations = !$this->showRecommendations;
    }

    public function toggleInsights()
    {
        $this->showInsights = !$this->showInsights;
    }

    public function getHealthStatusProperty()
    {
        $score = $this->healthData['health_score'] ?? 0;

        return match (true) {
            $score >= 80 => ['status' => 'excellent', 'color' => 'success', 'icon' => 'fas.check-circle'],
            $score >= 60 => ['status' => 'good', 'color' => 'info', 'icon' => 'fas.info-circle'],
            $score >= 40 => ['status' => 'warning', 'color' => 'warning', 'icon' => 'fas.exclamation-triangle'],
            default => ['status' => 'critical', 'color' => 'error', 'icon' => 'fas.exclamation-circle']
        };
    }

    public function getAlertsProperty()
    {
        return $this->project->alerts()
            ->with('user', 'resolvedBy')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function showAlert($alertId)
    {
        $this->selectedAlert = $this->project->alerts()->with('user', 'resolvedBy')->find($alertId);
        $this->showAlertModal = true;
    }

    public function resolveAlert(): void
    {
        if ($this->selectedAlert && !$this->selectedAlert->is_resolved) {
            $this->selectedAlert->update([
                'is_resolved' => true,
                'resolved_at' => now(),
                'resolved_by' => auth()->id(),
            ]);

            $this->success('Alert resolved successfully!');
            $this->showAlertModal = false;
            $this->selectedAlert = null;
        }
    }

    public function with(): array
    {
        return [
            'alerts' => $this->getAlertsProperty(),
            'healthStatus' => $this->getHealthStatusProperty(),
        ];
    }
}; ?>

<div class="space-y-6">
    <!-- Header with Actions -->
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Project Health Dashboard</h2>
            <p class="text-gray-600">Monitor your project's health and get AI-powered recommendations</p>
        </div>
        <div class="flex gap-2">
            <x-button wire:click="refreshData" icon="fas.user" class="btn-outline">
                Refresh
            </x-button>
            <x-button wire:click="sendTestNotification" icon="fas.bell" class="btn-outline">
                Test Alert
            </x-button>
            <x-button no-wire-navigate link="{{ route('projects.health-analytics', $project) }}" icon="fas.chart-line" class="btn-primary">
                Advanced Analytics
            </x-button>
        </div>
    </div>

    <!-- Health Score Overview -->
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <!-- Main Health Score -->
        <div class="lg:col-span-1">
            <x-card class="text-center">
                <div class="mb-4">
                    <div
                        class="text-4xl font-bold {{ $this->healthStatus['color'] === 'success' ? 'text-green-600' : ($this->healthStatus['color'] === 'info' ? 'text-blue-600' : ($this->healthStatus['color'] === 'warning' ? 'text-yellow-600' : 'text-red-600')) }}">
                        {{ number_format($healthData['health_score'] ?? 0, 1) }}%
                    </div>
                    <div class="text-sm text-gray-500 uppercase tracking-wide">Health Score</div>
                </div>
                <x-badge :value="ucfirst($this->healthStatus['status'])"
                         class="badge-{{ $this->healthStatus['color'] }}"/>
            </x-card>
        </div>

        <!-- Key Metrics -->
        <div class="lg:col-span-3">
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <x-card>
                    <div class="text-center">
                        <div
                            class="text-2xl font-bold text-green-600">{{ $healthData['metrics']['completed_tasks'] ?? 0 }}</div>
                        <div class="text-sm text-gray-500">Completed</div>
                    </div>
                </x-card>
                <x-card>
                    <div class="text-center">
                        <div
                            class="text-2xl font-bold text-red-600">{{ $healthData['metrics']['overdue_tasks'] ?? 0 }}</div>
                        <div class="text-sm text-gray-500">Overdue</div>
                    </div>
                </x-card>
                <x-card>
                    <div class="text-center">
                        <div
                            class="text-2xl font-bold text-yellow-600">{{ $healthData['metrics']['blocked_tasks'] ?? 0 }}</div>
                        <div class="text-sm text-gray-500">Blocked</div>
                    </div>
                </x-card>
                <x-card>
                    <div class="text-center">
                        <div
                            class="text-2xl font-bold text-blue-600">{{ number_format($healthData['metrics']['velocity'] ?? 0, 1) }}</div>
                        <div class="text-sm text-gray-500">Velocity</div>
                    </div>
                </x-card>
            </div>
        </div>
    </div>

    <!-- AI Recommendations Section -->
    @if($showRecommendations && !empty($recommendations))
        <x-card>
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold flex items-center gap-2">
                    <x-icon name="fas.lightbulb" class="w-5 h-5 text-yellow-500"/>
                    AI Recommendations
                </h3>
                <x-button wire:click="toggleRecommendations" icon="fas.chevron-up" class="btn-ghost btn-sm"/>
            </div>

            <!-- Quick Wins -->
            @if(!empty($quickWins))
                <div class="mb-6">
                    <h4 class="text-md font-medium text-green-700 mb-3 flex items-center gap-2">
                        <x-icon name="fas.zap" class="w-4 h-4"/>
                        Quick Wins (Low Effort, High Impact)
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach($quickWins as $quickWin)
                            <div class="border border-green-200 rounded-lg p-4 bg-green-50">
                                <div class="flex justify-between items-start mb-2">
                                    <h5 class="font-medium text-green-800">{{ $quickWin['title'] }}</h5>
                                    <x-badge value="{{ ucfirst($quickWin['priority']) }}"
                                             class="badge-{{ $quickWin['priority'] === 'critical' ? 'error' : ($quickWin['priority'] === 'high' ? 'warning' : 'info') }}"/>
                                </div>
                                <p class="text-sm text-green-700 mb-3">{{ $quickWin['description'] }}</p>
                                <div class="space-y-1">
                                    @foreach($quickWin['actions'] as $action)
                                        <div class="text-xs text-green-600 flex items-center gap-1">
                                            <x-icon name="fas.check" class="w-3 h-3"/>
                                            {{ $action }}
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- All Recommendations -->
            <div class="space-y-4">
                @foreach($recommendations as $recommendation)
                    <div
                        class="border rounded-lg p-4 {{ $recommendation['priority'] === 'critical' ? 'border-red-200 bg-red-50' : ($recommendation['priority'] === 'high' ? 'border-orange-200 bg-orange-50' : 'border-gray-200 bg-gray-50') }}">
                        <div class="flex justify-between items-start mb-2">
                            <h5 class="font-medium {{ $recommendation['priority'] === 'critical' ? 'text-red-800' : ($recommendation['priority'] === 'high' ? 'text-orange-800' : 'text-gray-800') }}">
                                {{ $recommendation['title'] }}
                            </h5>
                            <div class="flex gap-2">
                                <x-badge value="{{ ucfirst($recommendation['priority']) }}"
                                         class="badge-{{ $recommendation['priority'] === 'critical' ? 'error' : ($recommendation['priority'] === 'high' ? 'warning' : 'info') }}"/>
                                <x-badge value="{{ ucfirst($recommendation['effort']) }} Effort" class="badge-outline"/>
                                <x-badge value="{{ ucfirst($recommendation['impact']) }} Impact" class="badge-outline"/>
                            </div>
                        </div>
                        <p class="text-sm {{ $recommendation['priority'] === 'critical' ? 'text-red-700' : ($recommendation['priority'] === 'high' ? 'text-orange-700' : 'text-gray-700') }} mb-3">
                            {{ $recommendation['description'] }}
                        </p>
                        <div class="space-y-1">
                            @foreach($recommendation['actions'] as $action)
                                <div
                                    class="text-xs {{ $recommendation['priority'] === 'critical' ? 'text-red-600' : ($recommendation['priority'] === 'high' ? 'text-orange-600' : 'text-gray-600') }} flex items-center gap-1">
                                    <x-icon name="fas.arrow-right" class="w-3 h-3"/>
                                    {{ $action }}
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </x-card>
    @endif

    <!-- Actionable Insights -->
    @if($showInsights && !empty($insights))
        <x-card>
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold flex items-center gap-2">
                    <x-icon name="fas.chart-line" class="w-5 h-5 text-blue-500"/>
                    Actionable Insights
                </h3>
                <x-button wire:click="toggleInsights" icon="fas.chevron-up" class="btn-ghost btn-sm"/>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Health Score Interpretation -->
                @if(isset($insights['health_score_interpretation']))
                    <div class="space-y-3">
                        <h4 class="font-medium text-gray-800">Health Score Analysis</h4>
                        <div class="p-3 bg-blue-50 rounded-lg">
                            <p class="text-sm text-blue-800 font-medium">{{ $insights['health_score_interpretation']['message'] }}</p>
                            <p class="text-xs text-blue-600 mt-1">{{ $insights['health_score_interpretation']['advice'] }}</p>
                        </div>
                    </div>
                @endif

                <!-- Velocity Analysis -->
                @if(isset($insights['velocity_analysis']))
                    <div class="space-y-3">
                        <h4 class="font-medium text-gray-800">Velocity Analysis</h4>
                        <div class="p-3 bg-green-50 rounded-lg">
                            <div class="flex justify-between items-center mb-1">
                                <span class="text-sm text-green-800">Average Velocity</span>
                                <span
                                    class="font-medium text-green-800">{{ $insights['velocity_analysis']['average_velocity'] ?? 'N/A' }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-green-800">Trend</span>
                                <x-badge value="{{ ucfirst($insights['velocity_analysis']['trend'] ?? 'stable') }}"
                                         class="badge-outline"/>
                            </div>
                            @if(isset($insights['velocity_analysis']['recommendation']))
                                <p class="text-xs text-green-600 mt-2">{{ $insights['velocity_analysis']['recommendation'] }}</p>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </x-card>
    @endif

    <!-- Alerts -->
    <div class="bg-white rounded-lg shadow">
        <div class="p-6 border-b">
            <h3 class="text-lg font-semibold flex items-center">
                <x-icon name="fas.bell" class="w-5 h-5 mr-2 text-blue-500"/>
                Project Alerts
                @if($alerts->where('is_resolved', false)->count() > 0)
                    <x-badge :value="$alerts->where('is_resolved', false)->count()" class="badge-error ml-2"/>
                @endif
            </h3>
        </div>

        <div class="divide-y">
            @forelse($alerts as $alert)
                <div class="p-4 hover:bg-gray-50 cursor-pointer" wire:click="showAlert({{ $alert->id }})">
                    <div class="flex items-start justify-between">
                        <div class="flex items-start space-x-3">
                            <x-icon :name="$alert->type_icon"
                                    class="w-5 h-5 mt-0.5 text-{{ $alert->severity_color }}-500"/>
                            <div>
                                <h4 class="font-medium text-gray-900">{{ $alert->title }}</h4>
                                <p class="text-sm text-gray-600 mt-1">{{ $alert->description }}</p>
                                <div class="flex items-center space-x-4 mt-2 text-xs text-gray-500">
                                    <span>{{ $alert->created_at->diffForHumans() }}</span>
                                    @if($alert->is_resolved)
                                        <span
                                            class="text-green-600">Resolved by {{ $alert->resolvedBy->name ?? 'Unknown' }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center space-x-2">
                            <x-badge :value="ucfirst($alert->severity)" class="badge-{{ $alert->severity_color }}"/>
                            @if($alert->is_resolved)
                                <x-icon name="fas.check-circle" class="w-4 h-4 text-green-500"/>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="p-8 text-center text-gray-500">
                    <x-icon name="fas.bell-slash" class="w-12 h-12 mx-auto mb-4 text-gray-300"/>
                    <p>No alerts found</p>
                </div>
            @endforelse
        </div>
    </div>

    <!-- Alert Detail Modal -->
    <x-modal wire:model="showAlertModal" title="Alert Details">
        @if($selectedAlert)
            <div class="space-y-4">
                <div class="flex items-center space-x-3">
                    <x-icon :name="$selectedAlert->type_icon"
                            class="w-6 h-6 text-{{ $selectedAlert->severity_color }}-500"/>
                    <div>
                        <h3 class="font-semibold text-lg">{{ $selectedAlert->title }}</h3>
                        <x-badge :value="ucfirst($selectedAlert->severity)"
                                 class="badge-{{ $selectedAlert->severity_color }}"/>
                    </div>
                </div>

                <div>
                    <h4 class="font-medium text-gray-900 mb-2">Description</h4>
                    <p class="text-gray-700">{{ $selectedAlert->description }}</p>
                </div>

                @if($selectedAlert->metadata)
                    <div>
                        <h4 class="font-medium text-gray-900 mb-2">Additional Information</h4>
                        <div class="bg-gray-50 p-3 rounded text-sm">
                            <pre>{{ json_encode($selectedAlert->metadata, JSON_PRETTY_PRINT) }}</pre>
                        </div>
                    </div>
                @endif

                @if($selectedAlert->is_resolved)
                    <div class="bg-green-50 p-4 rounded-lg">
                        <div class="flex items-center mb-2">
                            <x-icon name="fas.check-circle" class="w-5 h-5 text-green-500 mr-2"/>
                            <span class="font-medium text-green-800">Resolved</span>
                        </div>
                        <p class="text-sm text-green-700">
                            Resolved by {{ $selectedAlert->resolvedBy->name ?? 'Unknown' }}
                            on {{ $selectedAlert->resolved_at->format('M j, Y \a\t g:i A') }}
                        </p>
                        @if($selectedAlert->resolution_notes)
                            <p class="text-sm text-green-700 mt-2">
                                <strong>Notes:</strong> {{ $selectedAlert->resolution_notes }}
                            </p>
                        @endif
                    </div>
                @else
                    <div class="space-y-4">
                        <x-textarea
                            wire:model="resolutionNotes"
                            label="Resolution Notes (Optional)"
                            placeholder="Add notes about how this alert was resolved..."
                            rows="3"
                        />

                        <div class="flex justify-end space-x-3">
                            <x-button wire:click="closeAlertModal" class="btn-secondary">
                                Cancel
                            </x-button>
                            <x-button wire:click="resolveAlert" class="btn-success">
                                <x-icon name="fas.check" class="w-4 h-4 mr-2"/>
                                Mark as Resolved
                            </x-button>
                        </div>
                    </div>
                @endif
            </div>
        @endif
    </x-modal>

    <!-- Notification Preferences -->
    <x-card>
        <h3 class="text-lg font-semibold mb-4 flex items-center gap-2">
            <x-icon name="fas.bell" class="w-5 h-5 text-purple-500"/>
            Notification Preferences
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="space-y-4">
                <x-checkbox
                    wire:model="emailNotifications"
                    label="Email Notifications"
                    hint="Receive health alerts via email"
                />
                <x-checkbox
                    wire:model="criticalAlerts"
                    label="Critical Alerts"
                    hint="Immediate notifications for critical issues"
                />
            </div>
            <div class="space-y-4">
                <x-checkbox
                    wire:model="dailyDigest"
                    label="Daily Health Digest"
                    hint="Daily summary of project health"
                />
                <x-checkbox
                    wire:model="weeklyReport"
                    label="Weekly Health Report"
                    hint="Comprehensive weekly health analysis"
                />
            </div>
        </div>

        <div class="mt-4 pt-4 border-t">
            <x-button wire:click="updateNotificationPreferences" icon="fas.save" class="btn-primary">
                Save Preferences
            </x-button>
        </div>
    </x-card>
</div>
