<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\ListingType;
use App\Services\ListingSearch;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListingFilterRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'type' => ['nullable', Rule::in(ListingType::values())],
            'sort' => ['nullable', Rule::in(['newest', 'oldest', 'price_asc', 'price_desc'])],
            'zip' => ['nullable', 'string', 'max:16'],
            'radius' => ['nullable', 'integer', 'min:0', 'max:'.ListingSearch::MAX_RADIUS],
            'price_min' => ['nullable', 'integer', 'min:0'],
            'price_max' => ['nullable', 'integer', 'min:0'],
            'deal' => ['nullable', Rule::in(['sell', 'rent', 'both'])],
            'make_model' => ['nullable', 'string', 'max:120'],
            'cab_type' => ['nullable', Rule::in(['sleeper', 'day_cab'])],
            'year_min' => ['nullable', 'integer', 'min:1900', 'max:2100'],
            'year_max' => ['nullable', 'integer', 'min:1900', 'max:2100'],
            'mileage_min' => ['nullable', 'integer', 'min:0'],
            'mileage_max' => ['nullable', 'integer', 'min:0'],
            'trailer_type' => ['nullable', Rule::in(['flatbed', 'reefer', 'dry_van'])],
            'load_type' => ['nullable', Rule::in(['car_hauler', 'flatbed', 'reefer', 'dry_van'])],
            'vehicle_type' => ['nullable', Rule::in(['sedan', 'suv', 'truck'])],
            'pickup_zip' => ['nullable', 'string', 'max:16'],
            'delivery_zip' => ['nullable', 'string', 'max:16'],
            'weight_min' => ['nullable', 'integer', 'min:0'],
            'weight_max' => ['nullable', 'integer', 'min:0'],
            'company_name' => ['nullable', 'string', 'max:150'],
            'experience' => ['nullable', 'integer', 'min:0', 'max:60'],
            'employment_type' => ['nullable', Rule::in(['full_time', 'part_time'])],
            'languages' => ['nullable', 'array'],
            'languages.*' => [Rule::in(['english_a1', 'english_b2', 'english_c1', 'russian', 'georgian'])],
            'cdl_class' => ['nullable', Rule::in(['a', 'b'])],
            'service_category_id' => ['nullable', 'integer', 'exists:service_categories,id'],
        ];
    }

    /** Validated filters with empty strings stripped out. */
    public function filters(): array
    {
        return array_filter(
            $this->validated(),
            fn ($value) => $value !== null && $value !== '' && $value !== []
        );
    }
}
