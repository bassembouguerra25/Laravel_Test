<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Booking Confirmed Notification
 *
 * Notification sent to customer when their booking is confirmed.
 * This notification is queued to be sent asynchronously.
 */
class BookingConfirmedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * The booking instance.
     */
    public Booking $booking;

    /**
     * Create a new notification instance.
     */
    public function __construct(Booking $booking)
    {
        $this->booking = $booking;
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
        $event = $this->booking->ticket->event;
        $totalAmount = $this->booking->total_amount;

        return (new MailMessage)
            ->subject('Booking Confirmed: '.$event->title)
            ->greeting('Hello '.$notifiable->name.'!')
            ->line('Your booking has been confirmed successfully.')
            ->line('**Event Details:**')
            ->line('- **Event:** '.$event->title)
            ->line('- **Date:** '.$event->date->format('F j, Y \a\t g:i A'))
            ->line('- **Location:** '.$event->location)
            ->line('**Booking Details:**')
            ->line('- **Ticket Type:** '.$this->booking->ticket->type)
            ->line('- **Quantity:** '.$this->booking->quantity)
            ->line('- **Total Amount:** $'.number_format($totalAmount, 2))
            ->line('Thank you for booking with us! We look forward to seeing you at the event.')
            ->action('View Booking Details', url('/bookings/'.$this->booking->id));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'booking_id' => $this->booking->id,
            'event_title' => $this->booking->ticket->event->title,
            'total_amount' => $this->booking->total_amount,
        ];
    }
}
