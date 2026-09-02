@extends('layouts.app')

@section('title', __('auth.reset_password'))

@section('content')
<div class="mx-auto max-w-md rounded-lg border border-gray-200 bg-white p-6">
    <h1 class="mb-4 text-xl font-bold">{{ __('auth.reset_password') }}</h1>

    <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <label class="block text-sm">
            <span class="mb-1 block font-medium">{{ __('auth.email') }}</span>
            <input type="email" name="email" value="{{ old('email', $email) }}" required
                   class="w-full rounded-md border border-gray-300 px-3 py-2">
        </label>

        <label class="block text-sm">
            <span class="mb-1 block font-medium">{{ __('auth.password_field') }}</span>
            <input type="password" name="password" required class="w-full rounded-md border border-gray-300 px-3 py-2">
        </label>

        <label class="block text-sm">
            <span class="mb-1 block font-medium">{{ __('auth.password_confirm') }}</span>
            <input type="password" name="password_confirmation" required class="w-full rounded-md border border-gray-300 px-3 py-2">
        </label>

        <button type="submit" class="w-full rounded-md bg-brand-500 px-4 py-2 font-medium text-white hover:bg-brand-600">
            {{ __('auth.reset_password') }}
        </button>
    </form>
</div>
@endsection
