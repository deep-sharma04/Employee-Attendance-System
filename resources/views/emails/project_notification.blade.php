<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #f8fafc;
            color: #1e293b;
            margin: 0;
            padding: 24px;
        }
        .container {
            max-width: 580px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }
        .header {
            background-color: #4f46e5;
            padding: 24px;
            color: #ffffff;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 20px;
            font-weight: 700;
        }
        .badge {
            display: inline-block;
            margin-top: 8px;
            padding: 4px 10px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 9999px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .content {
            padding: 28px 24px;
            line-height: 1.6;
            font-size: 14px;
            color: #334155;
        }
        .greeting {
            font-weight: 600;
            margin-bottom: 12px;
            color: #0f172a;
        }
        .message-box {
            background: #f1f5f9;
            border-left: 4px solid #4f46e5;
            padding: 14px 16px;
            border-radius: 8px;
            margin: 16px 0;
            font-size: 14px;
        }
        .btn-wrapper {
            text-align: center;
            margin: 24px 0 12px 0;
        }
        .btn {
            display: inline-block;
            background-color: #4f46e5;
            color: #ffffff !important;
            padding: 12px 24px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            font-size: 13px;
        }
        .footer {
            background: #f8fafc;
            padding: 16px 24px;
            text-align: center;
            font-size: 12px;
            color: #64748b;
            border-top: 1px solid #e2e8f0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{{ config('app.name', 'HRM System') }}</h1>
            <span class="badge">{{ strtoupper(str_replace('_', ' ', $category)) }}</span>
        </div>
        <div class="content">
            <p class="greeting">Hello {{ $recipient->name }},</p>
            <h2 style="font-size: 16px; color: #0f172a; margin-top: 0;">{{ $title }}</h2>
            <div class="message-box">
                {{ $bodyMessage }}
            </div>
            @if(!empty($actionUrl))
                <div class="btn-wrapper">
                    <a href="{{ $actionUrl }}" class="btn">View in Workspace &rarr;</a>
                </div>
            @endif
        </div>
        <div class="footer">
            <p style="margin: 0;">This is an automated notification from {{ config('app.name', 'HRM System') }}.</p>
            <p style="margin: 4px 0 0 0;">You can manage your notification preferences in your user account settings.</p>
        </div>
    </div>
</body>
</html>
