<?php

namespace App\Mail;

use App\Models\Employee;
use App\Models\LeaveRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LeaveStatusMail extends Mailable
{
    use Queueable, SerializesModels;

    public Employee $employee;

    public function __construct(
        public LeaveRequest $leave,
        public string $status,
        public ?string $reason = null
    ) {
        $this->employee = $leave->employee;
    }

    public function envelope(): Envelope
    {
        $statusLabel = ucfirst($this->status);
        $leaveName = $this->leave->leaveType?->name ?? 'Leave';

        return new Envelope(
            subject: "Leave Request {$statusLabel} ({$leaveName}) - " . config('app.name', 'HRM System'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.leave.status',
            with: [
                'leave' => $this->leave,
                'employee' => $this->employee,
                'status' => $this->status,
                'reason' => $this->reason,
                'actionUrl' => url('/employee/leaves'),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
