@props(['listing'])

<a href="{{ $listing->seoUrl() }}"
   class="block overflow-hidden rounded-lg border border-gray-200 bg-white transition hover:shadow-md">
    @if ($listing->coverUrl())
        <img src="{{ $listing->coverUrl() }}" alt="{{ $listing->title }}" class="h-40 w-full object-cover">
    @else
        <div class="flex h-40 w-full items-center justify-center bg-gray-100 text-4xl">{{ $listing->type->icon() }}</div>
    @endif

    <div class="space-y-1 p-3">
        <div class="line-clamp-2 text-sm font-semibold">{{ $listing->title }}</div>
        @if ($listing->price)
            <div class="font-bold text-brand-600">${{ number_format($listing->price) }}</div>
        @endif
        <div class="text-xs text-gray-500">
            {{ $listing->type->label() }}@if ($listing->zip) • {{ $listing->zip }} @endif
        </div>
    </div>
</a>
