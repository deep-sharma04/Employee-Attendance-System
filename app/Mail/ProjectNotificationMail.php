<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ProjectNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $recipient,
        public string $title,
        public string $bodyMessage,
        public ?string $actionUrl = null,
        public string $category = 'general',
        public array $data = []
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "{$this->title} - " . config('app.name', 'HRM System'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.project_notification',
            with: [
                'recipient' => $this->recipient,
                'title' => $this->title,
                'bodyMessage' => $this->bodyMessage,
                'actionUrl' => $this->actionUrl,
                'category' => $this->category,
                'data' => $this->data,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
