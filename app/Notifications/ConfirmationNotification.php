<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ConfirmationNotification extends Notification
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

        if ($this->workOrder->schedule_type == 'Arrive at a specific date and time - (Hard Start)') {
            $schedule_date = $this->workOrder->schedule_date;
        }
        if ($this->workOrder->schedule_type == 'Complete work between specific hours') {
            $schedule_date = $this->workOrder->schedule_date_between_1;
        }
        if ($this->workOrder->schedule_type == 'Complete work anytime over a date range') {
            $schedule_date = $this->workOrder->between_date;
        }
        return (new MailMessage)
            ->subject('Work Order Confirmation Reminder')
            ->line('You need to confirm the work order: ' . $this->workOrder->work_order_unique_id)
            ->line('Scheduled Date: ' . $schedule_date)
            // ->action('Confirm Now', url('/work-orders/' . $this->workOrder->id))
            ->line('Please confirm before 12:00 PM tomorrow.');
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
            'message' => 'Confirmation reminder for work order: ' . $this->workOrder->work_order_unique_id,
        ];
    }
}
