<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AtRiskNotification extends Notification
{
    use Queueable;

    private $workOrder;

    public function __construct($workOrder)
    {
        $this->workOrder = $workOrder;
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
            ->subject('Work Order At-Risk Alert')
            ->line('The work order: ' . $this->workOrder->work_order_unique_id . ' is now at-risk.')
            ->line('Please review and take necessary action.');
            // ->action('View Work Order', url('/work-orders/' . $this->workOrder->id));
   
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'work_order_unique_id' => $this->workOrder->work_order_unique_id,
            'message' => 'The work order: ' . $this->workOrder->work_order_unique_id . ' is now at-risk.',
        ];
    }
}
