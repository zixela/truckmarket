<div x-data="{ open: false }" class="relative">
    <button type="button" @click="open = !open" @click.outside="open = false"
            class="relative rounded-md px-2 py-2 hover:bg-gray-100" aria-label="{{ __('common.notifications') }}">
        🔔
        @if ($total > 0)
            <span class="absolute -right-0.5 -top-0.5 flex h-5 min-w-5 items-center justify-center rounded-full bg-red-500 px-1 text-[11px] font-bold text-white">
                {{ $total > 99 ? '99+' : $total }}
            </span>
        @endif
    </button>

    <div x-show="open" x-cloak x-transition.opacity
         class="absolute right-0 z-40 mt-2 w-72 rounded-lg border border-gray-200 bg-white p-2 shadow-lg">
        @if ($total === 0)
            <p class="px-3 py-4 text-center text-sm text-gray-400">{{ __('common.notifications_empty') }}</p>
        @else
            @if ($newOrders > 0)
                <a href="{{ route('account.orders.index') }}"
                   class="flex items-center justify-between rounded-md px-3 py-2 text-sm hover:bg-gray-50">
                    <span>📦 {{ __('common.notifications_new_orders') }}</span>
                    <span class="rounded-full bg-red-500 px-2 py-0.5 text-xs font-bold text-white">{{ $newOrders }}</span>
                </a>
            @endif

            @if ($unreadMessages > 0)
                <a href="{{ route('account.orders.index') }}"
                   class="flex items-center justify-between rounded-md px-3 py-2 text-sm hover:bg-gray-50">
                    <span>💬 {{ __('common.notifications_unread_messages') }}</span>
                    <span class="rounded-full bg-red-500 px-2 py-0.5 text-xs font-bold text-white">{{ $unreadMessages }}</span>
                </a>
            @endif
        @endif
    </div>
</div>
