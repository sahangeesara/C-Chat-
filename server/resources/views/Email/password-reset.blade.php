@component('mail::message')
# Reset Your Password

Click the button below to reset your password.

@component('mail::button', ['url' => $url])
Reset Password
@endcomponent

If the button does not work, use this link:

{{ $url }}

If you did not request a password reset, you can safely ignore this email.
@endcomponent

