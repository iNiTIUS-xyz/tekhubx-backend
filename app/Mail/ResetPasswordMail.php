<?php

namespace App\Mail;

use App\Models\EmployeeProvider;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ResetPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    public $email;
    public $token;

    /**
     * Create a new message instance.
     */
    public function __construct($email, $token)
    {
        $this->email = $email;
        $this->token = $token;
    }

    public function build()
    {
        // Use FRONTEND_URL from .env, fallback to localhost:3000
        $frontendUrl = env('FRONTEND_URL', 'http://localhost:3000');

        // Build full reset link
        $resetUrl = $frontendUrl . 'reset-password?token=' . $this->token . '&email=' . urlencode($this->email);

        // $resetUrl = env('FRONTEND_URL') . '/reset-password?token=' . $this->token;
        return $this->subject('Reset Your Password')
            ->view('emails.reset_password')
            ->with([
                'resetUrl' => $resetUrl,
                'email' => $this->email,
            ]);
    }
}
