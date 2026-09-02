<?php

namespace App\Models\ListingDetails;

use App\Enums\DealType;
use App\Models\Listing;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['listing_id', 'deal', 'trailer_type', 'year'])]
class TrailerDetail extends Model
{
    public $timestamps = false;

    protected $table = 'listing_trailer_details';

    protected function casts(): array
    {
        return ['deal' => DealType::class, 'year' => 'integer'];
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }
}
