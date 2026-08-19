@php
    $companyName = \App\Models\CompanySetting::where('key', 'company_name')->value('value') ?? config('app.name', 'HRM Enterprise Inc.');
    $companyAddress = \App\Models\CompanySetting::where('key', 'company_address')->value('value') ?? '100 Business Tech Park, Silicon Corridor';
    $companyEmail = \App\Models\CompanySetting::where('key', 'company_email')->value('value') ?? 'hr@hrm.local';
    $companyPhone = \App\Models\CompanySetting::where('key', 'company_phone')->value('value') ?? '+1 (555) 019-2834';
    $companyLogo = \App\Models\CompanySetting::where('key', 'company_logo')->value('value') ?? null;
    
    $monthName = \Carbon\Carbon::createFromDate((int) $payroll->payroll_year, (int) $payroll->payroll_month, 1)->format('F Y');
    $payslipNumber = $payroll->payslip?->payslip_number ?? ('PSL-' . $payroll->payroll_year . str_pad($payroll->payroll_month, 2, '0', STR_PAD_LEFT) . '-' . $payroll->employee->employee_code);
    $generatedDate = $payroll->payslip?->generated_at ? \Carbon\Carbon::parse($payroll->payslip->generated_at)->format('d M, Y') : now()->format('d M, Y');

    // Categorize items
    $earnings = $payroll->items->where('type', 'earning');
    $deductions = $payroll->items->where('type', 'deduction');

    // Amount in Words helper
    $amountInWords = '';
    try {
        $netVal = (float) $payroll->net_salary;
        $formatter = new \NumberFormatter('en_IN', \NumberFormatter::SPELLOUT);
        $rupees = floor($netVal);
        $paise = round(($netVal - $rupees) * 100);
        $amountInWords = ucwords($formatter->format($rupees)) . ' Rupees';
        if ($paise > 0) {
            $amountInWords .= ' and ' . ucwords($formatter->format($paise)) . ' Paise';
        }
        $amountInWords .= ' Only';
    } catch (\Throwable $e) {
        $amountInWords = 'Rupees ' . number_format($payroll->net_salary, 2) . ' Only';
    }
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Payslip - {{ $payroll->employee->first_name }} {{ $payroll->employee->last_name }} - {{ $monthName }}</title>
    <style>
        @page {
            size: a4 portrait;
            margin: 10mm 12mm 10mm 12mm;
        }
        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact;
        }
        body {
            font-family: 'DejaVu Sans', 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 10px;
            line-height: 1.35;
            color: #1e293b;
            background: #ffffff;
            margin: 0;
            padding: 0;
        }
        .container {
            width: 100%;
            border: 1px solid #cbd5e1;
            padding: 14px 18px;
            background: #ffffff;
        }
        
        /* Typography & Utilities */
        .text-left { text-align: left; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }
        .uppercase { text-transform: uppercase; }
        .text-muted { color: #64748b; }
        .text-dark { color: #0f172a; }
        .text-primary { color: #1e3a8a; }
        
        /* Header section */
        table.header-table {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 2px solid #1e3a8a;
            padding-bottom: 8px;
            margin-bottom: 10px;
        }
        .company-title {
            font-size: 16px;
            font-weight: bold;
            color: #1e3a8a;
            letter-spacing: 0.5px;
            margin: 0 0 2px 0;
        }
        .company-meta {
            font-size: 8.5px;
            color: #475569;
            margin: 0;
            line-height: 1.3;
        }
        .payslip-badge {
            background-color: #1e3a8a;
            color: #ffffff;
            font-size: 12px;
            font-weight: bold;
            letter-spacing: 1px;
            padding: 4px 12px;
            display: inline-block;
            text-transform: uppercase;
        }
        .payslip-period {
            font-size: 11px;
            font-weight: bold;
            color: #0f172a;
            margin-top: 4px;
        }
        .payslip-doc-meta {
            font-size: 8.5px;
            color: #64748b;
            margin-top: 2px;
        }
        
        /* Employee details section */
        table.meta-table {
            width: 100%;
            border-collapse: collapse;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            margin-bottom: 10px;
        }
        table.meta-table td {
            padding: 4px 8px;
            font-size: 9px;
            vertical-align: top;
        }
        .meta-label {
            color: #64748b;
            font-weight: 600;
            width: 110px;
        }
        .meta-value {
            color: #0f172a;
            font-weight: bold;
        }
        
        /* Attendance strip */
        table.attendance-strip {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            border: 1px solid #cbd5e1;
            background: #f1f5f9;
        }
        table.attendance-strip th {
            background: #334155;
            color: #ffffff;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
            padding: 3px 6px;
            text-align: center;
            border-right: 1px solid #475569;
        }
        table.attendance-strip th:last-child {
            border-right: none;
        }
        table.attendance-strip td {
            font-size: 9.5px;
            font-weight: bold;
            color: #0f172a;
            padding: 3px 6px;
            text-align: center;
            border-right: 1px solid #cbd5e1;
            background: #ffffff;
        }
        table.attendance-strip td:last-child {
            border-right: none;
        }
        .lop-highlight {
            color: #b91c1c !important;
        }
        
        /* Financial Breakdowns (Side by Side) */
        table.split-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        table.split-table > tbody > tr > td {
            vertical-align: top;
            padding: 0;
        }
        
        table.finance-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #cbd5e1;
        }
        table.finance-table th {
            background-color: #1e3a8a;
            color: #ffffff;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            padding: 5px 8px;
            letter-spacing: 0.5px;
        }
        table.finance-table td {
            padding: 4.5px 8px;
            font-size: 9px;
            border-bottom: 1px solid #f1f5f9;
            color: #1e293b;
        }
        table.finance-table tr:nth-child(even) td {
            background-color: #f8fafc;
        }
        table.finance-table .total-row td {
            background-color: #e2e8f0 !important;
            font-weight: bold;
            font-size: 9.5px;
            color: #0f172a;
            border-top: 1px solid #94a3b8;
            border-bottom: none;
            padding: 5px 8px;
        }

        /* Net Salary Summary Block */
        table.net-salary-card {
            width: 100%;
            border-collapse: collapse;
            background-color: #f0fdf4;
            border: 1.5px solid #16a34a;
            margin-bottom: 10px;
        }
        table.net-salary-card td {
            padding: 8px 12px;
            vertical-align: middle;
        }
        .net-amount-label {
            font-size: 10px;
            font-weight: bold;
            color: #166534;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .net-amount-words {
            font-size: 8.5px;
            color: #14532d;
            margin-top: 2px;
            font-style: italic;
        }
        .net-amount-number {
            font-size: 16px;
            font-weight: bold;
            color: #15803d;
            text-align: right;
        }
        .status-badge {
            display: inline-block;
            font-size: 8px;
            font-weight: bold;
            color: #15803d;
            background: #dcfce7;
            border: 1px solid #86efac;
            padding: 1px 6px;
            border-radius: 3px;
            text-transform: uppercase;
            margin-top: 2px;
        }

        /* Signatures & Footer */
        table.signature-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 14px;
            margin-bottom: 8px;
        }
        table.signature-table td {
            width: 50%;
            text-align: center;
            font-size: 8.5px;
            color: #475569;
            vertical-align: bottom;
            padding: 0 20px;
        }
        .sign-line {
            border-bottom: 1px dashed #94a3b8;
            height: 28px;
            margin-bottom: 4px;
        }

        .footer-note {
            border-top: 1px solid #e2e8f0;
            padding-top: 6px;
            font-size: 7.5px;
            color: #94a3b8;
            text-align: center;
            line-height: 1.3;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- 1. Header with Company & Payslip Title -->
        <table class="header-table">
            <tr>
                <td class="text-left" style="vertical-align: middle; width: 60%;">
                    @if($companyLogo)
                        <img src="{{ $companyLogo }}" alt="Logo" style="height: 36px; margin-bottom: 4px;">
                    @endif
                    <div class="company-title">{{ $companyName }}</div>
                    <div class="company-meta">{{ $companyAddress }}</div>
                    <div class="company-meta">Email: {{ $companyEmail }} | Phone: {{ $companyPhone }}</div>
                </td>
                <td class="text-right" style="vertical-align: middle; width: 40%;">
                    <div class="payslip-badge">Salary Statement</div>
                    <div class="payslip-period">{{ $monthName }}</div>
                    <div class="payslip-doc-meta">Payslip No: <strong class="text-dark">{{ $payslipNumber }}</strong></div>
                    <div class="payslip-doc-meta">Issue Date: {{ $generatedDate }}</div>
                </td>
            </tr>
        </table>

        <!-- 2. Employee Profile & Bank Details (Side by Side 2-Columns) -->
        <table class="meta-table">
            <tr>
                <td style="width: 50%; border-right: 1px solid #e2e8f0;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <tr>
                            <td class="meta-label">Employee Name:</td>
                            <td class="meta-value">{{ $payroll->employee->first_name }} {{ $payroll->employee->last_name }}</td>
                        </tr>
                        <tr>
                            <td class="meta-label">Employee Code:</td>
                            <td class="meta-value">{{ $payroll->employee->employee_code }}</td>
                        </tr>
                        <tr>
                            <td class="meta-label">Designation:</td>
                            <td class="meta-value">{{ $payroll->employee->designation ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td class="meta-label">Department:</td>
                            <td class="meta-value">{{ $payroll->employee->department ?? 'General' }}</td>
                        </tr>
                        <tr>
                            <td class="meta-label">Date of Joining:</td>
                            <td class="meta-value">{{ $payroll->employee->joining_date ? \Carbon\Carbon::parse($payroll->employee->joining_date)->format('d M, Y') : 'N/A' }}</td>
                        </tr>
                    </table>
                </td>
                <td style="width: 50%;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <tr>
                            <td class="meta-label">Bank Name:</td>
                            <td class="meta-value">{{ $payroll->employee->bank_name ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td class="meta-label">Account No:</td>
                            <td class="meta-value">{{ $payroll->employee->account_number ? substr($payroll->employee->account_number, 0, 4) . '••••' . substr($payroll->employee->account_number, -4) : 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td class="meta-label">IFSC Code:</td>
                            <td class="meta-value">{{ $payroll->employee->ifsc_code ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td class="meta-label">PAN Number:</td>
                            <td class="meta-value">{{ $payroll->employee->pan_number ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td class="meta-label">Payment Mode:</td>
                            <td class="meta-value">Direct Bank Transfer (NEFT/RTGS)</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <!-- 3. Attendance Summary Mini Strip -->
        <table class="attendance-strip">
            <thead>
                <tr>
                    <th>Days in Month</th>
                    <th>Present Days</th>
                    <th>Half Days</th>
                    <th>Paid Leaves</th>
                    <th>Weekends & Holidays</th>
                    <th>Unpaid LOP Days</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ $payroll->total_days_in_month ?? 30 }}</td>
                    <td>{{ (float) ($payroll->present_days ?? 0) }}</td>
                    <td>{{ $payroll->half_days ?? 0 }}</td>
                    <td>{{ (float) ($payroll->leave_days ?? 0) }}</td>
                    <td>{{ ($payroll->weekend_days ?? 0) + ($payroll->holiday_days ?? 0) }}</td>
                    <td class="{{ ((float) ($payroll->total_lop_days ?? 0)) > 0 ? 'lop-highlight' : '' }}">
                        {{ (float) ($payroll->total_lop_days ?? 0) }}
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- 4. Earnings & Deductions Tables -->
        <table class="split-table">
            <tr>
                <!-- Left: Earnings -->
                <td style="width: 49%;">
                    <table class="finance-table">
                        <thead>
                            <tr>
                                <th class="text-left">Earnings (Credits)</th>
                                <th class="text-right" style="width: 90px;">Amount (₹)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($earnings as $item)
                                <tr>
                                    <td>{{ $item->label }}</td>
                                    <td class="text-right">{{ number_format($item->amount, 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td>Basic Monthly Salary</td>
                                    <td class="text-right">{{ number_format($payroll->monthly_salary, 2) }}</td>
                                </tr>
                            @endforelse

                            {{-- Fill placeholder rows for visual height parity --}}
                            @for($i = count($earnings); $i < max(3, count($deductions)); $i++)
                                <tr>
                                    <td style="color: transparent;">-</td>
                                    <td class="text-right" style="color: transparent;">-</td>
                                </tr>
                            @endfor

                            <tr class="total-row">
                                <td>Gross Earnings</td>
                                <td class="text-right">₹{{ number_format($payroll->total_earnings, 2) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </td>

                <!-- Spacer -->
                <td style="width: 2%;"></td>

                <!-- Right: Deductions -->
                <td style="width: 49%;">
                    <table class="finance-table">
                        <thead>
                            <tr>
                                <th class="text-left" style="background-color: #334155;">Deductions (Debits)</th>
                                <th class="text-right" style="background-color: #334155; width: 90px;">Amount (₹)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($deductions as $item)
                                <tr>
                                    <td>{{ $item->label }}</td>
                                    <td class="text-right text-muted">{{ number_format($item->amount, 2) }}</td>
                                </tr>
                            @empty
                                @if(((float) ($payroll->lop_deduction_amount ?? 0)) > 0)
                                    <tr>
                                        <td>Loss of Pay ({{ $payroll->total_lop_days }} days)</td>
                                        <td class="text-right text-muted">{{ number_format($payroll->lop_deduction_amount, 2) }}</td>
                                    </tr>
                                @else
                                    <tr>
                                        <td>No Statutory Deductions</td>
                                        <td class="text-right text-muted">0.00</td>
                                    </tr>
                                @endif
                            @endforelse

                            {{-- Fill placeholder rows for visual height parity --}}
                            @for($i = count($deductions); $i < max(3, count($earnings)); $i++)
                                <tr>
                                    <td style="color: transparent;">-</td>
                                    <td class="text-right" style="color: transparent;">-</td>
                                </tr>
                            @endfor

                            <tr class="total-row">
                                <td>Total Deductions</td>
                                <td class="text-right">₹{{ number_format($payroll->total_deductions, 2) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </td>
            </tr>
        </table>

        <!-- 5. Net Salary Highlight Banner -->
        <table class="net-salary-card">
            <tr>
                <td style="width: 65%;">
                    <div class="net-amount-label">Net Disbursed Take-Home Salary</div>
                    <div class="net-amount-words">In Words: {{ $amountInWords }}</div>
                </td>
                <td style="width: 35%; text-align: right;">
                    <div class="net-amount-number">₹{{ number_format($payroll->net_salary, 2) }}</div>
                    @php
                        $statusText = $payroll->payment_status instanceof \BackedEnum ? $payroll->payment_status->value : (is_string($payroll->payment_status) ? $payroll->payment_status : 'Cleared');
                    @endphp
                    <div class="status-badge">✓ Status: {{ ucfirst($statusText) }}</div>
                </td>
            </tr>
        </table>

        <!-- 6. Signatures and Authorizations -->
        <table class="signature-table">
            <tr>
                <td>
                    <div class="sign-line"></div>
                    <strong>Employee Signature</strong><br>
                    <span>(Acknowledged and Accepted)</span>
                </td>
                <td>
                    <div class="sign-line"></div>
                    <strong>Authorized Signatory</strong><br>
                    <span>For {{ $companyName }}</span>
                </td>
            </tr>
        </table>

        <!-- 7. Footer Disclaimer -->
        <div class="footer-note">
            <strong>Confidential:</strong> This document contains confidential remuneration information intended solely for the named employee.
            <br>
            This is a computer-generated statement issued by {{ $companyName }} and requires no physical signature under electronic payroll records.
        </div>
    </div>
</body>
</html>