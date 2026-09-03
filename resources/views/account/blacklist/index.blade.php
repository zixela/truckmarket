@extends('account._layout')

@section('account-content')
<h2 class="text-lg font-semibold">{{ __('account.blacklist') }}</h2>
<p class="text-sm text-gray-500 dark:text-gray-400">{{ __('account.blacklist_intro') }}</p>

<div class="flex flex-wrap items-start justify-between gap-3">
    <form method="GET" action="{{ route('account.blacklist.index') }}" class="flex gap-2">
        <input type="text" name="q" value="{{ $search }}" placeholder="🔍 {{ __('account.blacklist_search') }}"
               class="rounded-md border border-gray-300 dark:border-gray-600 px-3 py-2 text-sm">
        <button type="submit" class="rounded-md border border-gray-300 dark:border-gray-600 px-3 py-2 text-sm hover:bg-gray-50 dark:hover:bg-gray-800">{{ __('common.search') }}</button>
    </form>

    <form method="POST" action="{{ route('account.blacklist.store') }}" class="flex flex-wrap gap-2">
        @csrf
        <input type="text" name="identifier" required placeholder="{{ __('account.blacklist_user') }}"
               class="rounded-md border border-gray-300 dark:border-gray-600 px-3 py-2 text-sm">
        <input type="text" name="reason" placeholder="{{ __('account.blacklist_reason') }}"
               class="rounded-md border border-gray-300 dark:border-gray-600 px-3 py-2 text-sm">
        <button type="submit" class="rounded-md bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600">
            + {{ __('account.blacklist_add') }}
        </button>
    </form>
</div>

@forelse ($entries as $entry)
    <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-4">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div>
                <a href="{{ route('profile.show', $entry->blockedUser) }}" class="font-semibold text-brand-600 dark:text-brand-400 hover:underline">
                    {{ $entry->blockedUser->name }}
                </a>
                <span class="ml-2 rounded-full bg-gray-100 dark:bg-gray-800 px-2 py-0.5 text-xs text-gray-600 dark:text-gray-400">
                    {{ $entry->blockedUser->role()?->label() }}
                </span>
            </div>
            <form method="POST" action="{{ route('account.blacklist.destroy', $entry) }}">
                @csrf
                @method('DELETE')
                <button type="submit" class="rounded-md border border-red-200 dark:border-red-800 px-3 py-1 text-xs text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/50">
                    {{ __('account.remove') }}
                </button>
            </form>
        </div>
        <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('account.added_on') }}: {{ $entry->created_at->format('M d, Y') }}</div>
        <div class="text-sm text-gray-700 dark:text-gray-300">{{ $entry->reason ?: __('account.no_reason') }}</div>
    </div>
@empty
    <div class="rounded-lg border border-dashed border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 p-8 text-center text-gray-500 dark:text-gray-400">
        {{ __('account.blacklist_empty') }}
    </div>
@endforelse

{{ $entries->links() }}
@endsection
