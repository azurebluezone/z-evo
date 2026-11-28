<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * The A-to-Z report. The same Blade view renders the printable page, so the
 * email and the browser preview are literally the same template.
 */
class DailyReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public array $summary,
        public string $label
    ) {
    }

    public function build()
    {
        return $this->subject('Daily report '.$this->label)
            ->view('backEnd.reports.email.daily-summary');
    }
}
