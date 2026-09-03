<dt class="text-gray-500 dark:text-gray-400">{{ __('listings.fields.deal') }}</dt><dd>{{ $detail->deal?->label() }}</dd>
<dt class="text-gray-500 dark:text-gray-400">{{ __('listings.fields.trailer_type') }}</dt><dd>{{ $detail->trailer_type ? __('listings.trailer_types.'.$detail->trailer_type) : '—' }}</dd>
<dt class="text-gray-500 dark:text-gray-400">{{ __('listings.fields.year') }}</dt><dd>{{ $detail->year ?: '—' }}</dd>
