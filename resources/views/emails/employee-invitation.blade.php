<x-mail::message>
    <x-slot:header>
        <x-mail::header :url="config('app.url')">
            {{ config('app.name') }}
        </x-mail::header>
    </x-slot:header>

    # Welcome {{ $user->name }}!

    An account has been created for you on our HR System.

    Please click the button below to set your password and activate your account.

    <x-mail::button :url="$activationUrl">
        Set Password & Activate
    </x-mail::button>

    If you did not request this, no further action is required.

    Thanks,<br>
    {{ config('app.name') }}
</x-mail::message>
