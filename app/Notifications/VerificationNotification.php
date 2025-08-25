<?php

namespace App\Notifications;

use App\Models\Verification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

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
            'link' => '/profile',
        ];
    }
}
