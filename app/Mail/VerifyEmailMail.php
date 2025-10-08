<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class VerifyEmailMail extends Mailable
{
    use Queueable, SerializesModels;

    public $token;
    /**
     * Create a new message instance.
     */
    public function __construct($token)
    {
        $this->token = $token;
    }


    public function build()
    {
        return $this->subject('Verify Your Email Address')
            ->view('emails.verify_email')
            ->with([
                'verificationUrl' => url("/verify-email/{$this->token}"),
            ]);
    }
}
