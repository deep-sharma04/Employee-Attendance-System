<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Authorize Application</title>
    <style>
        body { font-family: sans-serif; padding: 40px; background: #f9f9f9; }
        .card { max-width: 500px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .buttons { display: flex; gap: 10px; margin-top: 20px; }
        button { padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
        .approve { background: #4F46E5; color: white; }
        .deny { background: #E5E7EB; color: #374151; }
    </style>
</head>
<body>
    <div class="card">
        <h2>Authorize {{ $client->name }}</h2>
        <p><strong>{{ $client->name }}</strong> is requesting permission to access your account.</p>

        <div class="scopes">
            <p>This application will be able to:</p>
            <ul>
                @foreach ($scopes as $scope)
                    <li>{{ $scope->description }}</li>
                @endforeach
            </ul>
        </div>

        <div class="buttons">
            <form method="POST" action="{{ route('passport.authorizations.approve') }}">
                @csrf
                <input type="hidden" name="state" value="{{ $request->state }}">
                <input type="hidden" name="client_id" value="{{ $client->id }}">
                <input type="hidden" name="auth_token" value="{{ $authToken }}">
                <button type="submit" class="approve">Approve</button>
            </form>

            <form method="POST" action="{{ route('passport.authorizations.deny') }}">
                @method('DELETE')
                @csrf
                <input type="hidden" name="state" value="{{ $request->state }}">
                <input type="hidden" name="client_id" value="{{ $client->id }}">
                <input type="hidden" name="auth_token" value="{{ $authToken }}">
                <button type="submit" class="deny">Deny</button>
            </form>
        </div>
    </div>
</body>
</html>
