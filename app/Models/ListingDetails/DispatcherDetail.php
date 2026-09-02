<?php

namespace App\Models\ListingDetails;

use App\Models\Listing;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['listing_id', 'experience_years', 'employment_type', 'languages'])]
class DispatcherDetail extends Model
{
    public $timestamps = false;

    protected $table = 'listing_dispatcher_details';

    protected function casts(): array
    {
        return ['languages' => 'array', 'experience_years' => 'integer'];
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }
}
