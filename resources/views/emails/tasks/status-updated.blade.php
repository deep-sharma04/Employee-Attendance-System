<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Task Status Updated</title>
</head>
<body style="font-family: sans-serif; line-height: 1.5; color: #333;">
    <div style="max-w-2xl; margin: 0 auto; padding: 20px;">
        <h2>Task Status Updated: {{ $task->task_code }}</h2>
        <p><strong>{{ $updater->name }}</strong> has updated the status of the following task:</p>
        
        <table style="width: 100%; border-collapse: collapse; margin-top: 20px;">
            <tr>
                <td style="padding: 10px; border-bottom: 1px solid #ddd; width: 30%;"><strong>Title</strong></td>
                <td style="padding: 10px; border-bottom: 1px solid #ddd;">{{ $task->title }}</td>
            </tr>
            <tr>
                <td style="padding: 10px; border-bottom: 1px solid #ddd;"><strong>New Status</strong></td>
                <td style="padding: 10px; border-bottom: 1px solid #ddd;">{{ $task->status->label() }}</td>
            </tr>
            <tr>
                <td style="padding: 10px; border-bottom: 1px solid #ddd;"><strong>Project</strong></td>
                <td style="padding: 10px; border-bottom: 1px solid #ddd;">{{ $task->project->name }}</td>
            </tr>
        </table>
        
        <p style="margin-top: 30px;">
            Please log in to the system to view more details.
        </p>
    </div>
</body>
</html>
