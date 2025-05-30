<?php

namespace App\Console\Commands;

use App\Models\Meeting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use App\Notifications\MeetingReminder;

class SendMeetingReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'meetings:send-reminders {--minutes=15 : Minutes before meeting to send reminder}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send reminders for upcoming meetings';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $minutes = $this->option('minutes');
        $this->info("Sending reminders for meetings starting in {$minutes} minutes...");
        
        $now = Carbon::now();
        $reminderTime = $now->copy()->addMinutes($minutes);
        
        // Find meetings that start in the specified time window
        $meetings = Meeting::where('status', 'scheduled')
            ->whereBetween('scheduled_at', [
                $reminderTime->copy()->subMinutes(1),
                $reminderTime->copy()->addMinutes(1)
            ])
            ->with(['project', 'users'])
            ->get();
        
        $this->info("Found {$meetings->count()} meetings to send reminders for.");
        
        foreach ($meetings as $meeting) {
            $this->sendReminders($meeting);
        }
        
        $this->info('Meeting reminders sent successfully.');
    }
    
    /**
     * Send reminders to all attendees of a meeting.
     */
    private function sendReminders(Meeting $meeting)
    {
        $this->info("Sending reminders for meeting: {$meeting->title}");
        
        // Get all attendees
        $attendees = $meeting->users;
        
        if ($attendees->isEmpty()) {
            $this->warn("No attendees found for meeting: {$meeting->title}");
            return;
        }
        
        // Send notifications to all attendees
        Notification::send($attendees, new MeetingReminder($meeting));
        
        $this->info("Sent reminders to {$attendees->count()} attendees for meeting: {$meeting->title}");
    }
}
