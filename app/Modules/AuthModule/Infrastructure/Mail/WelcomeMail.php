<?php

namespace App\Modules\AuthModule\Infrastructure\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class WelcomeMail extends Mailable
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly string $temporaryPassword,
        public readonly string $loginUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your account has been created',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'authmodule::emails.welcome',
        );
    }
}
