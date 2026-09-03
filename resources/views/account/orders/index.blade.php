@extends('account._layout')

@php
    $badge = fn ($status) => match ($status->value) {
        'pending' => 'bg-yellow-100 dark:bg-yellow-900/50 text-yellow-800 dark:text-yellow-200',
        'confirmed' => 'bg-blue-100 dark:bg-blue-900/50 text-blue-800 dark:text-blue-200',
        'completed' => 'bg-green-100 dark:bg-green-900/50 text-green-800 dark:text-green-200',
        default => 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400',
    };
@endphp

@section('account-content')
<div x-data="{ tab: '{{ request('outgoing') ? 'outgoing' : 'incoming' }}' }" class="space-y-4">
    <div class="flex gap-2">
        <button @click="tab = 'incoming'" :class="tab === 'incoming' ? 'bg-brand-500 text-white' : 'bg-white dark:bg-gray-900 hover:bg-gray-50 dark:hover:bg-gray-800'"
                class="rounded-md border border-gray-200 dark:border-gray-700 px-4 py-2 text-sm font-medium">
            {{ __('orders.incoming') }} ({{ $incoming->total() }})
        </button>
        <button @click="tab = 'outgoing'" :class="tab === 'outgoing' ? 'bg-brand-500 text-white' : 'bg-white dark:bg-gray-900 hover:bg-gray-50 dark:hover:bg-gray-800'"
                class="rounded-md border border-gray-200 dark:border-gray-700 px-4 py-2 text-sm font-medium">
            {{ __('orders.outgoing') }} ({{ $outgoing->total() }})
        </button>
    </div>

    {{-- INCOMING --}}
    <div x-show="tab === 'incoming'" x-cloak class="space-y-3">
        @forelse ($incoming as $order)
            <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-4">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <a href="{{ $order->listing->seoUrl() }}" class="font-semibold text-brand-600 dark:text-brand-400 hover:underline">
                            {{ $order->listing->title }}
                        </a>
                        <div class="text-sm text-gray-500 dark:text-gray-400">
                            {{ __('orders.customer') }}:
                            <a href="{{ route('profile.show', $order->customer) }}" class="hover:underline">{{ $order->customer->name }}</a>
                            • {{ $order->created_at->format('M d, Y H:i') }}
                        </div>
                    </div>
                    <span class="rounded-full px-3 py-1 text-xs font-medium {{ $badge($order->status) }}">{{ $order->status->label() }}</span>
                </div>

                @if ($order->message)
                    <p class="mt-2 rounded-md bg-gray-50 dark:bg-gray-800 p-2 text-sm text-gray-700 dark:text-gray-300">{{ $order->message }}</p>
                @endif

                <div class="mt-3 flex flex-wrap gap-2">
                    <a href="{{ route('account.orders.show', $order) }}"
                       class="rounded-md border border-brand-300 px-3 py-1.5 text-xs font-medium text-brand-600 dark:text-brand-400 hover:bg-brand-50 dark:hover:bg-brand-500/10">
                        💬 {{ __('orders.chat_open') }}
                        @if ($order->unread_count)
                            <span class="ml-1 rounded-full bg-brand-500 px-1.5 text-white">{{ $order->unread_count }}</span>
                        @endif
                    </a>
                    @if ($order->status === \App\Enums\OrderStatus::Pending)
                        <form method="POST" action="{{ route('account.orders.confirm', $order) }}">
                            @csrf
                            <button type="submit" class="rounded-md bg-green-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-green-700">
                                {{ __('orders.confirm') }}
                            </button>
                        </form>
                        <form method="POST" action="{{ route('account.orders.decline', $order) }}">
                            @csrf
                            <button type="submit" class="rounded-md border border-red-200 dark:border-red-800 px-3 py-1.5 text-xs font-medium text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/50">
                                {{ __('orders.decline') }}
                            </button>
                        </form>
                    @elseif ($order->status === \App\Enums\OrderStatus::Confirmed)
                        <form method="POST" action="{{ route('account.orders.complete', $order) }}">
                            @csrf
                            <button type="submit" class="rounded-md bg-brand-500 px-3 py-1.5 text-xs font-medium text-white hover:bg-brand-600">
                                {{ __('orders.complete') }}
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        @empty
            <div class="rounded-lg border border-dashed border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 p-8 text-center text-gray-500 dark:text-gray-400">
                {{ __('orders.incoming_empty') }}
            </div>
        @endforelse

        {{ $incoming->links() }}
    </div>

    {{-- OUTGOING --}}
    <div x-show="tab === 'outgoing'" x-cloak class="space-y-3">
        @forelse ($outgoing as $order)
            <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-4">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <a href="{{ $order->listing->seoUrl() }}" class="font-semibold text-brand-600 dark:text-brand-400 hover:underline">
                            {{ $order->listing->title }}
                        </a>
                        <div class="text-sm text-gray-500 dark:text-gray-400">
                            {{ __('orders.owner') }}:
                            <a href="{{ route('profile.show', $order->owner) }}" class="hover:underline">{{ $order->owner->name }}</a>
                            • {{ $order->created_at->format('M d, Y H:i') }}
                        </div>
                    </div>
                    <span class="rounded-full px-3 py-1 text-xs font-medium {{ $badge($order->status) }}">{{ $order->status->label() }}</span>
                </div>

                <div class="mt-3 flex flex-wrap gap-2">
                    <a href="{{ route('account.orders.show', $order) }}"
                       class="rounded-md border border-brand-300 px-3 py-1.5 text-xs font-medium text-brand-600 dark:text-brand-400 hover:bg-brand-50 dark:hover:bg-brand-500/10">
                        💬 {{ __('orders.chat_open') }}
                        @if ($order->unread_count)
                            <span class="ml-1 rounded-full bg-brand-500 px-1.5 text-white">{{ $order->unread_count }}</span>
                        @endif
                    </a>
                    @if ($order->awaitsPayment())
                        <a href="{{ route('account.orders.show', $order) }}"
                           class="rounded-md bg-brand-500 px-3 py-1.5 text-xs font-medium text-white hover:bg-brand-600">
                            💳 {{ __('orders.pay_now', ['amount' => number_format((float) $order->payment_amount, 2)]) }}
                        </a>
                    @elseif ($order->isPaid())
                        <span class="rounded-md bg-green-100 dark:bg-green-900/50 px-3 py-1.5 text-xs font-medium text-green-800 dark:text-green-200">✓ {{ __('orders.paid') }}</span>
                    @endif
                    @if ($order->status === \App\Enums\OrderStatus::Pending)
                        <form method="POST" action="{{ route('account.orders.cancel', $order) }}">
                            @csrf
                            <button type="submit" class="rounded-md border border-gray-300 dark:border-gray-600 px-3 py-1.5 text-xs font-medium hover:bg-gray-50 dark:hover:bg-gray-800">
                                {{ __('orders.cancel') }}
                            </button>
                        </form>
                    @elseif ($order->isReviewable())
                        <a href="{{ route('account.reviews.create', $order) }}"
                           class="rounded-md bg-brand-500 px-3 py-1.5 text-xs font-medium text-white hover:bg-brand-600">
                            {{ __('orders.leave_review') }}
                        </a>
                    @elseif ($order->review)
                        <span class="text-xs text-gray-500 dark:text-gray-400">✓ {{ __('account.review_exists') }}</span>
                    @endif
                </div>
            </div>
        @empty
            <div class="rounded-lg border border-dashed border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 p-8 text-center text-gray-500 dark:text-gray-400">
                {{ __('orders.outgoing_empty') }}
            </div>
        @endforelse

        {{ $outgoing->links() }}
    </div>
</div>
@endsection
