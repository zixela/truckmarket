<dt class="text-gray-500">{{ __('listings.fields.experience') }}</dt><dd>{{ $detail->experience_years ? $detail->experience_years.'+' : '—' }}</dd>
<dt class="text-gray-500">{{ __('listings.fields.employment_type') }}</dt><dd>{{ $detail->employment_type ? __('listings.employment_types.'.$detail->employment_type) : '—' }}</dd>
<dt class="text-gray-500">{{ __('listings.fields.languages') }}</dt>
<dd>{{ collect($detail->languages ?? [])->map(fn ($l) => __('listings.languages.'.$l))->join(', ') ?: '—' }}</dd>
