@extends('account._layout')

@section('account-content')
<h2 class="text-lg font-semibold">{{ __('account.listings') }}</h2>

@if ($listings->isEmpty())
    <div class="rounded-lg border border-dashed border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 p-10 text-center text-gray-500 dark:text-gray-400">
        {{ __('account.no_listings') }}
    </div>
@else
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @foreach ($listings as $listing)
            <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900">
                @if ($listing->coverUrl())
                    <img src="{{ $listing->coverUrl() }}" alt="{{ $listing->title }}" class="h-32 w-full object-cover">
                @else
                    <div class="flex h-32 items-center justify-center bg-gray-100 dark:bg-gray-800 text-3xl">{{ $listing->type->icon() }}</div>
                @endif
                <div class="space-y-2 p-3">
                    <div class="text-sm font-semibold">{{ $listing->title }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">
                        {{ $listing->type->label() }} • {{ $listing->status->label() }}
                        @if ($listing->price) • ${{ number_format($listing->price) }} @endif
                    </div>
                    <div class="flex gap-2">
                        <a href="{{ route('account.listings.edit', $listing) }}"
                           class="rounded-md border border-gray-300 dark:border-gray-600 px-3 py-1 text-xs hover:bg-gray-50 dark:hover:bg-gray-800">{{ __('common.edit') }}</a>
                        <a href="{{ $listing->seoUrl() }}"
                           class="rounded-md border border-gray-300 dark:border-gray-600 px-3 py-1 text-xs hover:bg-gray-50 dark:hover:bg-gray-800">→</a>
                        <form method="POST" action="{{ route('account.listings.destroy', $listing) }}"
                              onsubmit="return confirm('{{ __('common.delete') }}?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="rounded-md border border-red-200 dark:border-red-800 px-3 py-1 text-xs text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/50">
                                {{ __('common.delete') }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{ $listings->links() }}
@endif
@endsection
