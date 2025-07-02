<?php

use App\Models\PomodoroSession;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

new class extends Component {
    public $todayStats = [
        'focus_time' => '0h 0m',
        'completed_pomodoros' => 0,
        'completion_rate' => 0,
    ];
    public $weeklyStats = [
        'focus_time' => '0h 0m',
        'completed_pomodoros' => 0,
        'completion_rate' => 0,
    ];
    public $streak = 0;
    public $bestStreak = 0;

    public function mount()
    {
        $this->loadStats();
        $this->loadStreak();
    }

    public function loadStats()
    {
        // Today's stats
        $today = now()->startOfDay();
        $todaySessions = PomodoroSession::where('user_id', Auth::id())
            ->where('workspace_id', session('workspace_id'))
            ->where('started_at', '>=', $today)
            ->get();
        $todayFocusMinutes = 0;
        $todayCompletedPomodoros = 0;
        $todayTargetPomodoros = 0;
        foreach ($todaySessions as $session) {
            $todayFocusMinutes += $session->logs()
                    ->where('type', 'work')
                    ->where('completed', true)
                    ->sum('duration') / 60;
            $todayCompletedPomodoros += $session->completed_pomodoros;
            $todayTargetPomodoros += $session->target_pomodoros;
        }
        $this->todayStats = [
            'focus_time' => floor($todayFocusMinutes / 60) . 'h ' . ($todayFocusMinutes % 60) . 'm',
            'completed_pomodoros' => $todayCompletedPomodoros,
            'completion_rate' => $todayTargetPomodoros > 0 ? min(100, round(($todayCompletedPomodoros / $todayTargetPomodoros) * 100)) : 0,
        ];
        // Weekly stats
        $weekStart = now()->startOfWeek();
        $weeklySessions = PomodoroSession::where('user_id', Auth::id())
            ->where('workspace_id', session('workspace_id'))
            ->where('started_at', '>=', $weekStart)
            ->get();
        $weeklyFocusMinutes = 0;
        $weeklyCompletedPomodoros = 0;
        $weeklyTargetPomodoros = 0;
        foreach ($weeklySessions as $session) {
            $weeklyFocusMinutes += $session->logs()
                    ->where('type', 'work')
                    ->where('completed', true)
                    ->sum('duration') / 60;
            $weeklyCompletedPomodoros += $session->completed_pomodoros;
            $weeklyTargetPomodoros += $session->target_pomodoros;
        }
        $this->weeklyStats = [
            'focus_time' => floor($weeklyFocusMinutes / 60) . 'h ' . ($weeklyFocusMinutes % 60) . 'm',
            'completed_pomodoros' => $weeklyCompletedPomodoros,
            'completion_rate' => $weeklyTargetPomodoros > 0 ? min(100, round(($weeklyCompletedPomodoros / $weeklyTargetPomodoros) * 100)) : 0,
        ];
    }

    public function loadStreak()
    {
        // Basit streak hesabı (geliştirilebilir)
        $this->streak = 0;
        $this->bestStreak = 0;
    }
};
?>

<div>
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Pomodoro Dashboard</h1>
        <x-button icon="fas.plus" href="{{ route('pomodoro.sessions.create') }}" wire:navigate>New Session</x-button>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <x-card>
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">Today's Focus</p>
                    <p class="text-3xl font-bold">{{ $todayStats['focus_time'] }}</p>
                </div>
                <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                    <i class="fas fa-clock text-xl"></i>
                </div>
            </div>
            <div class="mt-4">
                <p class="text-sm text-gray-500">{{ $todayStats['completed_pomodoros'] }} pomodoros completed</p>
                <div class="w-full bg-gray-200 rounded-full h-2 mt-1">
                    <div class="bg-blue-600 h-2 rounded-full"
                         style="width: {{ $todayStats['completion_rate'] }}%"></div>
                </div>
            </div>
        </x-card>
        <x-card>
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">Weekly Focus</p>
                    <p class="text-3xl font-bold">{{ $weeklyStats['focus_time'] }}</p>
                </div>
                <div class="p-3 rounded-full bg-green-100 text-green-600">
                    <i class="fas fa-calendar-week text-xl"></i>
                </div>
            </div>
            <div class="mt-4">
                <p class="text-sm text-gray-500">{{ $weeklyStats['completed_pomodoros'] }} pomodoros this week</p>
                <div class="w-full bg-gray-200 rounded-full h-2 mt-1">
                    <div class="bg-green-600 h-2 rounded-full"
                         style="width: {{ $weeklyStats['completion_rate'] }}%"></div>
                </div>
            </div>
        </x-card>
        <x-card>
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">Current Streak</p>
                    <p class="text-3xl font-bold">{{ $streak }} days</p>
                </div>
                <div class="p-3 rounded-full bg-orange-100 text-orange-600">
                    <i class="fas fa-fire text-xl"></i>
                </div>
            </div>
            <div class="mt-4">
                <p class="text-sm text-gray-500">Best: {{ $bestStreak }} days</p>
                <div class="w-full bg-gray-200 rounded-full h-2 mt-1">
                    <div class="bg-orange-600 h-2 rounded-full"
                         style="width: {{ $streak > 0 ? ($streak / max($bestStreak, 1)) * 100 : 0 }}%"></div>
                </div>
            </div>
        </x-card>
    </div>
    <div class="mb-8">
        <h2 class="text-lg font-semibold mb-4">Quick Actions</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <x-card class="hover:bg-gray-50 transition-colors cursor-pointer" onclick="window.location='{{ route('pomodoro.timer') }}'">
                <div class="flex flex-col items-center justify-center py-4">
                    <div class="p-3 rounded-full bg-red-100 text-red-600 mb-3">
                        <i class="fas fa-stopwatch text-xl"></i>
                    </div>
                    <h3 class="font-medium">Start Timer</h3>
                </div>
            </x-card>
            <x-card class="hover:bg-gray-50 transition-colors cursor-pointer"
                    onclick="window.location='{{ route('pomodoro.sessions.index') }}'">
                <div class="flex flex-col items-center justify-center py-4">
                    <div class="p-3 rounded-full bg-purple-100 text-purple-600 mb-3">
                        <i class="fas fa-history text-xl"></i>
                    </div>
                    <h3 class="font-medium">Session History</h3>
                </div>
            </x-card>
            <x-card class="hover:bg-gray-50 transition-colors cursor-pointer" onclick="window.location='{{ route('pomodoro.reports') }}'">
                <div class="flex flex-col items-center justify-center py-4">
                    <div class="p-3 rounded-full bg-blue-100 text-blue-600 mb-3">
                        <i class="fas fa-chart-bar text-xl"></i>
                    </div>
                    <h3 class="font-medium">Analytics</h3>
                </div>
            </x-card>
            <x-card class="hover:bg-gray-50 transition-colors cursor-pointer" onclick="window.location='{{ route('pomodoro.tags.index') }}'">
                <div class="flex flex-col items-center justify-center py-4">
                    <div class="p-3 rounded-full bg-green-100 text-green-600 mb-3">
                        <i class="fas fa-tags text-xl"></i>
                    </div>
                    <h3 class="font-medium">Manage Tags</h3>
                </div>
            </x-card>
        </div>
    </div>
    <!-- Burada son oturumlar, günlük aktivite gibi bölümler eklenebilir -->
</div>
