<?php

namespace App\Mail;

use App\Models\Task;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TaskStatusUpdatedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Task $task,
        public User $updater
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Task Status Updated: {$this->task->task_code}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.tasks.status-updated',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
