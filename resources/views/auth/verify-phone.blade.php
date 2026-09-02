@extends('layouts.app')

@section('title', __('auth.phone_verify_title'))

@section('content')
<div class="mx-auto max-w-md rounded-lg border border-gray-200 bg-white p-6">
    <h1 class="mb-2 text-xl font-bold">{{ __('auth.phone_verify_title') }}</h1>
    <p class="mb-4 text-sm text-gray-600">
        {{ __('auth.phone_verify_intro', ['phone' => auth()->user()->company_phone]) }}
    </p>

    <form method="POST" action="{{ route('verification.phone.verify') }}" class="space-y-4">
        @csrf
        <label class="block text-sm">
            <span class="mb-1 block font-medium">{{ __('auth.verify_code') }}</span>
            <input type="text" name="code" inputmode="numeric" maxlength="6" required autofocus
                   class="w-full rounded-md border border-gray-300 px-3 py-2 text-center text-2xl tracking-[0.5em]">
        </label>

        <button type="submit" class="w-full rounded-md bg-brand-500 px-4 py-2 font-medium text-white hover:bg-brand-600">
            {{ __('auth.verify_submit') }}
        </button>
    </form>

    <form method="POST" action="{{ route('verification.phone.resend') }}" class="mt-3 text-center">
        @csrf
        <button type="submit" class="text-sm text-brand-600 hover:underline">{{ __('auth.sms_resend') }}</button>
    </form>
</div>
@endsection
