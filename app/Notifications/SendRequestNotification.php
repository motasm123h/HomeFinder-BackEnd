<?php

namespace App\Notifications;

use App\Models\RealEstate_Request; 
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
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
        return ['mail', 'database']; // Send via both email and save to database
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New Real Estate Request Submitted')
            ->line('Your real estate request has been received by our office.')
            ->line('Request Details:')
            ->line('Request ID: ' . $this->realEstateRequest->id)
            ->line('Submitted at: ' . $this->realEstateRequest->created_at)
            ->action('View Request', url('/requests/' . $this->realEstateRequest->id))
            ->line('We will process your request shortly.');
    }

    /**
     * Get the array representation for database storage.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'request_id' => $this->realEstateRequest->id,
            'message' => 'Your have new request received',
            'link' => '/requests/' . $this->realEstateRequest->id
        ];
    }
}