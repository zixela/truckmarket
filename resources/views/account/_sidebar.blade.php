@php
    $user = auth()->user();
    $summary = app(\App\Services\RatingService::class)->summary($user);
    $menu = [
        ['route' => 'account.listings.index', 'icon' => '🔴', 'title' => __('account.listings'), 'sub' => __('account.listings_sub')],
        ['route' => 'account.orders.index', 'icon' => '🔗', 'title' => __('account.orders'), 'sub' => __('account.orders_sub')],
        ['route' => 'account.reviews.index', 'icon' => '⭐', 'title' => __('account.rating'), 'sub' => __('account.rating_sub')],
        ['route' => 'account.blacklist.index', 'icon' => '🚫', 'title' => __('account.blacklist'), 'sub' => __('account.blacklist_sub')],
        ['route' => 'account.settings.edit', 'icon' => '⚙️', 'title' => __('account.settings'), 'sub' => __('account.settings_sub')],
    ];
@endphp

<div class="rounded-lg border border-gray-200 bg-white p-4 text-center">
    <form method="POST" action="{{ route('account.settings.avatar') }}" enctype="multipart/form-data" x-data>
        @csrf
        <button type="button" class="group relative mx-auto block" @click="$refs.avatarInput.click()">
            @if ($user->avatarUrl())
                <img src="{{ $user->avatarUrl() }}" alt="{{ $user->name }}" class="mx-auto h-20 w-20 rounded-full object-cover">
            @else
                <span class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-gray-100 text-3xl">👤</span>
            @endif
        </button>
        <input type="file" name="avatar" accept="image/*" class="hidden" x-ref="avatarInput" @change="$el.form.submit()">
    </form>
    <p class="mt-1 text-xs text-gray-400">{{ __('account.avatar_hint') }}</p>

    <div class="mt-2 font-semibold">{{ $user->name }}</div>
    <div class="text-xs text-gray-500">{{ $user->role()?->label() }}</div>

    <a href="{{ route('account.reviews.index') }}" class="mt-2 block">
        <x-stars :score="$summary['average']" />
        <span class="text-sm text-gray-600">{{ $summary['average'] }} ({{ $summary['count'] }} {{ __('account.reviews') }})</span>
    </a>
</div>

<nav class="rounded-lg border border-gray-200 bg-white p-2">
    <div class="px-3 py-2 text-xs font-semibold uppercase text-gray-400">{{ __('account.menu') }}</div>
    @foreach ($menu as $item)
        <a href="{{ route($item['route']) }}"
           class="flex items-center gap-3 rounded-md px-3 py-2 {{ request()->routeIs(str_replace('.index', '.*', $item['route'])) || request()->routeIs($item['route']) ? 'bg-brand-50 text-brand-700' : 'hover:bg-gray-50' }}">
            <span>{{ $item['icon'] }}</span>
            <span>
                <span class="block text-sm font-medium">{{ $item['title'] }}</span>
                <span class="block text-xs text-gray-500">{{ $item['sub'] }}</span>
            </span>
        </a>
    @endforeach
</nav>
