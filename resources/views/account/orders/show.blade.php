@extends('account._layout')

@php
    $me = auth()->user();
    $other = $order->otherParty($me);
    $badge = match ($order->status->value) {
        'pending' => 'bg-yellow-100 text-yellow-800',
        'confirmed' => 'bg-blue-100 text-blue-800',
        'completed' => 'bg-green-100 text-green-800',
        default => 'bg-gray-100 text-gray-600',
    };
@endphp

@section('account-content')
<div class="flex flex-wrap items-center justify-between gap-2">
    <div>
        <a href="{{ route('account.orders.index') }}" class="text-sm text-brand-600 hover:underline">← {{ __('orders.my_orders') }}</a>
        <h2 class="text-lg font-semibold">
            <a href="{{ $order->listing->seoUrl() }}" class="hover:underline">{{ $order->listing->title }}</a>
        </h2>
        <div class="text-sm text-gray-500">
            {{ __('orders.chat_with') }}:
            <a href="{{ route('profile.show', $other) }}" class="font-medium text-brand-600 hover:underline">{{ $other->name }}</a>
            • {{ $order->created_at->format('M d, Y H:i') }}
        </div>
    </div>
    <div class="flex items-center gap-2">
        @if ($order->isPaid())
            <span class="rounded-full bg-green-100 px-3 py-1 text-xs font-medium text-green-800">
                ✓ {{ __('orders.paid') }} (${{ number_format((float) $order->payment_amount, 2) }})
            </span>
        @elseif ($order->awaitsPayment())
            <span class="rounded-full bg-orange-100 px-3 py-1 text-xs font-medium text-orange-800">
                {{ __('orders.payment_pending') }}
            </span>
        @endif
        <span class="rounded-full px-3 py-1 text-xs font-medium {{ $badge }}">{{ $order->status->label() }}</span>
    </div>
</div>

@if ($order->awaitsPayment() && $me->id === $order->customer_id)
    <form method="POST" action="{{ route('account.orders.pay', $order) }}"
          class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-orange-200 bg-orange-50 p-4">
        @csrf
        <div class="text-sm text-orange-900">
            {{ __('orders.payment_request', ['amount' => number_format((float) $order->payment_amount, 2)]) }}
        </div>
        <button type="submit" class="rounded-md bg-brand-500 px-5 py-2 text-sm font-medium text-white hover:bg-brand-600">
            💳 {{ __('orders.pay_now', ['amount' => number_format((float) $order->payment_amount, 2)]) }}
        </button>
    </form>
@endif

@if ($order->message)
    <div class="rounded-lg border border-gray-200 bg-gray-50 p-3 text-sm text-gray-700">
        <span class="font-medium">{{ $order->customer->name }}:</span> {{ $order->message }}
    </div>
@endif

{{-- Thread --}}
<div class="space-y-3 rounded-lg border border-gray-200 bg-white p-4">
    @forelse ($order->messages as $message)
        <div class="flex {{ $message->sender_id === $me->id ? 'justify-end' : 'justify-start' }}">
            <div class="max-w-[80%] rounded-lg px-3 py-2 text-sm {{ $message->sender_id === $me->id ? 'bg-brand-500 text-white' : 'bg-gray-100 text-gray-800' }}">
                <p class="whitespace-pre-line">{{ $message->body }}</p>
                <div class="mt-1 text-[11px] {{ $message->sender_id === $me->id ? 'text-brand-100' : 'text-gray-400' }}">
                    {{ $message->created_at->format('M d, H:i') }}
                    @if ($message->sender_id === $me->id && $message->read_at)
                        ✓✓
                    @endif
                </div>
            </div>
        </div>
    @empty
        <p class="py-6 text-center text-sm text-gray-400">{{ __('orders.chat_empty') }}</p>
    @endforelse
    <span id="latest"></span>
</div>

{{-- Composer --}}
@if ($order->allowsMessages())
    <form method="POST" action="{{ route('account.orders.messages.store', $order) }}" class="flex items-start gap-2">
        @csrf
        <textarea name="body" rows="2" required maxlength="2000"
                  placeholder="{{ __('orders.chat_placeholder') }}"
                  class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm">{{ old('body') }}</textarea>
        <button type="submit" class="rounded-md bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600">
            {{ __('orders.chat_send') }}
        </button>
    </form>
@else
    <p class="rounded-md border border-dashed border-gray-300 bg-white p-3 text-center text-sm text-gray-500">
        {{ __('orders.chat_closed') }}
    </p>
@endif
@endsection
