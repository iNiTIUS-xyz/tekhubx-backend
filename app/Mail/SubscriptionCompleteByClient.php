<?php

namespace App\Mail;

use App\Models\Payment;
use App\Models\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SubscriptionCompleteByClient extends Mailable
{
    use Queueable, SerializesModels;

    public $transaction;
    public $status;
    public $tag;
    /**
     * Create a new message instance.
     */
    public function __construct(Payment $transaction, string $status, string $tag)
    {
        $this->transaction = $transaction;
        $this->status = $status;
        $this->tag = $tag;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Subscription Complete By Client',
        );
    }

    public function build()
    {

        if($this->tag == 'Subscription')
        {
            return $this->subject('Thank You For Your Subscription')
                        ->view('emails.client_subscription')
                        ->with([
                            'payment' => $this->transaction,
                            'status' => $this->status,
                        ]);
        }

        if($this->tag == 'Payment')
        {
            return $this->subject('Thank You For Your Payment')
                        ->view('emails.client_payment')
                        ->with([
                            'payment' => $this->transaction,
                            'status' => $this->status,
                        ]);
        }
    }
}
