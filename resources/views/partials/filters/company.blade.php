@php $filters = $filters ?? []; @endphp

<label class="block text-sm">
    <span class="mb-1 block font-medium">{{ __('listings.fields.company_name') }}</span>
    <input type="text" name="company_name" value="{{ $filters['company_name'] ?? '' }}" placeholder="Vmoon Corporation"
           class="w-full rounded-md border border-gray-300 dark:border-gray-600 px-3 py-2">
</label>

@include('partials.filters._shared', ['filters' => $filters])
