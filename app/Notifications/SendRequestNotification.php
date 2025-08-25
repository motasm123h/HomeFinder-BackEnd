<?php

namespace App\Notifications;

use App\Models\RealEstate_Request;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SendRequestNotification extends Notification
{
    use Queueable;

    public $realEstateRequest;

    /**
     * Create a new notification instance.
     */
    public function __construct(RealEstate_Request $realEstateRequest)
    {
        $this->realEstateRequest = $realEstateRequest;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation for database storage.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'request_id' => $this->realEstateRequest->id,
            'message' => 'Your have new request received',
            'link' => '/requests/'.$this->realEstateRequest->id,
        ];
    }
}
