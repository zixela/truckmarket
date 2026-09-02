<?php

namespace App\Models\ListingDetails;

use App\Models\Listing;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['listing_id', 'experience_years', 'cdl_class', 'owns_truck'])]
class DriverOwnerDetail extends Model
{
    public $timestamps = false;

    protected $table = 'listing_driver_owner_details';

    protected function casts(): array
    {
        return ['experience_years' => 'integer', 'owns_truck' => 'boolean'];
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }
}
