<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ForgotPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $resetUrl,
        public int $expireMinutes = 15
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Password Reset Request - ' . config('app.name', 'HRM System'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.auth.forgot-password',
            with: [
                'user' => $this->user,
                'resetUrl' => $this->resetUrl,
                'expireMinutes' => $this->expireMinutes,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
