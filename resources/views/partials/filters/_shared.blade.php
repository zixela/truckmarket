{{-- Shared filter inputs: ZIP + radius + price range --}}
@props(['filters' => []])

<div class="grid gap-4 @lg:grid-cols-3">
    <label class="block text-sm">
        <span class="mb-1 block font-medium">{{ __('listings.fields.zip') }}</span>
        <input type="text" name="zip" value="{{ $filters['zip'] ?? '' }}" placeholder="10001"
               class="w-full rounded-md border border-gray-300 dark:border-gray-600 px-3 py-2">
    </label>

    <label class="block text-sm">
        <span class="mb-1 block font-medium">{{ __('listings.fields.radius') }}</span>
        <input type="number" name="radius" min="0" max="2000" value="{{ $filters['radius'] ?? 50 }}"
               class="w-full rounded-md border border-gray-300 dark:border-gray-600 px-3 py-2">
    </label>

    <div class="text-sm">
        <span class="mb-1 block font-medium">{{ __('listings.fields.price_range') }}</span>
        <div class="flex gap-2">
            <input type="number" name="price_min" value="{{ $filters['price_min'] ?? '' }}" placeholder="{{ __('common.min') }}"
                   class="w-full min-w-0 rounded-md border border-gray-300 dark:border-gray-600 px-3 py-2">
            <input type="number" name="price_max" value="{{ $filters['price_max'] ?? '' }}" placeholder="{{ __('common.max') }}"
                   class="w-full min-w-0 rounded-md border border-gray-300 dark:border-gray-600 px-3 py-2">
        </div>
    </div>
</div>
