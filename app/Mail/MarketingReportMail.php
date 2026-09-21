<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MarketingReportMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array{filename: string, bytes: string, mime: string}  $file
     */
    public function __construct(
        public array $file,
        public string $periodLabel,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'تقرير التسويق — '.$this->periodLabel,
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: '<p dir="rtl">مرفق تقرير التسويق للفترة: '.e($this->periodLabel).'.</p>',
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [
            Attachment::fromData(fn () => $this->file['bytes'], $this->file['filename'])
                ->withMime($this->file['mime']),
        ];
    }
}
