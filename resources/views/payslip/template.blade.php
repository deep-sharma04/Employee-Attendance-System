@php
    // Map PayrollItems to the PRD required structure, defaulting to 0.00
    $earnings = $payroll->items->where('type', 'earning')->keyBy('label');
    $deductions = $payroll->items->where('type', 'deduction')->keyBy('label');

    $earningLabels = ['Basic', 'HRA', 'Telephone Reimbursements', 'Bonus', 'LTA', 'Special Allowance'];
    $deductionLabels = ['Income Tax', 'Provident Fund', 'Professional Tax', 'LOP Deduction'];

    $companyName = \App\Models\CompanySetting::where('key', 'company_name')->value('value') ?? config('app.name', 'Company Name');
    $companyAddress = \App\Models\CompanySetting::where('key', 'company_address')->value('value') ?? 'Company Address';
    $companyLogo = \App\Models\CompanySetting::where('key', 'company_logo')->value('value') ?? null;
    
    $monthName = \Carbon\Carbon::createFromDate($payroll->payroll_year, $payroll->payroll_month, 1)->format('F Y');
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payslip - {{ $payroll->employee->full_name }} - {{ $monthName }}</title>
    <style>
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 12px; color: #333; margin: 0; padding: 20px; background-color: #f9fafb; }
        .payslip-container { max-width: 800px; margin: 0 auto; background: #fff; padding: 40px; border: 1px solid #ddd; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 3px solid #1f2937; padding-bottom: 20px; margin-bottom: 20px; }
        .company-info h2 { margin: 0; font-size: 20px; color: #1f2937; text-transform: uppercase; }
        .company-info p { margin: 4px 0 0 0; color: #6b7280; font-size: 11px; }
        .payslip-title { text-align: right; }
        .payslip-title h3 { margin: 0; font-size: 24px; color: #1f2937; text-transform: uppercase; }
        .payslip-title span { display: block; font-size: 13px; color: #4b5563; margin-top: 5px; font-weight: bold; }
        
        .employee-details { display: flex; justify-content: space-between; margin-bottom: 30px; background: #f3f4f6; padding: 15px; border-radius: 6px; }
        .employee-details div { width: 48%; }
        .employee-details p { margin: 4px 0; }
        .employee-details strong { display: inline-block; width: 130px; color: #6b7280; font-weight: 600; }
        
        .tables-section { display: flex; justify-content: space-between; gap: 20px; margin-bottom: 30px; }
        .table-box { width: 48%; border: 1px solid #e5e7eb; border-radius: 6px; overflow: hidden; }
        .table-box h4 { background: #1f2937; color: #fff; margin: 0; padding: 10px 15px; font-size: 13px; text-transform: uppercase; }
        table { width: 100%; border-collapse: collapse; }
        table th, table td { padding: 8px 15px; text-align: left; border-bottom: 1px solid #e5e7eb; }
        table td:last-child { text-align: right; font-weight: 500; }
        .total-row td { border-top: 2px solid #1f2937; border-bottom: none; font-weight: bold; font-size: 13px; background: #f9fafb; }
        
        .net-pay-container { display: flex; justify-content: flex-end; margin-bottom: 30px; }
        .net-pay-box { width: 48%; background: #ecfdf5; border: 1px solid #10b981; border-radius: 6px; padding: 15px; display: flex; justify-content: space-between; align-items: center; }
        .net-pay-box span { font-size: 16px; font-weight: bold; color: #065f46; text-transform: uppercase; }
        .net-pay-box strong { font-size: 20px; color: #065f46; }
        
        .footer { margin-top: 40px; text-align: center; border-top: 1px dashed #d1d5db; padding-top: 15px; }
        .footer p { margin: 0; font-size: 10px; color: #9ca3af; }
    </style>
</head>
<body>
    <div class="payslip-container">
        <!-- Header -->
        <div class="header">
            <div class="company-info">
                @if($companyLogo)
                    <img src="{{ $companyLogo }}" alt="Company Logo" style="height: 50px; margin-bottom: 10px;">
                @endif
                <h2>{{ $companyName }}</h2>
                <p>{{ $companyAddress }}</p>
            </div>
            <div class="payslip-title">
                <h3>Payslip</h3>
                <span>For the month of: {{ $monthName }}</span>
            </div>
        </div>

        <!-- Employee Information -->
        <div class="employee-details">
            <div>
                <p><strong>Employee Name:</strong> {{ $payroll->employee->full_name }}</p>
                <p><strong>Employee ID:</strong> {{ $payroll->employee->employee_code }}</p>
                <p><strong>Designation:</strong> {{ $payroll->employee->designation }}</p>
                <p><strong>Department:</strong> {{ $payroll->employee->department }}</p>
            </div>
            <div>
                <p><strong>Bank Name:</strong> {{ $payroll->employee->bank_name }}</p>
                <p><strong>Account No:</strong> {{ $payroll->employee->account_number }}</p>
                <p><strong>PAN:</strong> {{ $payroll->employee->pan_number }}</p>
                <p><strong>LOP Days:</strong> {{ $payroll->total_lop_days }}</p>
            </div>
        </div>

        <!-- Earnings & Deductions Tables -->
        <div class="tables-section">
            <!-- Earnings -->
            <div class="table-box">
                <h4>Earnings</h4>
                <table>
                    <tbody>
                        @foreach($earningLabels as $label)
                            <tr>
                                <td>{{ $label }}</td>
                                <td>₹{{ number_format($earnings[$label]->amount ?? 0, 2) }}</td>
                            </tr>
                        @endforeach
                        <tr class="total-row">
                            <td>Total Earnings</td>
                            <td>₹{{ number_format($payroll->total_earnings, 2) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Deductions -->
            <div class="table-box">
                <h4>Deductions</h4>
                <table>
                    <tbody>
                        @foreach($deductionLabels as $label)
                            <tr>
                                <td>{{ $label }}</td>
                                <td>₹{{ number_format($deductions[$label]->amount ?? 0, 2) }}</td>
                            </tr>
                        @endforeach
                        <tr class="total-row">
                            <td>Total Deductions</td>
                            <td>₹{{ number_format($payroll->total_deductions + $payroll->lop_deduction_amount, 2) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Net Pay -->
        <div class="net-pay-container">
            <div class="net-pay-box">
                <span>Net Pay for the month</span>
                <strong>₹{{ number_format($payroll->net_salary, 2) }}</strong>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>This is a system-generated payslip and does not require a physical signature.</p>
        </div>
    </div>
</body>
</html>