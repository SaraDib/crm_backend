<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;

class CrmNotification extends Notification
{
    use Queueable;

    protected $title;
    protected $message;
    protected $actionUrl;
    protected $type;

    /**
     * Create a new notification instance.
     */
    public function __construct($title, $message, $actionUrl = '', $type = 'info')
    {
        $this->title = $title;
        $this->message = $message;
        $this->actionUrl = $actionUrl;
        $this->type = $type;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
            'action_url' => $this->actionUrl,
            'type' => $this->type,
            'created_at' => now()->toISOString(),
        ];
    }
}
