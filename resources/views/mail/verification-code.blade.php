<x-mail::message>
# {{ __('auth.verify_title') }}

{{ __('auth.code_mail_line') }}

<x-mail::panel>
# {{ $code }}
</x-mail::panel>

{{ __('auth.code_mail_expires', ['minutes' => $minutes]) }}

{{ __('auth.code_mail_ignore') }}

{{ config('app.name') }}
</x-mail::message>
