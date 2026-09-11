<x-mail::message>
# {{ config('app.site_host') }}

{{ $line }}

<x-mail::button :url="$url">
{{ __('orders.mail.view_order') }}
</x-mail::button>

{{ config('app.site_host') }}
</x-mail::message>
