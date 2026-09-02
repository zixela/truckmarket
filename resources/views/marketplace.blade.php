@extends('layouts.app')

@section('title', $activeType->label().' — '.__('common.marketplace'))

@section('head')
    <link rel="canonical" href="{{ route('listings.type', ['typeSlug' => $activeType->slug()]) }}">
    <meta name="description" content="{{ __('listings.seo.type_description', ['type' => $activeType->label(), 'count' => $counts[$activeType->value]]) }}">
@endsection

@section('content')
<div class="grid gap-4 lg:grid-cols-[280px_1fr]">

    <aside class="space-y-4 rounded-lg border border-gray-200 bg-white p-4">
        <div class="flex items-center justify-between">
            <span class="font-semibold">{{ __('common.filter') }}</span>
            <a href="{{ route('listings.type', ['typeSlug' => $activeType->slug()]) }}" class="text-xs text-brand-600 hover:underline">
                {{ __('common.clear') }}
            </a>
        </div>

        <ul class="space-y-1">
            @foreach ($types as $t)
                <li>
                    <a href="{{ route('listings.type', ['typeSlug' => $t->slug()]) }}"
                       class="flex items-center justify-between rounded-md px-3 py-2 text-sm {{ $activeType === $t ? 'bg-brand-50 font-medium text-brand-700' : 'hover:bg-gray-50' }}">
                        <span>{{ $t->icon() }} {{ $t->label() }}</span>
                        <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-600">{{ $counts[$t->value] }}</span>
                    </a>
                </li>
            @endforeach
        </ul>

        <form method="GET" action="{{ route('listings.type', ['typeSlug' => $activeType->slug()]) }}" class="@container space-y-4 border-t border-gray-100 pt-4">
            <input type="hidden" name="sort" value="{{ $filters['sort'] ?? 'newest' }}">
            @include('partials.filters.'.$activeType->value, ['filters' => $filters])

            <button type="submit" class="w-full rounded-md bg-brand-500 px-4 py-2 font-medium text-white hover:bg-brand-600">
                {{ __('common.search') }}
            </button>
        </form>
    </aside>

    <section class="space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-gray-200 bg-white px-4 py-3">
            <span class="text-sm font-medium">{{ __('common.total') }}: {{ $listings->total() }}</span>

            <form method="GET" action="{{ route('listings.type', ['typeSlug' => $activeType->slug()]) }}" class="flex items-center gap-2 text-sm">
                @foreach ($filters as $key => $value)
                    @if ($key !== 'sort' && $key !== 'type')
                        @if (is_array($value))
                            @foreach ($value as $item)
                                <input type="hidden" name="{{ $key }}[]" value="{{ $item }}">
                            @endforeach
                        @else
                            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                        @endif
                    @endif
                @endforeach
                <label for="sort">{{ __('common.sort') }}</label>
                <select id="sort" name="sort" onchange="this.form.submit()" class="rounded-md border border-gray-300 px-3 py-1.5">
                    @foreach (['newest', 'oldest', 'price_asc', 'price_desc'] as $sort)
                        <option value="{{ $sort }}" @selected(($filters['sort'] ?? 'newest') === $sort)>{{ __('listings.sort.'.$sort) }}</option>
                    @endforeach
                </select>
            </form>
        </div>

        @if ($listings->isEmpty())
            <div class="rounded-lg border border-dashed border-gray-300 bg-white p-10 text-center text-gray-500">
                {{ __('common.nothing_found') }}
            </div>
        @else
            <div class="grid grid-cols-2 gap-4 md:grid-cols-3 xl:grid-cols-4">
                @foreach ($listings as $listing)
                    <x-listing-card :listing="$listing" />
                @endforeach
            </div>

            {{ $listings->links() }}
        @endif
    </section>
</div>
@endsection
