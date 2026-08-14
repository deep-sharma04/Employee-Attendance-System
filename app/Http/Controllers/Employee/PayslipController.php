<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Payslip;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class PayslipController extends Controller
{
    /**
     * T134: Build "My Payslips" Page
     */
    public function index(Request $request): View
    {
        $employee = Auth::user()->employee;

        $payslips = Payslip::where('employee_id', $employee->id)
            ->latest('year')
            ->latest('month')
            ->paginate(10);

        return view('employee.payslips.index', compact('payslips'));
    }

    /**
     * T134 & T136: View own finalized payslip PDF in browser
     */
    public function view(int $id)
    {
        $payslip = Payslip::findOrFail($id);

        // T136: Enforce Own-Data Access Guard
        if ($payslip->employee_id !== Auth::user()->employee->id) {
            abort(403, 'You are not authorized to view this payslip.');
        }

        if (!Storage::disk('local')->exists($payslip->file_path)) {
            abort(404, 'Payslip file not found.');
        }

        return Storage::disk('local')->response($payslip->file_path);
    }

    /**
     * T134 & T136: Download own finalized payslip PDF
     */
    public function download(int $id)
    {
        $payslip = Payslip::findOrFail($id);

        // T136: Enforce Own-Data Access Guard
        if ($payslip->employee_id !== Auth::user()->employee->id) {
            abort(403, 'You are not authorized to download this payslip.');
        }

        if (!Storage::disk('local')->exists($payslip->file_path)) {
            abort(404, 'Payslip file not found.');
        }

        return Storage::disk('local')->download($payslip->file_path, $payslip->payslip_number . '.pdf');
    }
}