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
        <span class="mb-1 block font-medium">{{ __('listings.fields.employment_type') }}</span>
        <select name="employment_type" class="w-full rounded-md border border-gray-300 dark:border-gray-600 px-3 py-2">
            <option value="">{{ __('common.any') }}</option>
            @foreach (['full_time', 'part_time'] as $et)
                <option value="{{ $et }}" @selected(($filters['employment_type'] ?? '') === $et)>{{ __('listings.employment_types.'.$et) }}</option>
            @endforeach
        </select>
    </label>
</div>

<div class="text-sm">
    <span class="mb-1 block font-medium">{{ __('listings.fields.languages') }}</span>
    <div class="flex flex-wrap gap-2">
        @foreach (['english_a1', 'english_b2', 'english_c1', 'russian', 'georgian'] as $lang)
            <label class="cursor-pointer rounded-full border border-gray-300 dark:border-gray-600 px-3 py-1 text-xs has-[:checked]:border-brand-500 has-[:checked]:bg-brand-50 has-[:checked]:text-brand-700">
                <input type="checkbox" name="languages[]" value="{{ $lang }}" class="sr-only"
                       @checked(in_array($lang, (array) ($filters['languages'] ?? []), true))>
                {{ __('listings.languages.'.$lang) }}
            </label>
        @endforeach
    </div>
    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('listings.multi_language_hint') }}</p>
</div>

@include('partials.filters._shared', ['filters' => $filters])
