<?php

namespace App\Notifications;

use App\Models\Meeting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MeetingReminder extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * The meeting instance.
     *
     * @var \App\Models\Meeting
     */
    protected $meeting;

    /**
     * Create a new notification instance.
     */
    public function __construct(Meeting $meeting)
    {
        $this->meeting = $meeting;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $meetingTime = $this->meeting->scheduled_at->format('F j, Y \a\t g:i A');
        $meetingDuration = $this->meeting->duration;
        $meetingUrl = url("/meetings/{$this->meeting->id}");
        
        return (new MailMessage)
            ->subject("Reminder: {$this->meeting->title} starts soon")
            ->greeting("Hello {$notifiable->name}!")
            ->line("This is a reminder that you have an upcoming meeting: **{$this->meeting->title}**")
            ->line("**Time:** {$meetingTime}")
            ->line("**Duration:** {$meetingDuration} minutes")
            ->line("**Project:** {$this->meeting->project->name}")
            ->when($this->meeting->meeting_link, function ($message) {
                return $message->action('Join Meeting', $this->meeting->meeting_link);
            }, function ($message) use ($meetingUrl) {
                return $message->action('View Meeting Details', $meetingUrl);
            })
            ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'meeting_id' => $this->meeting->id,
            'title' => $this->meeting->title,
            'scheduled_at' => $this->meeting->scheduled_at->toIso8601String(),
            'project_id' => $this->meeting->project_id,
            'project_name' => $this->meeting->project->name,
            'meeting_type' => $this->meeting->meeting_type,
            'meeting_link' => $this->meeting->meeting_link,
        ];
    }
}
