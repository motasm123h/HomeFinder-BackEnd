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
        return ['database', 'mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
                    ->subject('Your Account Has Been Verified')
                    ->line('Congratulations! Your account verification has been completed successfully.')
                    ->line('Verification details:')
                    ->line('National Number: ' . $this->verification->national_no)
                    ->action('View Your Account', url('/profile'))
                    ->line('Thank you for using our service!');
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
