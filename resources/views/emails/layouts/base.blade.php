<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subject ?? config('app.name', 'HRM System') }}</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #f1f5f9;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            color: #1e293b;
            line-height: 1.6;
        }
        table {
            border-collapse: collapse;
            width: 100%;
        }
        .wrapper {
            width: 100%;
            background-color: #f1f5f9;
            padding: 40px 15px;
        }
        .main-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
            border: 1px solid #e2e8f0;
        }
        .header {
            background: linear-gradient(135deg, #1e1b4b 0%, #312e81 50%, #4338ca 100%);
            padding: 32px 30px;
            text-align: center;
        }
        .header h1 {
            color: #ffffff;
            font-size: 20px;
            font-weight: 700;
            margin: 0;
            letter-spacing: 0.5px;
        }
        .header p {
            color: #cbd5e1;
            font-size: 13px;
            margin: 6px 0 0;
        }
        .content {
            padding: 32px 30px;
        }
        .btn-container {
            text-align: center;
            margin: 28px 0;
        }
        .btn {
            display: inline-block;
            background-color: #4f46e5;
            color: #ffffff !important;
            text-decoration: none;
            padding: 12px 28px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            letter-spacing: 0.3px;
            box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.3);
        }
        .info-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 18px 20px;
            margin: 20px 0;
        }
        .badge-success {
            display: inline-block;
            background-color: #dcfce7;
            color: #15803d;
            font-size: 12px;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 6px;
            text-transform: uppercase;
        }
        .badge-danger {
            display: inline-block;
            background-color: #fee2e2;
            color: #b91c1c;
            font-size: 12px;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 6px;
            text-transform: uppercase;
        }
        .footer {
            background-color: #f8fafc;
            border-top: 1px solid #e2e8f0;
            padding: 24px 30px;
            text-align: center;
            font-size: 12px;
            color: #64748b;
        }
        .footer p {
            margin: 4px 0;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="main-container">
            <div class="header">
                <h1>{{ config('app.name', 'HRM System') }}</h1>
                <p>Employee Attendance, Leave & Payroll Management</p>
            </div>
            <div class="content">
                @yield('content')
            </div>
            <div class="footer">
                <p>This is an automated notification sent by {{ config('app.name', 'HRM System') }}.</p>
                <p>Please do not reply directly to this email.</p>
                <p style="margin-top: 10px; color: #94a3b8;">&copy; {{ date('Y') }} {{ config('app.name', 'HRM System') }}. All rights reserved.</p>
            </div>
        </div>
    </div>
</body>
</html>
