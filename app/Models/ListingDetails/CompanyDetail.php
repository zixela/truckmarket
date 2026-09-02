<?php

namespace App\Models\ListingDetails;

use App\Models\Listing;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['listing_id', 'company_name', 'services'])]
class CompanyDetail extends Model
{
    public $timestamps = false;

    protected $table = 'listing_company_details';

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }
}
