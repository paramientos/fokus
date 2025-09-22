<?php

namespace App\Notifications;

use App\Models\Workspace;
use App\Models\WorkspaceInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WorkspaceInvitationNotification extends Notification
{
    use Queueable;

    public WorkspaceInvitation $invitation;

    public Workspace $workspace;

    /**
     * Create a new notification instance.
     */
    public function __construct(WorkspaceInvitation $invitation, Workspace $workspace)
    {
        $this->invitation = $invitation;
        $this->workspace = $workspace;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('You\'ve been invited to join '.$this->workspace->name)
            ->greeting('Hello!')
            ->line('You have been invited to join the workspace "'.$this->workspace->name.'".')
            ->line('Role: '.ucfirst($this->invitation->role))
            ->line('Invited by: '.$this->invitation->invitedBy->name)
            ->action('Accept Invitation', route('workspaces.invitation.accept', $this->invitation->token))
            ->line('This invitation will expire on '.$this->invitation->expires_at->format('M d, Y'))
            ->line('If you did not expect to receive this invitation, no further action is required.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'workspace_id' => $this->workspace->id,
            'workspace_name' => $this->workspace->name,
            'invitation_id' => $this->invitation->id,
            'role' => $this->invitation->role,
            'invited_by' => $this->invitation->invitedBy->name,
        ];
    }
}
