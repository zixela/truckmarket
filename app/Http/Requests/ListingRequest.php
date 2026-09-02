<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\ListingType;
use App\Models\Listing;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListingRequest extends FormRequest
{
    public function rules(): array
    {
        $rules = [
            'type' => ['required', Rule::in(ListingType::values())],
            'title' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:5000'],
            'price' => ['nullable', 'integer', 'min:0', 'max:100000000'],
            'zip' => ['nullable', 'string', 'max:16'],
            'photos' => ['nullable', 'array', 'max:'.Listing::MAX_PHOTOS],
            'photos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'remove_photos' => ['nullable', 'array'],
            'remove_photos.*' => ['integer'],
        ];

        return array_merge($rules, $this->typeRules());
    }

    private function typeRules(): array
    {
        $type = ListingType::tryFrom((string) $this->input('type'));

        return match ($type) {
            ListingType::Truck => [
                'deal' => ['required', Rule::in(['sell', 'rent'])],
                'make_model' => ['required', 'string', 'max:120'],
                'cab_type' => ['nullable', Rule::in(['sleeper', 'day_cab'])],
                'year' => ['nullable', 'integer', 'min:1950', 'max:2100'],
                'mileage' => ['nullable', 'integer', 'min:0', 'max:5000000'],
            ],
            ListingType::Trailer => [
                'deal' => ['required', Rule::in(['sell', 'rent'])],
                'trailer_type' => ['required', Rule::in(['flatbed', 'reefer', 'dry_van'])],
                'year' => ['nullable', 'integer', 'min:1950', 'max:2100'],
            ],
            ListingType::Load => [
                'load_type' => ['required', Rule::in(['car_hauler', 'flatbed', 'reefer', 'dry_van'])],
                'pickup_zip' => ['required', 'string', 'max:16'],
                'delivery_zip' => ['required', 'string', 'max:16'],
                'vehicle_type' => ['nullable', Rule::in(['sedan', 'suv', 'truck'])],
                'weight' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            ],
            ListingType::Company => [
                'company_name' => ['required', 'string', 'max:150'],
                'services' => ['nullable', 'string', 'max:255'],
            ],
            ListingType::Dispatcher => [
                'experience_years' => ['nullable', 'integer', 'min:0', 'max:60'],
                'employment_type' => ['nullable', Rule::in(['full_time', 'part_time'])],
                'languages' => ['nullable', 'array'],
                'languages.*' => [Rule::in(['english_a1', 'english_b2', 'english_c1', 'russian', 'georgian'])],
            ],
            ListingType::DriverOwner => [
                'experience_years' => ['nullable', 'integer', 'min:0', 'max:60'],
                'cdl_class' => ['nullable', Rule::in(['a', 'b'])],
                'owns_truck' => ['nullable', 'boolean'],
            ],
            ListingType::Service => [
                'service_category_id' => [
                    'required',
                    Rule::exists('service_categories', 'id')->where('is_active', true),
                ],
            ],
            default => [],
        };
    }
}
