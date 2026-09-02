@extends('layouts.app')

@section('title', __('auth.login_title'))

@section('content')
<div class="mx-auto max-w-md rounded-lg border border-gray-200 bg-white p-6">
    <h1 class="mb-4 text-xl font-bold">{{ __('auth.login_title') }}</h1>

    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf

        <label class="block text-sm">
            <span class="mb-1 block font-medium">{{ __('auth.email') }}</span>
            <input type="email" name="email" value="{{ old('email') }}" required autofocus
                   class="w-full rounded-md border border-gray-300 px-3 py-2">
        </label>

        <label class="block text-sm">
            <span class="mb-1 block font-medium">{{ __('auth.password_field') }}</span>
            <input type="password" name="password" required class="w-full rounded-md border border-gray-300 px-3 py-2">
        </label>

        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="remember" value="1" class="rounded border-gray-300">
            {{ __('auth.remember_me') }}
        </label>

        <button type="submit" class="w-full rounded-md bg-brand-500 px-4 py-2 font-medium text-white hover:bg-brand-600">
            {{ __('common.login') }}
        </button>
    </form>

    <div class="my-4 text-center text-xs uppercase text-gray-400">{{ __('auth.or') }}</div>

    <a href="{{ route('auth.google') }}"
       class="block rounded-md border border-gray-300 px-4 py-2 text-center text-sm font-medium hover:bg-gray-50">
        {{ __('auth.continue_with_google') }}
    </a>

    <div class="mt-4 space-y-1 text-center text-sm text-gray-600">
        <a href="{{ route('password.request') }}" class="text-brand-600 hover:underline">{{ __('auth.forgot_password') }}</a>
        <div>{{ __('auth.no_account') }} <a href="{{ route('register') }}" class="text-brand-600 hover:underline">{{ __('common.register') }}</a></div>
    </div>
</div>
@endsection
