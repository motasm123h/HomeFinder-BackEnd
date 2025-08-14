<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Verification;

class VerificationNotification extends Notification
{
    use Queueable;

    public $verification;

    public function __construct(Verification $verification)
    {
        $this->verification = $verification;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toArray($notifiable)
    {
        return [
            'message' => 'Your account has been verified successfully',
            'verification_id' => $this->verification->id,
            'link' => '/profile'
        ];
    }
}
