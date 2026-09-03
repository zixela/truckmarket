@extends('account._layout')

@php
    $me = auth()->user();
    $other = $order->otherParty($me);
    $badge = match ($order->status->value) {
        'pending' => 'bg-yellow-100 dark:bg-yellow-900/50 text-yellow-800 dark:text-yellow-200',
        'confirmed' => 'bg-blue-100 dark:bg-blue-900/50 text-blue-800 dark:text-blue-200',
        'completed' => 'bg-green-100 dark:bg-green-900/50 text-green-800 dark:text-green-200',
        default => 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400',
    };
@endphp

@section('account-content')
<div class="flex flex-wrap items-center justify-between gap-2">
    <div>
        <a href="{{ route('account.orders.index') }}" class="text-sm text-brand-600 dark:text-brand-400 hover:underline">← {{ __('orders.my_orders') }}</a>
        <h2 class="text-lg font-semibold">
            <a href="{{ $order->listing->seoUrl() }}" class="hover:underline">{{ $order->listing->title }}</a>
        </h2>
        <div class="text-sm text-gray-500 dark:text-gray-400">
            {{ __('orders.chat_with') }}:
            <a href="{{ route('profile.show', $other) }}" class="font-medium text-brand-600 dark:text-brand-400 hover:underline">{{ $other->name }}</a>
            • {{ $order->created_at->format('M d, Y H:i') }}
        </div>
    </div>
    <div class="flex items-center gap-2">
        @if ($order->isPaid())
            <span class="rounded-full bg-green-100 dark:bg-green-900/50 px-3 py-1 text-xs font-medium text-green-800 dark:text-green-200">
                ✓ {{ __('orders.paid') }} (${{ number_format((float) $order->payment_amount, 2) }})
            </span>
        @elseif ($order->awaitsPayment())
            <span class="rounded-full bg-orange-100 dark:bg-orange-900/50 px-3 py-1 text-xs font-medium text-orange-800 dark:text-orange-200">
                {{ __('orders.payment_pending') }}
            </span>
        @endif
        <span class="rounded-full px-3 py-1 text-xs font-medium {{ $badge }}">{{ $order->status->label() }}</span>
    </div>
</div>

@if ($order->awaitsPayment() && $me->id === $order->customer_id)
    <form method="POST" action="{{ route('account.orders.pay', $order) }}"
          class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-orange-200 dark:border-orange-800 bg-orange-50 dark:bg-orange-950/50 p-4">
        @csrf
        <div class="text-sm text-orange-900 dark:text-orange-100">
            {{ __('orders.payment_request', ['amount' => number_format((float) $order->payment_amount, 2)]) }}
        </div>
        <button type="submit" class="rounded-md bg-brand-500 px-5 py-2 text-sm font-medium text-white hover:bg-brand-600">
            💳 {{ __('orders.pay_now', ['amount' => number_format((float) $order->payment_amount, 2)]) }}
        </button>
    </form>
@endif

@if ($order->message)
    <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 p-3 text-sm text-gray-700 dark:text-gray-300">
        <span class="font-medium">{{ $order->customer->name }}:</span> {{ $order->message }}
    </div>
@endif

{{-- Thread --}}
<div class="space-y-3 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-4">
    @forelse ($order->messages as $message)
        @php $mine = $message->sender_id === $me->id; @endphp
        <div id="msg-{{ $message->id }}" class="flex items-end gap-1 {{ $mine ? 'justify-end' : 'justify-start' }}">
            @if ($mine && $message->liked_at)
                <span class="text-sm" title="{{ __('orders.chat_liked') }}" aria-label="{{ __('orders.chat_liked') }}">❤️</span>
            @endif
            <div class="max-w-[80%] rounded-lg px-3 py-2 text-sm {{ $mine ? 'bg-brand-500 text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-200' }}">
                <p class="whitespace-pre-line">{{ $message->body }}</p>
                <div class="mt-1 text-[11px] {{ $mine ? 'text-brand-100' : 'text-gray-400 dark:text-gray-500' }}">
                    {{ $message->created_at->format('M d, H:i') }}
                    @if ($mine)
                        @if ($message->read_at)
                            <span title="{{ $message->read_at->format('M d, H:i') }}">· ✓✓ {{ __('orders.chat_read') }}</span>
                        @else
                            <span>· ✓ {{ __('orders.chat_sent') }}</span>
                        @endif
                    @endif
                </div>
            </div>
            @if (! $mine && $order->allowsMessages())
                <form method="POST" action="{{ route('account.orders.messages.like', [$order, $message]) }}">
                    @csrf
                    <button type="submit"
                            class="rounded-md px-1.5 py-1 text-sm hover:bg-gray-100 dark:hover:bg-gray-800"
                            aria-pressed="{{ $message->liked_at ? 'true' : 'false' }}"
                            aria-label="{{ $message->liked_at ? __('orders.chat_unlike') : __('orders.chat_like') }}"
                            title="{{ $message->liked_at ? __('orders.chat_unlike') : __('orders.chat_like') }}">
                        {{ $message->liked_at ? '❤️' : '🤍' }}
                    </button>
                </form>
            @elseif (! $mine && $message->liked_at)
                <span class="text-sm" title="{{ __('orders.chat_liked') }}">❤️</span>
            @endif
        </div>
    @empty
        <p class="py-6 text-center text-sm text-gray-400 dark:text-gray-500">{{ __('orders.chat_empty') }}</p>
    @endforelse
    <span id="latest"></span>
</div>

{{-- Composer --}}
@if ($order->allowsMessages())
    @php $emojis = ['👍', '❤️', '😀', '😂', '🙏', '👌', '🔥', '😢']; @endphp
    <form method="POST" action="{{ route('account.orders.messages.store', $order) }}" class="space-y-2"
          x-data="{ insert(emoji) {
              const el = $refs.body, start = el.selectionStart, end = el.selectionEnd;
              el.value = el.value.slice(0, start) + emoji + el.value.slice(end);
              el.focus();
              el.setSelectionRange(start + emoji.length, start + emoji.length);
          } }">
        @csrf
        <div class="flex items-start gap-2">
            <textarea name="body" rows="2" required maxlength="2000" x-ref="body"
                      placeholder="{{ __('orders.chat_placeholder') }}"
                      class="w-full rounded-md border border-gray-300 dark:border-gray-600 px-3 py-2 text-sm">{{ old('body') }}</textarea>
            <button type="submit" class="rounded-md bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600">
                {{ __('orders.chat_send') }}
            </button>
        </div>
        <div class="flex flex-wrap gap-1" role="group" aria-label="{{ __('orders.chat_emoji') }}">
            @foreach ($emojis as $emoji)
                <button type="button" @click="insert('{{ $emoji }}')"
                        class="rounded-md px-2 py-1 text-lg leading-none hover:bg-gray-100 dark:hover:bg-gray-800">{{ $emoji }}</button>
            @endforeach
        </div>
    </form>
@else
    <p class="rounded-md border border-dashed border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 p-3 text-center text-sm text-gray-500 dark:text-gray-400">
        {{ __('orders.chat_closed') }}
    </p>
@endif
@endsection
