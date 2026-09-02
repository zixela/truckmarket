@extends('account._layout')

@section('account-content')
<h2 class="text-lg font-semibold">{{ __('account.review_title') }}</h2>
<p class="text-sm text-gray-500">{{ $order->listing->title }} — {{ $order->owner->name }}</p>

<form method="POST" action="{{ route('account.reviews.store', $order) }}"
      class="space-y-4 rounded-lg border border-gray-200 bg-white p-4">
    @csrf

    <div class="text-sm">
        <span class="mb-1 block font-medium">{{ __('account.review_score') }}</span>
        <div class="flex gap-2">
            @foreach ([1, 2, 3, 4, 5] as $score)
                <label class="cursor-pointer rounded-md border border-gray-300 px-4 py-2 has-[:checked]:border-brand-500 has-[:checked]:bg-brand-50">
                    <input type="radio" name="score" value="{{ $score }}" class="sr-only" @checked(old('score', 5) == $score)>
                    {{ $score }} ★
                </label>
            @endforeach
        </div>
    </div>

    <div class="text-sm">
        <div class="flex gap-2">
            <label class="cursor-pointer rounded-md border border-gray-300 px-4 py-2 has-[:checked]:border-green-500 has-[:checked]:bg-green-50">
                <input type="radio" name="is_positive" value="1" class="sr-only" @checked(old('is_positive', '1') === '1')>
                👍 {{ __('account.review_positive') }}
            </label>
            <label class="cursor-pointer rounded-md border border-gray-300 px-4 py-2 has-[:checked]:border-red-500 has-[:checked]:bg-red-50">
                <input type="radio" name="is_positive" value="0" class="sr-only" @checked(old('is_positive') === '0')>
                👎 {{ __('account.review_negative') }}
            </label>
        </div>
    </div>

    <label class="block text-sm">
        <span class="mb-1 block font-medium">{{ __('account.review_body') }}</span>
        <textarea name="body" rows="4" required class="w-full rounded-md border border-gray-300 px-3 py-2">{{ old('body') }}</textarea>
    </label>

    <div class="flex justify-end gap-3">
        <a href="{{ route('account.orders.index') }}" class="rounded-md border border-gray-300 px-4 py-2 text-sm hover:bg-gray-50">
            {{ __('common.cancel') }}
        </a>
        <button type="submit" class="rounded-md bg-brand-500 px-5 py-2 font-medium text-white hover:bg-brand-600">
            {{ __('common.submit') }}
        </button>
    </div>
</form>
@endsection
