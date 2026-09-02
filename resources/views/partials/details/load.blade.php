<dt class="text-gray-500">{{ __('listings.fields.load_type') }}</dt><dd>{{ $detail->load_type ? __('listings.load_types.'.$detail->load_type) : '—' }}</dd>
<dt class="text-gray-500">{{ __('listings.fields.vehicle_type') }}</dt><dd>{{ $detail->vehicle_type ? __('listings.vehicle_types.'.$detail->vehicle_type) : '—' }}</dd>
<dt class="text-gray-500">{{ __('listings.fields.pickup_zip') }}</dt><dd>{{ $detail->pickup_zip ?: '—' }}</dd>
<dt class="text-gray-500">{{ __('listings.fields.delivery_zip') }}</dt><dd>{{ $detail->delivery_zip ?: '—' }}</dd>
<dt class="text-gray-500">{{ __('listings.fields.weight') }}</dt><dd>{{ $detail->weight ? number_format($detail->weight) : '—' }}</dd>
