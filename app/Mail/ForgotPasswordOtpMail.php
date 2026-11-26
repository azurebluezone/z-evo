<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Carries the forgot-password one-time code to the customer's email address.
 *
 * The OTP is a public property on purpose: the feature test reads it back to
 * prove the code the customer receives is the code the reset form accepts.
 */
class ForgotPasswordOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $customerName,
        public string $otp,
        public string $siteName
    ) {
    }

    public function build()
    {
        return $this->subject('Your ' . $this->siteName . ' password reset code')
            ->view('emails.forgot_password_otp');
    }
}
