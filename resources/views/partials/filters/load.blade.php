@php $filters = $filters ?? []; @endphp

<div class="grid gap-4 @lg:grid-cols-3">
    <label class="block text-sm">
        <span class="mb-1 block font-medium">{{ __('listings.fields.load_type') }}</span>
        <select name="load_type" class="w-full rounded-md border border-gray-300 dark:border-gray-600 px-3 py-2">
            <option value="">{{ __('common.any') }}</option>
            @foreach (['car_hauler', 'flatbed', 'reefer', 'dry_van'] as $lt)
                <option value="{{ $lt }}" @selected(($filters['load_type'] ?? '') === $lt)>{{ __('listings.load_types.'.$lt) }}</option>
            @endforeach
        </select>
    </label>

    <label class="block text-sm">
        <span class="mb-1 block font-medium">{{ __('listings.fields.pickup_zip') }}</span>
        <input type="text" name="pickup_zip" value="{{ $filters['pickup_zip'] ?? '' }}" placeholder="10001"
               class="w-full rounded-md border border-gray-300 dark:border-gray-600 px-3 py-2">
    </label>

    <label class="block text-sm">
        <span class="mb-1 block font-medium">{{ __('listings.fields.delivery_zip') }}</span>
        <input type="text" name="delivery_zip" value="{{ $filters['delivery_zip'] ?? '' }}" placeholder="90001"
               class="w-full rounded-md border border-gray-300 dark:border-gray-600 px-3 py-2">
    </label>
</div>

<div class="grid gap-4 @lg:grid-cols-3">
    <label class="block text-sm">
        <span class="mb-1 block font-medium">{{ __('listings.fields.vehicle_type') }}</span>
        <select name="vehicle_type" class="w-full rounded-md border border-gray-300 dark:border-gray-600 px-3 py-2">
            <option value="">{{ __('common.any') }}</option>
            @foreach (['sedan', 'suv', 'truck'] as $vt)
                <option value="{{ $vt }}" @selected(($filters['vehicle_type'] ?? '') === $vt)>{{ __('listings.vehicle_types.'.$vt) }}</option>
            @endforeach
        </select>
    </label>

    <div class="text-sm sm:col-span-2">
        <span class="mb-1 block font-medium">{{ __('listings.fields.weight_range') }}</span>
        <div class="flex gap-2">
            <input type="number" name="weight_min" value="{{ $filters['weight_min'] ?? '' }}" placeholder="{{ __('common.min') }}"
                   class="w-full min-w-0 rounded-md border border-gray-300 dark:border-gray-600 px-3 py-2">
            <input type="number" name="weight_max" value="{{ $filters['weight_max'] ?? '' }}" placeholder="{{ __('common.max') }}"
                   class="w-full min-w-0 rounded-md border border-gray-300 dark:border-gray-600 px-3 py-2">
        </div>
    </div>
</div>

@include('partials.filters._shared', ['filters' => $filters])
