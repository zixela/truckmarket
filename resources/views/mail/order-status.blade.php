<x-mail::message>
# {{ config('app.name') }}

{{ $line }}

<x-mail::button :url="$url">
{{ __('orders.mail.view_order') }}
</x-mail::button>

{{ config('app.name') }}
</x-mail::message>
