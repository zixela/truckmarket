@extends('account._layout')

@section('account-content')
<h2 class="text-lg font-semibold">{{ __('account.rating_details') }}</h2>
<p class="text-sm text-gray-500">
    {{ $summary['average'] }} • {{ $summary['count'] }} {{ __('account.reviews') }} • 👍 {{ $summary['positive'] }} • 👎 {{ $summary['negative'] }}
</p>

<div class="flex gap-2">
    @foreach (['all', 'positive', 'negative'] as $option)
        <a href="{{ route('account.reviews.index', ['filter' => $option]) }}"
           class="rounded-md border border-gray-200 px-4 py-1.5 text-sm font-medium {{ $filter === $option ? 'bg-brand-500 text-white' : 'bg-white hover:bg-gray-50' }}">
            {{ __('account.filter_'.$option) }}
        </a>
    @endforeach
</div>

@forelse ($reviews as $review)
    <div class="rounded-lg border border-gray-200 bg-white p-4">
        <div class="flex items-center justify-between">
            <div>
                <span class="font-medium">{{ $review->author->name }}</span>
                <span class="ml-2 rounded-full px-2 py-0.5 text-xs {{ $review->is_positive ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                    {{ $review->is_positive ? __('account.review_positive') : __('account.review_negative') }}
                </span>
            </div>
            <span class="font-bold text-brand-600">{{ number_format($review->score, 1) }}</span>
        </div>
        <div class="text-sm text-gray-500">
            <x-stars :score="$review->score" /> • {{ $review->created_at->format('M d, Y') }}
            @if ($review->order?->listing) • {{ $review->order->listing->title }} @endif
        </div>
        <p class="mt-2 text-sm text-gray-700">{{ $review->body }}</p>

        @if ($review->reply)
            <div class="mt-3 border-l-2 border-brand-300 pl-3">
                <div class="text-xs font-medium text-gray-500">{{ __('account.your_reply') }}</div>
                <p class="text-sm italic text-gray-600">{{ $review->reply }}</p>
            </div>
        @else
            <form method="POST" action="{{ route('account.reviews.reply', $review) }}" class="mt-3 space-y-2">
                @csrf
                <textarea name="reply" rows="2" required placeholder="{{ __('account.reply_placeholder') }}"
                          class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm"></textarea>
                <button type="submit" class="rounded-md border border-brand-300 px-3 py-1.5 text-xs font-medium text-brand-600 hover:bg-brand-50">
                    {{ __('account.reply_submit') }}
                </button>
            </form>
        @endif
    </div>
@empty
    <div class="rounded-lg border border-dashed border-gray-300 bg-white p-8 text-center text-gray-500">
        {{ __('account.no_reviews') }}
    </div>
@endforelse

{{ $reviews->links() }}
@endsection
