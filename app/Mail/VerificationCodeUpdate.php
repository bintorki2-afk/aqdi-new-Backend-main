<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VerificationCodeUpdate extends Mailable
{
    use Queueable, SerializesModels;

    public $verification_code;

    public function __construct($verification_code)
    {
        $this->verification_code = $verification_code;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'كود التحقق الخاص بك ',
        );
    }

    public function content(): Content
    {
        $code = e((string) $this->verification_code);
        $app = e((string) config('app.name'));

        return new Content(
            htmlString: "<p dir=\"rtl\">كود التحقق الخاص بك: <strong>{$code}</strong></p><p>{$app}</p>",
        );
    }
}
