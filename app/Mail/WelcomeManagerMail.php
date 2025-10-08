<?php

namespace App\Mail;

use App\Models\ClientManager;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Contracts\Queue\ShouldQueue;

class WelcomeManagerMail extends Mailable
{
    use Queueable, SerializesModels;

    public $clientManager;
    public $token;

    /**
     * Create a new message instance.
     */
    public function __construct(ClientManager $clientManager, $token)
    {
        $this->clientManager = $clientManager;
        $this->token = $token;
    }

    public function build()
    {
        // Replace with the actual frontend URL for setting up the password
        $setupPasswordUrl = env('FRONTEND_URL') . '/setup-password/' . $this->token;

        return $this->subject('Welcome to the Management Team')
                    ->view('emails.welcome_manager')
                    ->with([
                        'clientManager' => $this->clientManager,
                        'setupPasswordUrl' => $setupPasswordUrl,
                    ]);
    }
}
