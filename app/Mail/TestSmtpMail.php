<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TestSmtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User|string $recipient,
        public string $timestamp
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'SMTP Connection Test - ' . config('app.name', 'HRM System'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.system.test-smtp',
            with: [
                'recipient' => is_string($this->recipient) ? $this->recipient : ($this->recipient->name ?? $this->recipient->email),
                'timestamp' => $this->timestamp,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
