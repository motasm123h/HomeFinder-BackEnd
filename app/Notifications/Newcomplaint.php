<?php

namespace App\Notifications;

use App\Models\Reviews;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification; // Assuming your model is named Reviews

class Newcomplaint extends Notification
{
    use Queueable;

    protected $review;

    public function __construct(Reviews $review)
    {
        $this->review = $review;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            'review_id' => $this->review->id,
            'review_name' => $this->review->name,
            'message' => 'A new complaint has been submitted.',
        ];
    }
}
