@php
    $filters = $filters ?? [];
    $serviceCategories = \App\Models\ServiceCategory::query()->active()->ordered()->get();
@endphp

<label class="block text-sm">
    <span class="mb-1 block font-medium">{{ __('listings.fields.service_category') }}</span>
    <select name="service_category_id" class="w-full rounded-md border border-gray-300 px-3 py-2">
        <option value="">{{ __('common.any') }}</option>
        @foreach ($serviceCategories as $category)
            <option value="{{ $category->id }}" @selected((string) ($filters['service_category_id'] ?? '') === (string) $category->id)>
                {{ $category->name() }}
            </option>
        @endforeach
    </select>
</label>

@include('partials.filters._shared', ['filters' => $filters])
