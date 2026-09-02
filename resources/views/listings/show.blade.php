@extends('layouts.app')

@section('title', $listing->title.' — '.$listing->type->label())

@section('head')
    <link rel="canonical" href="{{ $listing->seoUrl() }}">
    <meta name="description" content="{{ \Illuminate\Support\Str::limit(trim(preg_replace('/\s+/', ' ', (string) $listing->description)) ?: $listing->title.' — '.$listing->type->label(), 160) }}">
    <meta property="og:title" content="{{ $listing->title }}">
    <meta property="og:type" content="product">
    <meta property="og:url" content="{{ $listing->seoUrl() }}">
    @if ($listing->coverUrl())
        <meta property="og:image" content="{{ $listing->coverUrl() }}">
    @endif
@endsection

@section('content')
<div class="grid gap-6 lg:grid-cols-[2fr_1fr]">

    <div class="space-y-4">
        <div class="rounded-lg border border-gray-200 bg-white p-4">
            <div class="mb-2 flex flex-wrap items-center gap-2 text-sm text-gray-500">
                <span class="rounded-full bg-gray-100 px-3 py-1">{{ $listing->type->icon() }} {{ $listing->type->label() }}</span>
                @if ($listing->zip)<span>{{ $listing->zip }}</span>@endif
                <span>{{ __('listings.views') }}: {{ $listing->views }}</span>
            </div>

            <h1 class="text-2xl font-bold">{{ $listing->title }}</h1>

            @if ($listing->price)
                <div class="mt-2 text-2xl font-bold text-brand-600">${{ number_format($listing->price) }}</div>
            @endif

            @php
                $photos = $listing->getMedia(\App\Models\Listing::PHOTO_COLLECTION)
                    ->map(fn ($photo) => ['full' => $photo->getUrl(), 'thumb' => $photo->getUrl('card')])
                    ->values();
            @endphp

            @if ($photos->isNotEmpty())
                <div class="mt-4"
                     x-data="{
                        photos: {{ Js::from($photos) }},
                        current: 0,
                        open: false,
                        prev() { this.current = (this.current - 1 + this.photos.length) % this.photos.length },
                        next() { this.current = (this.current + 1) % this.photos.length },
                     }"
                     @keydown.window.escape="open = false"
                     @keydown.window.arrow-left="open && prev()"
                     @keydown.window.arrow-right="open && next()">

                    {{-- Main photo --}}
                    <button type="button" @click="open = true"
                            class="group relative block w-full cursor-zoom-in overflow-hidden rounded-lg">
                        <img :src="photos[current].full" alt="{{ $listing->title }}"
                             class="h-72 w-full object-cover transition group-hover:scale-[1.01] md:h-96">
                        <span class="absolute bottom-2 right-2 rounded-md bg-black/60 px-2 py-1 text-xs text-white">
                            🔍 <span x-text="(current + 1) + ' / ' + photos.length"></span>
                        </span>
                    </button>

                    {{-- Thumbnails --}}
                    <div class="mt-2 flex gap-2 overflow-x-auto pb-1" x-show="photos.length > 1">
                        <template x-for="(photo, index) in photos" :key="index">
                            <button type="button" @click="current = index"
                                    class="shrink-0 overflow-hidden rounded-md border-2"
                                    :class="current === index ? 'border-brand-500' : 'border-transparent opacity-70 hover:opacity-100'">
                                <img :src="photo.thumb" alt="" class="h-16 w-24 object-cover">
                            </button>
                        </template>
                    </div>

                    {{-- Fullscreen lightbox --}}
                    <div x-show="open" x-cloak x-transition.opacity
                         class="fixed inset-0 z-50 flex items-center justify-center bg-black/90 p-4"
                         @click.self="open = false">

                        <button type="button" @click="open = false"
                                class="absolute right-4 top-4 rounded-full bg-white/10 px-3 py-1.5 text-2xl leading-none text-white hover:bg-white/20"
                                aria-label="Close">&times;</button>

                        <button type="button" x-show="photos.length > 1" @click.stop="prev()"
                                class="absolute left-2 top-1/2 -translate-y-1/2 rounded-full bg-white/10 px-3 py-2 text-2xl text-white hover:bg-white/20 md:left-6"
                                aria-label="Previous">‹</button>

                        <img :src="photos[current].full" alt="{{ $listing->title }}"
                             class="max-h-[90vh] max-w-full rounded-lg object-contain shadow-2xl"
                             @click.stop="next()">

                        <button type="button" x-show="photos.length > 1" @click.stop="next()"
                                class="absolute right-2 top-1/2 -translate-y-1/2 rounded-full bg-white/10 px-3 py-2 text-2xl text-white hover:bg-white/20 md:right-6"
                                aria-label="Next">›</button>

                        <span class="absolute bottom-4 left-1/2 -translate-x-1/2 rounded-md bg-white/10 px-3 py-1 text-sm text-white"
                              x-text="(current + 1) + ' / ' + photos.length"></span>
                    </div>
                </div>
            @endif

            @if ($listing->description)
                <p class="mt-4 whitespace-pre-line text-gray-700">{{ $listing->description }}</p>
            @endif
        </div>

        @if ($detail)
            <div class="rounded-lg border border-gray-200 bg-white p-4">
                <h2 class="mb-3 font-semibold">{{ $listing->type->label() }}</h2>
                <dl class="grid gap-x-6 gap-y-2 text-sm sm:grid-cols-2">
                    @include('partials.details.'.$listing->type->value, ['detail' => $detail])
                </dl>
            </div>
        @endif
    </div>

    <aside class="space-y-4">
        <div class="rounded-lg border border-gray-200 bg-white p-4">
            <div class="text-xs uppercase text-gray-500">{{ __('listings.posted_by') }}</div>
            <a href="{{ route('profile.show', $listing->user) }}" class="mt-1 block font-semibold text-brand-600 hover:underline">
                {{ $listing->user->name }}
            </a>
            <div class="mt-1 text-sm text-gray-600">
                <x-stars :score="$rating['average']" /> {{ $rating['average'] }} ({{ $rating['count'] }} {{ __('account.reviews') }})
            </div>
            <div class="mt-2 text-xs text-gray-500">
                {{ __('listings.published') }}: {{ $listing->created_at->format('M d, Y') }}
            </div>
        </div>

        @auth
            @if ($canOrder)
                <form method="POST" action="{{ route('account.orders.store', $listing) }}" class="space-y-3 rounded-lg border border-gray-200 bg-white p-4">
                    @csrf
                    <label class="block text-sm">
                        <span class="mb-1 block font-medium">{{ __('orders.message') }}</span>
                        <textarea name="message" rows="3" placeholder="{{ __('orders.message_placeholder') }}"
                                  class="w-full rounded-md border border-gray-300 px-3 py-2"></textarea>
                    </label>
                    <button type="submit" class="w-full rounded-md bg-brand-500 px-4 py-2 font-medium text-white hover:bg-brand-600">
                        {{ __('orders.place_order') }}
                    </button>
                </form>
            @endif
        @else
            <a href="{{ route('login') }}" class="block rounded-lg bg-brand-500 px-4 py-3 text-center font-medium text-white hover:bg-brand-600">
                {{ __('orders.place_order') }}
            </a>
        @endauth
    </aside>
</div>
@endsection
