@extends('layouts.app')

@section('title', $user->name)

@section('content')
<div class="grid gap-6 lg:grid-cols-[300px_1fr]">

    <aside class="space-y-4 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-4 text-center">
        @if ($user->avatarUrl())
            <img src="{{ $user->avatarUrl() }}" alt="{{ $user->name }}" class="mx-auto h-24 w-24 rounded-full object-cover">
        @else
            <div class="mx-auto flex h-24 w-24 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-800 text-3xl">👤</div>
        @endif

        <div>
            <div class="font-semibold">{{ $user->name }}</div>
            @if ($user->company_name)
                <div class="text-sm text-gray-500 dark:text-gray-400">{{ $user->company_name }}</div>
            @endif
            <div class="text-xs text-gray-500 dark:text-gray-400">{{ $user->role()?->label() }}</div>
        </div>

        <div>
            <x-stars :score="$rating['average']" />
            <div class="text-sm text-gray-600 dark:text-gray-400">{{ $rating['average'] }} • {{ $rating['count'] }} {{ __('account.reviews') }}</div>
            <div class="text-xs text-gray-500 dark:text-gray-400">👍 {{ $rating['positive'] }} • 👎 {{ $rating['negative'] }}</div>
        </div>
    </aside>

    <div class="space-y-6">
        <section>
            <h2 class="mb-3 text-lg font-semibold">{{ __('listings.my_listings') }}</h2>
            @if ($listings->isEmpty())
                <div class="rounded-lg border border-dashed border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 p-6 text-center text-gray-500 dark:text-gray-400">
                    {{ __('common.no_listings_yet') }}
                </div>
            @else
                <div class="grid grid-cols-2 gap-4 md:grid-cols-3">
                    @foreach ($listings as $listing)
                        <x-listing-card :listing="$listing" />
                    @endforeach
                </div>
                {{ $listings->links() }}
            @endif
        </section>

        <section>
            <h2 class="mb-3 text-lg font-semibold">{{ __('account.rating_details') }}</h2>
            @forelse ($reviews as $review)
                <div class="mb-3 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-4">
                    <div class="flex items-center justify-between">
                        <span class="font-medium">{{ $review->author->name }}</span>
                        <span class="rounded-full px-2 py-0.5 text-xs {{ $review->is_positive ? 'bg-green-100 dark:bg-green-900/50 text-green-700 dark:text-green-300' : 'bg-red-100 dark:bg-red-900/50 text-red-700 dark:text-red-300' }}">
                            {{ $review->is_positive ? __('account.review_positive') : __('account.review_negative') }}
                        </span>
                    </div>
                    <div class="text-sm text-gray-500 dark:text-gray-400"><x-stars :score="$review->score" /> • {{ $review->created_at->format('M d, Y') }}</div>
                    <p class="mt-2 text-sm text-gray-700 dark:text-gray-300">{{ $review->body }}</p>
                    @if ($review->reply)
                        <div class="mt-2 border-l-2 border-brand-300 pl-3 text-sm italic text-gray-600 dark:text-gray-400">{{ $review->reply }}</div>
                    @endif
                </div>
            @empty
                <div class="rounded-lg border border-dashed border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 p-6 text-center text-gray-500 dark:text-gray-400">
                    {{ __('account.no_reviews') }}
                </div>
            @endforelse
        </section>
    </div>
</div>
@endsection
