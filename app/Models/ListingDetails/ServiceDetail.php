<?php

namespace App\Models\ListingDetails;

use App\Models\Listing;
use App\Models\ServiceCategory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['listing_id', 'service_category_id'])]
class ServiceDetail extends Model
{
    public $timestamps = false;

    protected $table = 'listing_service_details';

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class, 'service_category_id');
    }
}
