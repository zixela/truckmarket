<?php

namespace App\Models\ListingDetails;

use App\Models\Listing;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'listing_id', 'load_type', 'pickup_zip', 'pickup_latitude', 'pickup_longitude',
    'delivery_zip', 'delivery_latitude', 'delivery_longitude', 'vehicle_type', 'weight',
])]
class LoadDetail extends Model
{
    public $timestamps = false;

    protected $table = 'listing_load_details';

    protected function casts(): array
    {
        return [
            'pickup_latitude' => 'float',
            'pickup_longitude' => 'float',
            'delivery_latitude' => 'float',
            'delivery_longitude' => 'float',
            'weight' => 'integer',
        ];
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }
}
