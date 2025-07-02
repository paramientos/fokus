<?php
use App\Models\PomodoroSession;
use App\Models\PomodoroLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;

new class extends Livewire\Volt\Component {
    public $dailyTotal = 0;
    public $weeklyTotal = 0;
    public $monthlyTotal = 0;
    public $dailyMinutes = 0;
    public $topTags = [];
    public $recentSessions = [];

    public function mount()
    {
        $workspaceId = session('workspace_id');
        $userId = Auth::id();
        $today = Carbon::today();
        $weekStart = Carbon::now()->startOfWeek();
        $monthStart = Carbon::now()->startOfMonth();

        $this->dailyTotal = PomodoroLog::whereHas('session', fn($q) => $q->where('user_id',$userId)->where('workspace_id',$workspaceId))
            ->where('type','work')
            ->whereDate('started_at',$today)
            ->count();
        $this->weeklyTotal = PomodoroLog::whereHas('session', fn($q) => $q->where('user_id',$userId)->where('workspace_id',$workspaceId))
            ->where('type','work')
            ->whereBetween('started_at',[$weekStart,now()])
            ->count();
        $this->monthlyTotal = PomodoroLog::whereHas('session', fn($q) => $q->where('user_id',$userId)->where('workspace_id',$workspaceId))
            ->where('type','work')
            ->whereBetween('started_at',[$monthStart,now()])
            ->count();
        $this->dailyMinutes = PomodoroLog::whereHas('session', fn($q) => $q->where('user_id',$userId)->where('workspace_id',$workspaceId))
            ->where('type','work')
            ->whereDate('started_at',$today)
            ->sum('duration') / 60;
        $this->topTags = \DB::table('pomodoro_tags')
            ->join('pomodoro_session_tag','pomodoro_tags.id','=','pomodoro_session_tag.pomodoro_tag_id')
            ->join('pomodoro_sessions','pomodoro_sessions.id','=','pomodoro_session_tag.pomodoro_session_id')
            ->where('pomodoro_sessions.user_id',$userId)
            ->where('pomodoro_sessions.workspace_id',$workspaceId)
            ->select('pomodoro_tags.name',\DB::raw('count(*) as total'))
            ->groupBy('pomodoro_tags.id','pomodoro_tags.name')
            ->orderByDesc('total')
            ->limit(5)
            ->get();
        $this->recentSessions = PomodoroSession::where('user_id',$userId)
            ->where('workspace_id',$workspaceId)
            ->orderByDesc('completed_at')
            ->limit(5)
            ->get();
    }
};
?>
<div>
    <h1 class="text-2xl font-bold mb-6">Pomodoro Reports</h1>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <x-card class="p-4 flex flex-col items-center">
            <x-icon name="fas.calendar-day" class="text-blue-500 text-2xl mb-2" />
            <div class="text-lg font-semibold">Today</div>
            <div class="text-3xl">{{ $dailyTotal }}</div>
            <div class="text-xs text-gray-500">Pomodoros</div>
            <div class="mt-2 text-xs text-gray-500">{{ number_format($dailyMinutes) }} min focused</div>
        </x-card>
        <x-card class="p-4 flex flex-col items-center">
            <x-icon name="fas.calendar-week" class="text-green-500 text-2xl mb-2" />
            <div class="text-lg font-semibold">This Week</div>
            <div class="text-3xl">{{ $weeklyTotal }}</div>
            <div class="text-xs text-gray-500">Pomodoros</div>
        </x-card>
        <x-card class="p-4 flex flex-col items-center">
            <x-icon name="fas.calendar-alt" class="text-pink-500 text-2xl mb-2" />
            <div class="text-lg font-semibold">This Month</div>
            <div class="text-3xl">{{ $monthlyTotal }}</div>
            <div class="text-xs text-gray-500">Pomodoros</div>
        </x-card>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <x-card class="p-4">
            <div class="font-semibold mb-2">Top Tags</div>
            <ul>
                @forelse($topTags as $tag)
                    <li class="flex items-center gap-2 mb-1">
                        <x-icon name="fas.tag" class="text-blue-400" />
                        <span>{{ $tag->name }}</span>
                        <span class="ml-auto bg-blue-100 text-blue-700 rounded px-2 py-0.5">{{ $tag->total }}</span>
                    </li>
                @empty
                    <li class="text-gray-400">No tags</li>
                @endforelse
            </ul>
        </x-card>
        <x-card class="p-4">
            <div class="font-semibold mb-2">Recent Sessions</div>
            <ul>
                @forelse($recentSessions as $session)
                    <li class="mb-2 flex items-center gap-2">
                        <x-icon name="fas.clock" class="text-green-400" />
                        <span>{{ $session->title }}</span>
                        <span class="ml-auto text-xs text-gray-500">{{ $session->completed_at?->format('d M H:i') }}</span>
                    </li>
                @empty
                    <li class="text-gray-400">No sessions</li>
                @endforelse
            </ul>
        </x-card>
    </div>
</div>
