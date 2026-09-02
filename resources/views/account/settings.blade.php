@extends('account._layout')

@section('account-content')
<h2 class="text-lg font-semibold">{{ __('account.settings') }}</h2>

<form method="POST" action="{{ route('account.settings.update') }}" class="space-y-4 rounded-lg border border-gray-200 bg-white p-4">
    @csrf
    @method('PUT')

    <div class="text-sm font-semibold text-gray-700">{{ __('account.profile_fields') }}</div>

    <div class="grid gap-4 sm:grid-cols-2">
        <label class="block text-sm">
            <span class="mb-1 block font-medium">{{ __('account.fullname') }}</span>
            <input type="text" name="name" required value="{{ old('name', $user->name) }}"
                   class="w-full rounded-md border border-gray-300 px-3 py-2">
        </label>
        <label class="block text-sm">
            <span class="mb-1 block font-medium">{{ __('account.company_name') }}</span>
            <input type="text" name="company_name" value="{{ old('company_name', $user->company_name) }}" placeholder="Vmoon Corporation"
                   class="w-full rounded-md border border-gray-300 px-3 py-2">
        </label>
        <label class="block text-sm">
            <span class="mb-1 block font-medium">{{ __('account.email') }}</span>
            <input type="email" name="email" required value="{{ old('email', $user->email) }}"
                   class="w-full rounded-md border border-gray-300 px-3 py-2">
        </label>
        <label class="block text-sm">
            <span class="mb-1 block font-medium">{{ __('account.phone') }}</span>
            <input type="text" name="phone" value="{{ old('phone', $user->phone) }}"
                   class="w-full rounded-md border border-gray-300 px-3 py-2">
        </label>
        <label class="block text-sm">
            <span class="mb-1 block font-medium">{{ __('account.address') }}</span>
            <input type="text" name="address" value="{{ old('address', $user->address) }}" placeholder="City, State, ZIP"
                   class="w-full rounded-md border border-gray-300 px-3 py-2">
        </label>
        <label class="block text-sm">
            <span class="mb-1 block font-medium">{{ __('account.residency') }}</span>
            <input type="text" name="residency" value="{{ old('residency', $user->residency) }}"
                   class="w-full rounded-md border border-gray-300 px-3 py-2">
        </label>
        <label class="block text-sm">
            <span class="mb-1 block font-medium">{{ __('account.zip') }}</span>
            <input type="text" name="zip" value="{{ old('zip', $user->zip) }}" placeholder="10001"
                   class="w-full rounded-md border border-gray-300 px-3 py-2">
        </label>
        <label class="block text-sm">
            <span class="mb-1 block font-medium">{{ __('account.locale') }}</span>
            <select name="locale" class="w-full rounded-md border border-gray-300 px-3 py-2">
                <option value="en" @selected(old('locale', $user->locale) === 'en')>English</option>
                <option value="ru" @selected(old('locale', $user->locale) === 'ru')>Русский</option>
            </select>
        </label>
    </div>

    <div class="border-t border-gray-100 pt-4 text-sm font-semibold text-gray-700">{{ __('account.new_password') }}</div>
    <p class="text-xs text-gray-500">{{ __('account.new_password_hint') }}</p>

    <div class="grid gap-4 sm:grid-cols-2">
        <label class="block text-sm">
            <span class="mb-1 block font-medium">{{ __('account.new_password') }}</span>
            <input type="password" name="password" class="w-full rounded-md border border-gray-300 px-3 py-2">
        </label>
        <label class="block text-sm">
            <span class="mb-1 block font-medium">{{ __('auth.password_confirm') }}</span>
            <input type="password" name="password_confirmation" class="w-full rounded-md border border-gray-300 px-3 py-2">
        </label>
    </div>

    <div class="border-t border-gray-100 pt-4 text-sm font-semibold text-gray-700">{{ __('account.notifications') }}</div>
    <label class="flex items-center gap-2 text-sm">
        <input type="checkbox" name="notify_by_email" value="1" class="rounded border-gray-300"
               @checked(old('notify_by_email', $user->notify_by_email))>
        {{ __('account.notify_by_email') }}
    </label>

    <div class="flex justify-end">
        <button type="submit" class="rounded-md bg-brand-500 px-5 py-2 font-medium text-white hover:bg-brand-600">
            {{ __('common.save') }}
        </button>
    </div>
</form>
@endsection
