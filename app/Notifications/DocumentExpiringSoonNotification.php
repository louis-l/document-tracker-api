<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

/**
 * This notification is sent to user to inform them about all the expiring soon documents.
 */
class DocumentExpiringSoonNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        protected Collection $documents,
    ) {}

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
    public function toMail(User $notifiable): MailMessage
    {
        $mailMessage = (new MailMessage)
            ->subject('You have documents expiring soon')
            ->greeting('Hi '.$notifiable->name)
            ->line('The following documents will expire within the next 7 days:');

        foreach ($this->documents as $document) {
            $mailMessage->line(" - **$document->name** (expires on {$document->expires_at->format('Y-m-d')})");
        }

        $mailMessage->line('Please take action before they expire.');

        // TODO: Include a link to the app to show these expiring soon documents

        return $mailMessage;
    }
}
