<x-mail::message>
# Verification Code

Hello,

Your verification code is:

<x-mail::panel>
# {{ $otp }}
</x-mail::panel>

This code will expire shortly. Please do not share this code with anyone.

If you did not request this code, you can safely ignore this email.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>