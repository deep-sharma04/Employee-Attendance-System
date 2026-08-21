<x-mail::message>
# Welcome, {{ $user->name }}!

Your account has been successfully created. Below are your login credentials:

**Username:** {{ $user->username }}
**Password:** {{ $password }}

<x-mail::button :url="route('login')">
Login to your account
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
