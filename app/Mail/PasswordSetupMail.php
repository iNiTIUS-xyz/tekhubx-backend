<?php

namespace App\Mail;

use App\Models\EmployeeProvider;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PasswordSetupMail extends Mailable
{
    use Queueable, SerializesModels;

    public $employeeProviders;
    public $token;

    /**
     * Create a new message instance.
     */
    public function __construct(EmployeeProvider $employeeProviders, $token)
    {
        $this->employeeProviders = $employeeProviders;
        $this->token = $token;
    }

    public function build()
    {
        $setupPasswordUrl = env('FRONTEND_URL') . '/setup-password/' . $this->token;
        return $this->subject('Set New Password')
                    ->view('emails.password_setup')
                    ->with([
                        'employeeProviders' => $this->employeeProviders,
                        'setupPasswordUrl' => $setupPasswordUrl,
                    ]);
    }
}
