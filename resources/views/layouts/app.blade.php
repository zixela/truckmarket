<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __('common.app_name')) — {{ __('common.app_name') }}</title>
    @yield('head')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-50 text-gray-900 antialiased">

<header class="border-b border-gray-200 bg-white">
    <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-3 px-4 py-3">
        <a href="{{ route('home') }}" class="text-lg font-bold text-brand-600">{{ __('common.app_name') }}</a>

        <nav class="flex flex-wrap items-center gap-2 text-sm">
            <a href="{{ route('listings.type', ['typeSlug' => \App\Enums\ListingType::Truck->slug()]) }}" class="rounded-md px-3 py-2 hover:bg-gray-100">{{ __('common.marketplace') }}</a>

            @auth
                <a href="{{ route('account.listings.index') }}" class="rounded-md px-3 py-2 hover:bg-gray-100">{{ __('common.my_account') }}</a>
                @if (auth()->user()->isAdmin())
                    <a href="{{ url('/admin') }}" class="rounded-md px-3 py-2 hover:bg-gray-100">{{ __('common.admin_panel') }}</a>
                @endif
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="rounded-md px-3 py-2 hover:bg-gray-100">{{ __('common.logout') }}</button>
                </form>
            @else
                <a href="{{ route('login') }}" class="rounded-md px-3 py-2 hover:bg-gray-100">{{ __('common.login') }}</a>
                <a href="{{ route('register') }}" class="rounded-md bg-brand-500 px-3 py-2 font-medium text-white hover:bg-brand-600">{{ __('common.register') }}</a>
            @endauth

            <x-locale-switcher />
        </nav>
    </div>
</header>

<main class="mx-auto max-w-7xl px-4 py-6">
    <x-flash />
    @yield('content')
</main>

<footer class="mt-10 border-t border-gray-200 bg-white py-6 text-center text-sm text-gray-500">
    &copy; {{ date('Y') }} {{ __('common.app_name') }}
</footer>

</body>
</html>
