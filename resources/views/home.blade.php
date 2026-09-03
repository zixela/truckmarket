@extends('layouts.app')

@section('title', __('common.home'))

@section('content')
<div x-data="{ type: '{{ \App\Enums\ListingType::Load->value }}' }" class="space-y-8">

    <div class="grid gap-4 lg:grid-cols-[260px_1fr]">
        {{-- Type sidebar with live counts --}}
        <aside class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-4">
            <div class="mb-3 flex items-center justify-between">
                <span class="font-semibold">{{ __('common.filter') }}</span>
            </div>
            <ul class="space-y-1">
                @foreach ($types as $t)
                    <li>
                        <button type="button" @click="type = '{{ $t->value }}'"
                                :class="type === '{{ $t->value }}' ? 'bg-brand-50 dark:bg-brand-500/10 text-brand-700 dark:text-brand-400 font-medium' : 'hover:bg-gray-50 dark:hover:bg-gray-800'"
                                class="flex w-full items-center justify-between rounded-md px-3 py-2 text-left text-sm">
                            <span>{{ $t->icon() }} {{ $t->label() }}</span>
                            <span class="rounded-full bg-gray-100 dark:bg-gray-800 px-2 py-0.5 text-xs text-gray-600 dark:text-gray-400">{{ $counts[$t->value] }}</span>
                        </button>
                    </li>
                @endforeach
            </ul>
        </aside>

        {{-- Per-type filter form --}}
        <section class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-4">
            @foreach ($types as $t)
                <form method="GET" action="{{ route('listings.type', ['typeSlug' => $t->slug()]) }}" x-show="type === '{{ $t->value }}'" x-cloak class="@container space-y-4">
                    @include('partials.filters.'.$t->value)

                    <div class="flex items-center gap-3">
                        <button type="submit" class="rounded-md bg-brand-500 px-5 py-2 font-medium text-white hover:bg-brand-600">
                            {{ __('common.search') }} ({{ $counts[$t->value] }})
                        </button>
                        <button type="reset" class="rounded-md border border-gray-300 dark:border-gray-600 px-4 py-2 text-sm hover:bg-gray-50 dark:hover:bg-gray-800">
                            {{ __('common.clear_filter') }}
                        </button>
                    </div>
                </form>
            @endforeach
        </section>
    </div>

    {{-- Category preview rows --}}
    @foreach ($types as $t)
        <section class="space-y-3">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold">{{ $t->icon() }} {{ $t->label() }}</h2>
                <a href="{{ route('listings.type', ['typeSlug' => $t->slug()]) }}" class="text-sm font-medium text-brand-600 dark:text-brand-400 hover:underline">
                    {{ __('common.view_all') }} →
                </a>
            </div>

            <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
                @forelse ($previews[$t->value] as $listing)
                    <x-listing-card :listing="$listing" />
                @empty
                    @for ($i = 0; $i < 4; $i++)
                        <div class="flex h-40 items-center justify-center rounded-lg border border-dashed border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-sm text-gray-400 dark:text-gray-500">
                            {{ __('common.no_listings_yet') }}
                        </div>
                    @endfor
                @endforelse
            </div>
        </section>
    @endforeach
</div>
@endsection
