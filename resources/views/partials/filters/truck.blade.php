@php $filters = $filters ?? []; @endphp

<div class="grid gap-4 @lg:grid-cols-3">
    <label class="block text-sm">
        <span class="mb-1 block font-medium">{{ __('listings.fields.deal') }}</span>
        <select name="deal" class="w-full rounded-md border border-gray-300 px-3 py-2">
            <option value="both">{{ __('listings.deals.both') }}</option>
            <option value="sell" @selected(($filters['deal'] ?? '') === 'sell')>{{ __('listings.deals.sell') }}</option>
            <option value="rent" @selected(($filters['deal'] ?? '') === 'rent')>{{ __('listings.deals.rent') }}</option>
        </select>
    </label>

    <label class="block text-sm">
        <span class="mb-1 block font-medium">{{ __('listings.fields.make_model') }}</span>
        <input type="text" name="make_model" value="{{ $filters['make_model'] ?? '' }}" placeholder="Freightliner Cascadia"
               class="w-full rounded-md border border-gray-300 px-3 py-2">
    </label>

    <label class="block text-sm">
        <span class="mb-1 block font-medium">{{ __('listings.fields.cab_type') }}</span>
        <select name="cab_type" class="w-full rounded-md border border-gray-300 px-3 py-2">
            <option value="">{{ __('common.any') }}</option>
            @foreach (['sleeper', 'day_cab'] as $cab)
                <option value="{{ $cab }}" @selected(($filters['cab_type'] ?? '') === $cab)>{{ __('listings.cab_types.'.$cab) }}</option>
            @endforeach
        </select>
    </label>
</div>

@include('partials.filters._shared', ['filters' => $filters])

<div class="grid gap-4 @lg:grid-cols-2">
    <div class="text-sm">
        <span class="mb-1 block font-medium">{{ __('listings.fields.year_range') }}</span>
        <div class="flex gap-2">
            <input type="number" name="year_min" value="{{ $filters['year_min'] ?? '' }}" placeholder="{{ __('common.min') }}"
                   class="w-full min-w-0 rounded-md border border-gray-300 px-3 py-2">
            <input type="number" name="year_max" value="{{ $filters['year_max'] ?? '' }}" placeholder="{{ __('common.max') }}"
                   class="w-full min-w-0 rounded-md border border-gray-300 px-3 py-2">
        </div>
    </div>

    <div class="text-sm">
        <span class="mb-1 block font-medium">{{ __('listings.fields.mileage_range') }}</span>
        <div class="flex gap-2">
            <input type="number" name="mileage_min" value="{{ $filters['mileage_min'] ?? '' }}" placeholder="{{ __('common.min') }}"
                   class="w-full min-w-0 rounded-md border border-gray-300 px-3 py-2">
            <input type="number" name="mileage_max" value="{{ $filters['mileage_max'] ?? '' }}" placeholder="{{ __('common.max') }}"
                   class="w-full min-w-0 rounded-md border border-gray-300 px-3 py-2">
        </div>
    </div>
</div>
