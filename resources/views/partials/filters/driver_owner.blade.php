@php $filters = $filters ?? []; @endphp

<div class="grid gap-4 @lg:grid-cols-2">
    <label class="block text-sm">
        <span class="mb-1 block font-medium">{{ __('listings.fields.experience') }}</span>
        <select name="experience" class="w-full rounded-md border border-gray-300 dark:border-gray-600 px-3 py-2">
            <option value="">{{ __('listings.experience.any') }}</option>
            @foreach ([1, 3, 5] as $exp)
                <option value="{{ $exp }}" @selected((string) ($filters['experience'] ?? '') === (string) $exp)>{{ __('listings.experience.'.$exp) }}</option>
            @endforeach
        </select>
    </label>

    <label class="block text-sm">
        <span class="mb-1 block font-medium">{{ __('listings.fields.cdl_class') }}</span>
        <select name="cdl_class" class="w-full rounded-md border border-gray-300 dark:border-gray-600 px-3 py-2">
            <option value="">{{ __('common.any') }}</option>
            @foreach (['a', 'b'] as $cdl)
                <option value="{{ $cdl }}" @selected(($filters['cdl_class'] ?? '') === $cdl)>{{ __('listings.cdl_classes.'.$cdl) }}</option>
            @endforeach
        </select>
    </label>
</div>

@include('partials.filters._shared', ['filters' => $filters])
