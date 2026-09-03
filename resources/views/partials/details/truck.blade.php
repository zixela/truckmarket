<dt class="text-gray-500 dark:text-gray-400">{{ __('listings.fields.deal') }}</dt><dd>{{ $detail->deal?->label() }}</dd>
<dt class="text-gray-500 dark:text-gray-400">{{ __('listings.fields.make_model') }}</dt><dd>{{ $detail->make_model ?: '—' }}</dd>
<dt class="text-gray-500 dark:text-gray-400">{{ __('listings.fields.cab_type') }}</dt><dd>{{ $detail->cab_type ? __('listings.cab_types.'.$detail->cab_type) : '—' }}</dd>
<dt class="text-gray-500 dark:text-gray-400">{{ __('listings.fields.year') }}</dt><dd>{{ $detail->year ?: '—' }}</dd>
<dt class="text-gray-500 dark:text-gray-400">{{ __('listings.fields.mileage') }}</dt><dd>{{ $detail->mileage ? number_format($detail->mileage) : '—' }}</dd>
