{{-- Google Analytics 4: measurement ID comes from admin → Settings → google_analytics_id. --}}
@php $gaId = \App\Models\Setting::get('google_analytics_id'); @endphp
@if ($gaId && preg_match('/^G-[A-Z0-9]{4,}$/', $gaId))
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ $gaId }}"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', '{{ $gaId }}');
    </script>
@endif
