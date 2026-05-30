<x-mail::message>
# Welcome to {{ config('app.name') }}

Hi **{{ $name }}**, your account has been created.

**Email:** {{ $email }}

**Temporary password:** `{{ $temporaryPassword }}`

Please log in and change your password as soon as possible.

<x-mail::button :url="$loginUrl">
Log In
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
