@extends('layouts.app')

@section('title', __('auth.choose_role_title'))

@section('content')
<div class="mx-auto max-w-md rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-6">
    <h1 class="mb-2 text-xl font-bold">{{ __('auth.choose_role_title') }}</h1>
    <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">{{ __('auth.choose_role_intro') }}</p>

    <form method="POST" action="{{ route('auth.role.store') }}" class="space-y-4"
          x-data="{ role: '{{ old('role', $roles[0]->value) }}' }">
        @csrf
        <div class="grid grid-cols-2 gap-2">
            @foreach ($roles as $role)
                <label class="cursor-pointer rounded-md border border-gray-300 dark:border-gray-600 px-3 py-2 text-sm has-[:checked]:border-brand-500 has-[:checked]:bg-brand-50">
                    <input type="radio" name="role" value="{{ $role->value }}" class="sr-only" x-model="role"
                           @checked(old('role') === $role->value || (! old('role') && $loop->first))>
                    {{ $role->label() }}
                </label>
            @endforeach
        </div>

        {{-- Company-only fields: same as on the registration page --}}
        <div x-show="role === '{{ \App\Enums\UserRole::Company->value }}'" x-cloak
             class="space-y-4 rounded-md border border-brand-100 dark:border-brand-500/30 bg-brand-50/50 dark:bg-brand-500/5 p-3">
            <label class="block text-sm">
                <span class="mb-1 block font-medium">{{ __('auth.company_name') }}</span>
                <input type="text" name="company_name" value="{{ old('company_name') }}"
                       placeholder="Vmoon Corporation"
                       :required="role === '{{ \App\Enums\UserRole::Company->value }}'"
                       class="w-full rounded-md border border-gray-300 dark:border-gray-600 px-3 py-2">
            </label>

            <label class="block text-sm">
                <span class="mb-1 block font-medium">{{ __('auth.company_number') }}</span>
                <input type="text" name="company_number" value="{{ old('company_number') }}"
                       placeholder="1234567" inputmode="numeric"
                       :required="role === '{{ \App\Enums\UserRole::Company->value }}'"
                       class="w-full rounded-md border border-gray-300 dark:border-gray-600 px-3 py-2">
                <span class="mt-1 block text-xs text-gray-500 dark:text-gray-400">{{ __('auth.company_number_hint') }}</span>
            </label>

            <label class="block text-sm">
                <span class="mb-1 block font-medium">{{ __('auth.company_phone') }}</span>
                <input type="tel" name="company_phone" value="{{ old('company_phone') }}"
                       placeholder="+1 555 123 4567"
                       :required="role === '{{ \App\Enums\UserRole::Company->value }}'"
                       class="w-full rounded-md border border-gray-300 dark:border-gray-600 px-3 py-2">
                <span class="mt-1 block text-xs text-gray-500 dark:text-gray-400">{{ __('auth.company_phone_hint') }}</span>
            </label>
        </div>

        <button type="submit" class="w-full rounded-md bg-brand-500 px-4 py-2 font-medium text-white hover:bg-brand-600">
            {{ __('common.save') }}
        </button>
    </form>
</div>
@endsection
