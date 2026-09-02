@extends('account._layout')

@php
    $badge = fn ($status) => match ($status->value) {
        'pending' => 'bg-yellow-100 text-yellow-800',
        'confirmed' => 'bg-blue-100 text-blue-800',
        'completed' => 'bg-green-100 text-green-800',
        default => 'bg-gray-100 text-gray-600',
    };
@endphp

@section('account-content')
<div x-data="{ tab: '{{ request('outgoing') ? 'outgoing' : 'incoming' }}' }" class="space-y-4">
    <div class="flex gap-2">
        <button @click="tab = 'incoming'" :class="tab === 'incoming' ? 'bg-brand-500 text-white' : 'bg-white hover:bg-gray-50'"
                class="rounded-md border border-gray-200 px-4 py-2 text-sm font-medium">
            {{ __('orders.incoming') }} ({{ $incoming->total() }})
        </button>
        <button @click="tab = 'outgoing'" :class="tab === 'outgoing' ? 'bg-brand-500 text-white' : 'bg-white hover:bg-gray-50'"
                class="rounded-md border border-gray-200 px-4 py-2 text-sm font-medium">
            {{ __('orders.outgoing') }} ({{ $outgoing->total() }})
        </button>
    </div>

    {{-- INCOMING --}}
    <div x-show="tab === 'incoming'" x-cloak class="space-y-3">
        @forelse ($incoming as $order)
            <div class="rounded-lg border border-gray-200 bg-white p-4">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <a href="{{ $order->listing->seoUrl() }}" class="font-semibold text-brand-600 hover:underline">
                            {{ $order->listing->title }}
                        </a>
                        <div class="text-sm text-gray-500">
                            {{ __('orders.customer') }}:
                            <a href="{{ route('profile.show', $order->customer) }}" class="hover:underline">{{ $order->customer->name }}</a>
                            • {{ $order->created_at->format('M d, Y H:i') }}
                        </div>
                    </div>
                    <span class="rounded-full px-3 py-1 text-xs font-medium {{ $badge($order->status) }}">{{ $order->status->label() }}</span>
                </div>

                @if ($order->message)
                    <p class="mt-2 rounded-md bg-gray-50 p-2 text-sm text-gray-700">{{ $order->message }}</p>
                @endif

                <div class="mt-3 flex flex-wrap gap-2">
                    <a href="{{ route('account.orders.show', $order) }}"
                       class="rounded-md border border-brand-300 px-3 py-1.5 text-xs font-medium text-brand-600 hover:bg-brand-50">
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
                            <button type="submit" class="rounded-md border border-red-200 px-3 py-1.5 text-xs font-medium text-red-600 hover:bg-red-50">
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
            <div class="rounded-lg border border-dashed border-gray-300 bg-white p-8 text-center text-gray-500">
                {{ __('orders.incoming_empty') }}
            </div>
        @endforelse

        {{ $incoming->links() }}
    </div>

    {{-- OUTGOING --}}
    <div x-show="tab === 'outgoing'" x-cloak class="space-y-3">
        @forelse ($outgoing as $order)
            <div class="rounded-lg border border-gray-200 bg-white p-4">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <a href="{{ $order->listing->seoUrl() }}" class="font-semibold text-brand-600 hover:underline">
                            {{ $order->listing->title }}
                        </a>
                        <div class="text-sm text-gray-500">
                            {{ __('orders.owner') }}:
                            <a href="{{ route('profile.show', $order->owner) }}" class="hover:underline">{{ $order->owner->name }}</a>
                            • {{ $order->created_at->format('M d, Y H:i') }}
                        </div>
                    </div>
                    <span class="rounded-full px-3 py-1 text-xs font-medium {{ $badge($order->status) }}">{{ $order->status->label() }}</span>
                </div>

                <div class="mt-3 flex flex-wrap gap-2">
                    <a href="{{ route('account.orders.show', $order) }}"
                       class="rounded-md border border-brand-300 px-3 py-1.5 text-xs font-medium text-brand-600 hover:bg-brand-50">
                        💬 {{ __('orders.chat_open') }}
                        @if ($order->unread_count)
                            <span class="ml-1 rounded-full bg-brand-500 px-1.5 text-white">{{ $order->unread_count }}</span>
                        @endif
                    </a>
                    @if ($order->status === \App\Enums\OrderStatus::Pending)
                        <form method="POST" action="{{ route('account.orders.cancel', $order) }}">
                            @csrf
                            <button type="submit" class="rounded-md border border-gray-300 px-3 py-1.5 text-xs font-medium hover:bg-gray-50">
                                {{ __('orders.cancel') }}
                            </button>
                        </form>
                    @elseif ($order->isReviewable())
                        <a href="{{ route('account.reviews.create', $order) }}"
                           class="rounded-md bg-brand-500 px-3 py-1.5 text-xs font-medium text-white hover:bg-brand-600">
                            {{ __('orders.leave_review') }}
                        </a>
                    @elseif ($order->review)
                        <span class="text-xs text-gray-500">✓ {{ __('account.review_exists') }}</span>
                    @endif
                </div>
            </div>
        @empty
            <div class="rounded-lg border border-dashed border-gray-300 bg-white p-8 text-center text-gray-500">
                {{ __('orders.outgoing_empty') }}
            </div>
        @endforelse

        {{ $outgoing->links() }}
    </div>
</div>
@endsection
