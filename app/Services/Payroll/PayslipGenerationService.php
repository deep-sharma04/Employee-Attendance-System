<?php

namespace App\Services\Payroll;

use App\Enums\PayrollStatus;
use App\Models\Payroll;
use App\Models\Payslip;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PayslipGenerationService
{
    /**
     * Generate and save a payslip for a finalized payroll.
     */
    public function generateForPayroll(Payroll $payroll): ?Payslip
    {
        // T136: Restrict Payslip Visibility Until Finalized
        if ($payroll->status !== PayrollStatus::FINALIZED) {
            return null;
        }

        // T133: Link Payslip to Payroll Record (Return existing if already generated)
        if ($payroll->payslip) {
            return $payroll->payslip;
        }

        $payslipNumber = 'PSL-' . $payroll->payroll_year . str_pad($payroll->payroll_month, 2, '0', STR_PAD_LEFT) . '-' . $payroll->employee->employee_code;
        
        $directory = 'payslips/' . $payroll->payroll_year . '/' . str_pad($payroll->payroll_month, 2, '0', STR_PAD_LEFT);
        $fileName = $payslipNumber . '.pdf';
        $filePath = $directory . '/' . $fileName;

        // T132: Implement PDF Payslip Export
        $pdf = Pdf::loadView('payslip.template', compact('payroll'));
        $pdf->setPaper('A4', 'portrait');
        
        // Save securely outside public webroot
        Storage::disk('local')->put($filePath, $pdf->output());

        // T133: Create database record
        return Payslip::create([
            'payroll_id' => $payroll->id,
            'employee_id' => $payroll->employee_id,
            'payslip_number' => $payslipNumber,
            'month' => $payroll->payroll_month,
            'year' => $payroll->payroll_year,
            'net_pay' => $payroll->net_salary,
            'file_path' => $filePath,
            'generated_at' => now(),
        ]);
    }

    /**
     * Delete physical file (used if payroll is revised later).
     */
    public function deletePayslipFile(Payslip $payslip): void
    {
        if (Storage::disk('local')->exists($payslip->file_path)) {
            Storage::disk('local')->delete($payslip->file_path);
        }
        $payslip->delete();
    }
}