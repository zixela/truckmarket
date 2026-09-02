<dt class="text-gray-500">{{ __('listings.fields.experience') }}</dt><dd>{{ $detail->experience_years ? $detail->experience_years.'+' : '—' }}</dd>
<dt class="text-gray-500">{{ __('listings.fields.cdl_class') }}</dt><dd>{{ $detail->cdl_class ? __('listings.cdl_classes.'.$detail->cdl_class) : '—' }}</dd>
<dt class="text-gray-500">{{ __('listings.fields.owns_truck') }}</dt><dd>{{ $detail->owns_truck ? __('common.yes') : __('common.no') }}</dd>
