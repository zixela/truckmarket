@extends('account._layout')

@section('account-content')
<h2 class="text-lg font-semibold">{{ __('account.listings') }}</h2>

@if ($listings->isEmpty())
    <div class="rounded-lg border border-dashed border-gray-300 bg-white p-10 text-center text-gray-500">
        {{ __('account.no_listings') }}
    </div>
@else
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @foreach ($listings as $listing)
            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white">
                @if ($listing->coverUrl())
                    <img src="{{ $listing->coverUrl() }}" alt="{{ $listing->title }}" class="h-32 w-full object-cover">
                @else
                    <div class="flex h-32 items-center justify-center bg-gray-100 text-3xl">{{ $listing->type->icon() }}</div>
                @endif
                <div class="space-y-2 p-3">
                    <div class="text-sm font-semibold">{{ $listing->title }}</div>
                    <div class="text-xs text-gray-500">
                        {{ $listing->type->label() }} • {{ $listing->status->label() }}
                        @if ($listing->price) • ${{ number_format($listing->price) }} @endif
                    </div>
                    <div class="flex gap-2">
                        <a href="{{ route('account.listings.edit', $listing) }}"
                           class="rounded-md border border-gray-300 px-3 py-1 text-xs hover:bg-gray-50">{{ __('common.edit') }}</a>
                        <a href="{{ $listing->seoUrl() }}"
                           class="rounded-md border border-gray-300 px-3 py-1 text-xs hover:bg-gray-50">→</a>
                        <form method="POST" action="{{ route('account.listings.destroy', $listing) }}"
                              onsubmit="return confirm('{{ __('common.delete') }}?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="rounded-md border border-red-200 px-3 py-1 text-xs text-red-600 hover:bg-red-50">
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
