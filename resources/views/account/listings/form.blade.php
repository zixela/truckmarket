@extends('account._layout')

@php
    $editing = $listing !== null;
    $detail = $detail ?? null;
    $action = $editing ? route('account.listings.update', $listing) : route('account.listings.store');
    $old = fn (string $key, $default = null) => old($key, $default);
@endphp

@section('account-content')
<h2 class="text-lg font-semibold">{{ $editing ? __('listings.edit') : __('listings.add') }}</h2>

<form method="POST" action="{{ $action }}" enctype="multipart/form-data"
      x-data="{ type: '{{ old('type', $type->value) }}' }"
      class="space-y-4 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-4">
    @csrf
    @if ($editing) @method('PUT') @endif

    <label class="block text-sm">
        <span class="mb-1 block font-medium">{{ __('listings.fields.category') }}</span>
        <select name="type" x-model="type" @disabled($editing) class="w-full rounded-md border border-gray-300 dark:border-gray-600 px-3 py-2 disabled:bg-gray-100 dark:disabled:bg-gray-800">
            @foreach ($types as $t)
                <option value="{{ $t->value }}">{{ $t->icon() }} {{ $t->label() }}</option>
            @endforeach
        </select>
        @if ($editing)
            <input type="hidden" name="type" value="{{ $type->value }}">
        @endif
    </label>

    <label class="block text-sm">
        <span class="mb-1 block font-medium">{{ __('listings.fields.title') }}</span>
        <input type="text" name="title" required value="{{ $old('title', $listing?->title) }}"
               class="w-full rounded-md border border-gray-300 dark:border-gray-600 px-3 py-2">
    </label>

    {{-- TRUCK --}}
    <div x-show="type === 'truck'" x-cloak class="space-y-4">
        <div class="grid gap-4 sm:grid-cols-2">
            <label class="block text-sm">
                <span class="mb-1 block font-medium">{{ __('listings.fields.deal') }}</span>
                <select name="deal" class="w-full rounded-md border border-gray-300 dark:border-gray-600 px-3 py-2" :disabled="type !== 'truck'">
                    <option value="sell" @selected($old('deal', $detail?->deal?->value) === 'sell')>{{ __('listings.deals.sell') }}</option>
                    <option value="rent" @selected($old('deal', $detail?->deal?->value) === 'rent')>{{ __('listings.deals.rent') }}</option>
                </select>
            </label>
            <label class="block text-sm">
                <span class="mb-1 block font-medium">{{ __('listings.fields.make_model') }}</span>
                <input type="text" name="make_model" value="{{ $old('make_model', $detail?->make_model) }}" placeholder="Freightliner Cascadia"
                       class="w-full rounded-md border border-gray-300 dark:border-gray-600 px-3 py-2" :disabled="type !== 'truck'">
            </label>
        </div>
        <div class="grid gap-4 sm:grid-cols-3">
            <label class="block text-sm">
                <span class="mb-1 block font-medium">{{ __('listings.fields.cab_type') }}</span>
                <select name="cab_type" class="w-full rounded-md border border-gray-300 dark:border-gray-600 px-3 py-2" :disabled="type !== 'truck'">
                    <option value="">—</option>
                    <option value="sleeper" @selected($old('cab_type', $detail?->cab_type) === 'sleeper')>{{ __('listings.cab_types.sleeper') }}</option>
                    <option value="day_cab" @selected($old('cab_type', $detail?->cab_type) === 'day_cab')>{{ __('listings.cab_types.day_cab') }}</option>
                </select>
            </label>
            <label class="block text-sm">
                <span class="mb-1 block font-medium">{{ __('listings.fields.year') }}</span>
                <input type="number" name="year" value="{{ $old('year', $detail?->year) }}" placeholder="2020"
                       class="w-full rounded-md border border-gray-300 dark:border-gray-600 px-3 py-2" :disabled="type !== 'truck'">
            </label>
            <label class="block text-sm">
                <span class="mb-1 block font-medium">{{ __('listings.fields.mileage') }}</span>
                <input type="number" name="mileage" value="{{ $old('mileage', $detail?->mileage) }}" placeholder="500000"
                       class="w-full rounded-md border border-gray-300 dark:border-gray-600 px-3 py-2" :disabled="type !== 'truck'">
            </label>
        </div>
    </div>

    {{-- TRAILER --}}
    <div x-show="type === 'trailer'" x-cloak class="space-y-4">
        <div class="grid gap-4 sm:grid-cols-2">
            <label class="block text-sm">
                <span class="mb-1 block font-medium">{{ __('listings.fields.deal') }}</span>
                <select name="deal" class="w-full rounded-md border border-gray-300 dark:border-gray-600 px-3 py-2" :disabled="type !== 'trailer'">
                    <option value="sell" @selected($old('deal', $detail?->deal?->value) === 'sell')>{{ __('listings.deals.sell') }}</option>
                    <option value="rent" @selected($old('deal', $detail?->deal?->value) === 'rent')>{{ __('listings.deals.rent') }}</option>
                </select>
            </label>
            <label class="block text-sm">
                <span class="mb-1 block font-medium">{{ __('listings.fields.trailer_type') }}</span>
                <select name="trailer_type" class="w-full rounded-md border border-gray-300 dark:border-gray-600 px-3 py-2" :disabled="type !== 'trailer'">
                    <option value="flatbed" @selected($old('trailer_type', $detail?->trailer_type) === 'flatbed')>{{ __('listings.trailer_types.flatbed') }}</option>
                    <option value="reefer" @selected($old('trailer_type', $detail?->trailer_type) === 'reefer')>{{ __('listings.trailer_types.reefer') }}</option>
                    <option value="dry_van" @selected($old('trailer_type', $detail?->trailer_type) === 'dry_van')>{{ __('listings.trailer_types.dry_van') }}</option>
                </select>
            </label>
        </div>
    </div>

    {{-- LOAD --}}
    <div x-show="type === 'load'" x-cloak class="space-y-4">
        <div class="grid gap-4 sm:grid-cols-3">
            <label class="block text-sm">
                <span class="mb-1 block font-medium">{{ __('listings.fields.load_type') }}</span>
                <select name="load_type" class="w-full rounded-md border border-gray-300 dark:border-gray-600 px-3 py-2" :disabled="type !== 'load'">
                    @foreach (['car_hauler', 'flatbed', 'reefer', 'dry_van'] as $lt)
                        <option value="{{ $lt }}" @selected($old('load_type', $detail?->load_type) === $lt)>{{ __('listings.load_types.'.$lt) }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block text-sm">
                <span class="mb-1 block font-medium">{{ __('listings.fields.pickup_zip') }}</span>
                <input type="text" name="pickup_zip" value="{{ $old('pickup_zip', $detail?->pickup_zip) }}" placeholder="10001"
                       class="w-full rounded-md border border-gray-300 dark:border-gray-600 px-3 py-2" :disabled="type !== 'load'">
            </label>
            <label class="block text-sm">
                <span class="mb-1 block font-medium">{{ __('listings.fields.delivery_zip') }}</span>
                <input type="text" name="delivery_zip" value="{{ $old('delivery_zip', $detail?->delivery_zip) }}" placeholder="90001"
                       class="w-full rounded-md border border-gray-300 dark:border-gray-600 px-3 py-2" :disabled="type !== 'load'">
            </label>
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <label class="block text-sm">
                <span class="mb-1 block font-medium">{{ __('listings.fields.vehicle_type') }}</span>
                <select name="vehicle_type" class="w-full rounded-md border border-gray-300 dark:border-gray-600 px-3 py-2" :disabled="type !== 'load'">
                    <option value="">—</option>
                    @foreach (['sedan', 'suv', 'truck'] as $vt)
                        <option value="{{ $vt }}" @selected($old('vehicle_type', $detail?->vehicle_type) === $vt)>{{ __('listings.vehicle_types.'.$vt) }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block text-sm">
                <span class="mb-1 block font-medium">{{ __('listings.fields.weight') }}</span>
                <input type="number" name="weight" value="{{ $old('weight', $detail?->weight) }}"
                       class="w-full rounded-md border border-gray-300 dark:border-gray-600 px-3 py-2" :disabled="type !== 'load'">
            </label>
        </div>
    </div>

    {{-- COMPANY --}}
    <div x-show="type === 'company'" x-cloak class="space-y-4">
        <div class="grid gap-4 sm:grid-cols-2">
            <label class="block text-sm">
                <span class="mb-1 block font-medium">{{ __('listings.fields.company_name') }}</span>
                <input type="text" name="company_name" value="{{ $old('company_name', $detail?->company_name) }}" placeholder="Vmoon Corporation"
                       class="w-full rounded-md border border-gray-300 dark:border-gray-600 px-3 py-2" :disabled="type !== 'company'">
            </label>
            <label class="block text-sm">
                <span class="mb-1 block font-medium">{{ __('listings.fields.services') }}</span>
                <input type="text" name="services" value="{{ $old('services', $detail?->services) }}"
                       class="w-full rounded-md border border-gray-300 dark:border-gray-600 px-3 py-2" :disabled="type !== 'company'">
            </label>
        </div>
    </div>

    {{-- DISPATCHER --}}
    <div x-show="type === 'dispatcher'" x-cloak class="space-y-4">
        <div class="grid gap-4 sm:grid-cols-2">
            <label class="block text-sm">
                <span class="mb-1 block font-medium">{{ __('listings.fields.experience') }}</span>
                <input type="number" name="experience_years" min="0" max="60" value="{{ $old('experience_years', $detail?->experience_years) }}"
                       class="w-full rounded-md border border-gray-300 dark:border-gray-600 px-3 py-2" :disabled="type !== 'dispatcher'">
            </label>
            <label class="block text-sm">
                <span class="mb-1 block font-medium">{{ __('listings.fields.employment_type') }}</span>
                <select name="employment_type" class="w-full rounded-md border border-gray-300 dark:border-gray-600 px-3 py-2" :disabled="type !== 'dispatcher'">
                    <option value="">—</option>
                    <option value="full_time" @selected($old('employment_type', $detail?->employment_type) === 'full_time')>{{ __('listings.employment_types.full_time') }}</option>
                    <option value="part_time" @selected($old('employment_type', $detail?->employment_type) === 'part_time')>{{ __('listings.employment_types.part_time') }}</option>
                </select>
            </label>
        </div>
        <div class="text-sm">
            <span class="mb-1 block font-medium">{{ __('listings.fields.languages') }}</span>
            <div class="flex flex-wrap gap-2">
                @foreach (['english_a1', 'english_b2', 'english_c1', 'russian', 'georgian'] as $lang)
                    <label class="cursor-pointer rounded-full border border-gray-300 dark:border-gray-600 px-3 py-1 text-xs has-[:checked]:border-brand-500 has-[:checked]:bg-brand-50 has-[:checked]:text-brand-700">
                        <input type="checkbox" name="languages[]" value="{{ $lang }}" class="sr-only" :disabled="type !== 'dispatcher'"
                               @checked(in_array($lang, (array) $old('languages', $detail?->languages ?? []), true))>
                        {{ __('listings.languages.'.$lang) }}
                    </label>
                @endforeach
            </div>
        </div>
    </div>

    {{-- DRIVER & OWNER --}}
    <div x-show="type === 'driver_owner'" x-cloak class="space-y-4">
        <div class="grid gap-4 sm:grid-cols-3">
            <label class="block text-sm">
                <span class="mb-1 block font-medium">{{ __('listings.fields.experience') }}</span>
                <input type="number" name="experience_years" min="0" max="60" value="{{ $old('experience_years', $detail?->experience_years) }}"
                       class="w-full rounded-md border border-gray-300 dark:border-gray-600 px-3 py-2" :disabled="type !== 'driver_owner'">
            </label>
            <label class="block text-sm">
                <span class="mb-1 block font-medium">{{ __('listings.fields.cdl_class') }}</span>
                <select name="cdl_class" class="w-full rounded-md border border-gray-300 dark:border-gray-600 px-3 py-2" :disabled="type !== 'driver_owner'">
                    <option value="">—</option>
                    <option value="a" @selected($old('cdl_class', $detail?->cdl_class) === 'a')>{{ __('listings.cdl_classes.a') }}</option>
                    <option value="b" @selected($old('cdl_class', $detail?->cdl_class) === 'b')>{{ __('listings.cdl_classes.b') }}</option>
                </select>
            </label>
            <label class="mt-6 flex items-center gap-2 text-sm">
                <input type="checkbox" name="owns_truck" value="1" class="rounded border-gray-300 dark:border-gray-600" :disabled="type !== 'driver_owner'"
                       @checked((bool) $old('owns_truck', $detail?->owns_truck))>
                {{ __('listings.fields.owns_truck') }}
            </label>
        </div>
    </div>

    {{-- SERVICE --}}
    <div x-show="type === 'service'" x-cloak class="space-y-4">
        @php $serviceCategories = \App\Models\ServiceCategory::query()->active()->ordered()->get(); @endphp
        <label class="block text-sm">
            <span class="mb-1 block font-medium">{{ __('listings.fields.service_category') }}</span>
            <select name="service_category_id" class="w-full rounded-md border border-gray-300 dark:border-gray-600 px-3 py-2"
                    :disabled="type !== 'service'" :required="type === 'service'">
                <option value="">—</option>
                @foreach ($serviceCategories as $category)
                    <option value="{{ $category->id }}" @selected((string) $old('service_category_id', $detail?->service_category_id) === (string) $category->id)>
                        {{ $category->name() }}
                    </option>
                @endforeach
            </select>
            @if ($serviceCategories->isEmpty())
                <span class="mt-1 block text-xs text-red-500 dark:text-red-400">{{ __('listings.no_service_categories') }}</span>
            @endif
        </label>
    </div>

    {{-- Common: price / zip / description / photos --}}
    <div class="grid gap-4 sm:grid-cols-2">
        <label class="block text-sm">
            <span class="mb-1 block font-medium">{{ __('listings.fields.price') }}</span>
            <input type="number" name="price" min="0" value="{{ $old('price', $listing?->price) }}" placeholder="45000"
                   class="w-full rounded-md border border-gray-300 dark:border-gray-600 px-3 py-2">
        </label>
        <label class="block text-sm">
            <span class="mb-1 block font-medium">{{ __('listings.fields.zip') }}</span>
            <input type="text" name="zip" value="{{ $old('zip', $listing?->zip) }}" placeholder="10001"
                   class="w-full rounded-md border border-gray-300 dark:border-gray-600 px-3 py-2">
        </label>
    </div>

    <label class="block text-sm">
        <span class="mb-1 block font-medium">{{ __('listings.fields.description') }}</span>
        <textarea name="description" rows="4" class="w-full rounded-md border border-gray-300 dark:border-gray-600 px-3 py-2">{{ $old('description', $listing?->description) }}</textarea>
    </label>

    <div class="text-sm">
        <span class="mb-1 block font-medium">{{ __('listings.fields.photos') }}</span>
        <input type="file" name="photos[]" accept="image/*" multiple
               class="w-full rounded-md border border-gray-300 dark:border-gray-600 px-3 py-2">
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('listings.photos_limit', ['count' => \App\Models\Listing::MAX_PHOTOS]) }}</p>
    </div>

    @if ($editing && $listing->getMedia(\App\Models\Listing::PHOTO_COLLECTION)->isNotEmpty())
        <div class="text-sm">
            <span class="mb-1 block font-medium">{{ __('listings.fields.photos') }}</span>
            <div class="grid grid-cols-3 gap-3 sm:grid-cols-5">
                @foreach ($listing->getMedia(\App\Models\Listing::PHOTO_COLLECTION) as $photo)
                    <label class="relative block cursor-pointer">
                        <img src="{{ $photo->getUrl('card') }}" class="h-20 w-full rounded-md object-cover">
                        <span class="absolute right-1 top-1 rounded bg-white/90 dark:bg-gray-900/90 px-1 text-xs">
                            <input type="checkbox" name="remove_photos[]" value="{{ $photo->id }}"> ✕
                        </span>
                    </label>
                @endforeach
            </div>
        </div>
    @endif

    <div class="flex justify-end gap-3">
        <a href="{{ route('account.listings.index') }}" class="rounded-md border border-gray-300 dark:border-gray-600 px-4 py-2 text-sm hover:bg-gray-50 dark:hover:bg-gray-800">
            {{ __('common.cancel') }}
        </a>
        <button type="submit" class="rounded-md bg-brand-500 px-5 py-2 font-medium text-white hover:bg-brand-600">
            {{ __('common.save') }}
        </button>
    </div>
</form>
@endsection
