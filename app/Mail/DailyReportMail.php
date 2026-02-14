<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DailyReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public array $report
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Rapport quotidien NutriSport – ' . $this->report['date'],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.daily-report',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
