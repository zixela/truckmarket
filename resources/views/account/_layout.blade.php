@extends('layouts.app')

@section('title', __('account.title'))

@section('content')
<div class="mb-4 flex flex-wrap items-center justify-between gap-3">
    <div>
        <h1 class="text-xl font-bold">{{ __('account.title') }}</h1>
        <p class="text-sm text-gray-500">{{ __('account.subtitle') }}</p>
    </div>
    <a href="{{ route('account.listings.create') }}"
       class="rounded-md bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600">
        + {{ __('listings.add') }}
    </a>
</div>

<div class="grid gap-4 lg:grid-cols-[280px_1fr]">
    <aside class="space-y-4">
        @include('account._sidebar')
    </aside>

    <section class="space-y-4">
        @yield('account-content')
    </section>
</div>
@endsection
