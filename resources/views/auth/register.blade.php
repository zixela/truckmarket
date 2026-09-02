@extends('layouts.app')

@section('title', __('auth.register_title'))

@section('content')
<div class="mx-auto max-w-md rounded-lg border border-gray-200 bg-white p-6">
    <h1 class="mb-4 text-xl font-bold">{{ __('auth.register_title') }}</h1>

    <form method="POST" action="{{ route('register') }}" class="space-y-4"
          x-data="{ role: '{{ old('role', $roles[0]->value) }}' }">
        @csrf

        <label class="block text-sm">
            <span class="mb-1 block font-medium">{{ __('auth.name') }}</span>
            <input type="text" name="name" value="{{ old('name') }}" required autofocus
                   class="w-full rounded-md border border-gray-300 px-3 py-2">
        </label>

        <label class="block text-sm">
            <span class="mb-1 block font-medium">{{ __('auth.email') }}</span>
            <input type="email" name="email" value="{{ old('email') }}" required
                   class="w-full rounded-md border border-gray-300 px-3 py-2">
        </label>

        <div class="text-sm">
            <span class="mb-1 block font-medium">{{ __('auth.role') }}</span>
            <div class="grid grid-cols-2 gap-2">
                @foreach ($roles as $role)
                    <label class="cursor-pointer rounded-md border border-gray-300 px-3 py-2 text-sm has-[:checked]:border-brand-500 has-[:checked]:bg-brand-50">
                        <input type="radio" name="role" value="{{ $role->value }}" class="sr-only" x-model="role"
                               @checked(old('role') === $role->value || (! old('role') && $loop->first))>
                        {{ $role->label() }}
                    </label>
                @endforeach
            </div>
        </div>

        {{-- Company-only fields --}}
        <div x-show="role === '{{ \App\Enums\UserRole::Company->value }}'" x-cloak
             class="space-y-4 rounded-md border border-brand-100 bg-brand-50/50 p-3">
            <label class="block text-sm">
                <span class="mb-1 block font-medium">{{ __('auth.company_name') }}</span>
                <input type="text" name="company_name" value="{{ old('company_name') }}"
                       placeholder="Vmoon Corporation"
                       :required="role === '{{ \App\Enums\UserRole::Company->value }}'"
                       class="w-full rounded-md border border-gray-300 px-3 py-2">
            </label>

            <label class="block text-sm">
                <span class="mb-1 block font-medium">{{ __('auth.company_number') }}</span>
                <input type="text" name="company_number" value="{{ old('company_number') }}"
                       placeholder="1234567" inputmode="numeric"
                       :required="role === '{{ \App\Enums\UserRole::Company->value }}'"
                       class="w-full rounded-md border border-gray-300 px-3 py-2">
                <span class="mt-1 block text-xs text-gray-500">{{ __('auth.company_number_hint') }}</span>
            </label>

            <label class="block text-sm">
                <span class="mb-1 block font-medium">{{ __('auth.company_phone') }}</span>
                <input type="tel" name="company_phone" value="{{ old('company_phone') }}"
                       placeholder="+1 555 123 4567"
                       :required="role === '{{ \App\Enums\UserRole::Company->value }}'"
                       class="w-full rounded-md border border-gray-300 px-3 py-2">
                <span class="mt-1 block text-xs text-gray-500">{{ __('auth.company_phone_hint') }}</span>
            </label>
        </div>

        <label class="block text-sm">
            <span class="mb-1 block font-medium">{{ __('auth.password_field') }}</span>
            <input type="password" name="password" required class="w-full rounded-md border border-gray-300 px-3 py-2">
        </label>

        <label class="block text-sm">
            <span class="mb-1 block font-medium">{{ __('auth.password_confirm') }}</span>
            <input type="password" name="password_confirmation" required class="w-full rounded-md border border-gray-300 px-3 py-2">
        </label>

        <button type="submit" class="w-full rounded-md bg-brand-500 px-4 py-2 font-medium text-white hover:bg-brand-600">
            {{ __('common.register') }}
        </button>
    </form>

    <div class="my-4 text-center text-xs uppercase text-gray-400">{{ __('auth.or') }}</div>

    <a href="{{ route('auth.google') }}"
       class="block rounded-md border border-gray-300 px-4 py-2 text-center text-sm font-medium hover:bg-gray-50">
        {{ __('auth.continue_with_google') }}
    </a>

    <p class="mt-4 text-center text-sm text-gray-600">
        {{ __('auth.have_account') }} <a href="{{ route('login') }}" class="text-brand-600 hover:underline">{{ __('common.login') }}</a>
    </p>
</div>
@endsection
