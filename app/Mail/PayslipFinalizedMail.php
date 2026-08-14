<?php

namespace App\Mail;

use App\Models\Employee;
use App\Models\Payroll;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class PayslipFinalizedMail extends Mailable
{
    use Queueable, SerializesModels;

    public Employee $employee;
    public string $monthName;

    public function __construct(
        public Payroll $payroll
    ) {
        $this->employee = $payroll->employee;
        $this->monthName = date('F Y', mktime(0, 0, 0, (int) $payroll->payroll_month, 1, (int) $payroll->payroll_year));
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Your Payslip for {$this->monthName} is Ready - " . config('app.name', 'HRM System'),
        );
    }

    public function content(): Content
    {
        $hasAttachment = $this->payroll->payslip && Storage::disk('local')->exists($this->payroll->payslip->file_path);

        return new Content(
            view: 'emails.payroll.payslip-finalized',
            with: [
                'payroll' => $this->payroll,
                'employee' => $this->employee,
                'monthName' => $this->monthName,
                'hasAttachment' => $hasAttachment,
                'actionUrl' => url('/employee/payslips'),
            ],
        );
    }

    public function attachments(): array
    {
        $attachments = [];

        if ($this->payroll->payslip && Storage::disk('local')->exists($this->payroll->payslip->file_path)) {
            $fileName = ($this->payroll->payslip->payslip_number ?: 'Payslip') . '.pdf';
            $attachments[] = Attachment::fromStorageDisk('local', $this->payroll->payslip->file_path)
                ->as($fileName)
                ->withMime('application/pdf');
        }

        return $attachments;
    }
}
