@php $filters = $filters ?? []; @endphp

<div class="grid gap-4 @lg:grid-cols-2">
    <label class="block text-sm">
        <span class="mb-1 block font-medium">{{ __('listings.fields.deal') }}</span>
        <select name="deal" class="w-full rounded-md border border-gray-300 px-3 py-2">
            <option value="both">{{ __('listings.deals.both') }}</option>
            <option value="sell" @selected(($filters['deal'] ?? '') === 'sell')>{{ __('listings.deals.sell') }}</option>
            <option value="rent" @selected(($filters['deal'] ?? '') === 'rent')>{{ __('listings.deals.rent') }}</option>
        </select>
    </label>

    <label class="block text-sm">
        <span class="mb-1 block font-medium">{{ __('listings.fields.trailer_type') }}</span>
        <select name="trailer_type" class="w-full rounded-md border border-gray-300 px-3 py-2">
            <option value="">{{ __('common.any') }}</option>
            @foreach (['flatbed', 'reefer', 'dry_van'] as $tt)
                <option value="{{ $tt }}" @selected(($filters['trailer_type'] ?? '') === $tt)>{{ __('listings.trailer_types.'.$tt) }}</option>
            @endforeach
        </select>
    </label>
</div>

@include('partials.filters._shared', ['filters' => $filters])
