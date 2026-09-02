<?php

namespace App\Models;

use App\Enums\ListingStatus;
use App\Enums\ListingType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

#[Fillable([
    'user_id', 'type', 'title', 'description', 'price', 'zip',
    'latitude', 'longitude', 'status', 'moderation_note',
])]
class Listing extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, SoftDeletes;

    public const PHOTO_COLLECTION = 'photos';

    public const MAX_PHOTOS = 10;

    protected static function booted(): void
    {
        static::saving(function (Listing $listing) {
            if ($listing->isDirty('title') || $listing->slug === null) {
                $listing->slug = Str::slug($listing->title) ?: 'listing';
            }
        });
    }

    /** Canonical SEO URL: /{locale}/{type-slug}/{title-slug}-{id} */
    public function seoUrl(?string $locale = null): string
    {
        return route('listings.show', [
            'locale' => $locale ?? app()->getLocale(),
            'typeSlug' => $this->type->slug(),
            'slugId' => ($this->slug ?: 'listing').'-'.$this->id,
        ]);
    }

    protected function casts(): array
    {
        return [
            'type' => ListingType::class,
            'status' => ListingStatus::class,
            'price' => 'integer',
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::PHOTO_COLLECTION);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('card')
            ->fit(Fit::Crop, 600, 400)
            ->nonQueued();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function truckDetail(): HasOne
    {
        return $this->hasOne(ListingDetails\TruckDetail::class);
    }

    public function trailerDetail(): HasOne
    {
        return $this->hasOne(ListingDetails\TrailerDetail::class);
    }

    public function loadDetail(): HasOne
    {
        return $this->hasOne(ListingDetails\LoadDetail::class);
    }

    public function companyDetail(): HasOne
    {
        return $this->hasOne(ListingDetails\CompanyDetail::class);
    }

    public function dispatcherDetail(): HasOne
    {
        return $this->hasOne(ListingDetails\DispatcherDetail::class);
    }

    public function driverOwnerDetail(): HasOne
    {
        return $this->hasOne(ListingDetails\DriverOwnerDetail::class);
    }

    public function serviceDetail(): HasOne
    {
        return $this->hasOne(ListingDetails\ServiceDetail::class);
    }

    /** The detail relation name matching this listing's type. */
    public function detailRelation(): string
    {
        return match ($this->type) {
            ListingType::Truck => 'truckDetail',
            ListingType::Trailer => 'trailerDetail',
            ListingType::Load => 'loadDetail',
            ListingType::Company => 'companyDetail',
            ListingType::Dispatcher => 'dispatcherDetail',
            ListingType::DriverOwner => 'driverOwnerDetail',
            ListingType::Service => 'serviceDetail',
        };
    }

    public function detail(): ?Model
    {
        return $this->{$this->detailRelation()};
    }

    public function coverUrl(): ?string
    {
        return $this->getFirstMediaUrl(self::PHOTO_COLLECTION, 'card') ?: null;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', ListingStatus::Active);
    }

    public function scopeOfType(Builder $query, ListingType $type): Builder
    {
        return $query->where('type', $type);
    }

    /**
     * Restrict to listings whose coordinates are within $miles of the given point.
     * Uses a bounding box first (index-friendly) then an exact haversine distance.
     */
    public function scopeWithinRadius(Builder $query, float $lat, float $lng, int $miles): Builder
    {
        $latDelta = $miles / 69.0;
        $lngDelta = $miles / max(cos(deg2rad($lat)) * 69.0, 0.000001);

        return $query
            ->whereNotNull('listings.latitude')
            ->whereBetween('listings.latitude', [$lat - $latDelta, $lat + $latDelta])
            ->whereBetween('listings.longitude', [$lng - $lngDelta, $lng + $lngDelta])
            ->whereRaw(
                '(3959 * acos(least(1, cos(radians(?)) * cos(radians(listings.latitude)) * cos(radians(listings.longitude) - radians(?)) + sin(radians(?)) * sin(radians(listings.latitude))))) <= ?',
                [$lat, $lng, $lat, $miles]
            );
    }
}
