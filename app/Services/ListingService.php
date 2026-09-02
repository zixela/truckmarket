<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ListingType;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class ListingService
{
    public function __construct(
        private ZipResolver $zips,
        private ListingCache $cache,
    ) {}

    /**
     * @param  array<string,mixed>  $data  validated payload (common + type-specific keys)
     * @param  array<UploadedFile>  $photos
     */
    public function create(User $owner, ListingType $type, array $data, array $photos = []): Listing
    {
        $listing = DB::transaction(function () use ($owner, $type, $data) {
            $coords = $this->zips->coordinates($data['zip'] ?? null);

            $listing = Listing::query()->create([
                'user_id' => $owner->id,
                'type' => $type,
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'price' => $data['price'] ?? null,
                'zip' => $data['zip'] ?? null,
                'latitude' => $coords['lat'] ?? null,
                'longitude' => $coords['lng'] ?? null,
            ]);

            $listing->{$listing->detailRelation()}()->create($this->detailPayload($type, $data));

            return $listing;
        });

        $this->attachPhotos($listing, $photos);
        $this->cache->flush();

        return $listing;
    }

    /**
     * @param  array<string,mixed>  $data
     * @param  array<UploadedFile>  $photos
     * @param  array<int>  $removePhotoIds
     */
    public function update(Listing $listing, array $data, array $photos = [], array $removePhotoIds = []): Listing
    {
        DB::transaction(function () use ($listing, $data) {
            $coords = $this->zips->coordinates($data['zip'] ?? null);

            $listing->update([
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'price' => $data['price'] ?? null,
                'zip' => $data['zip'] ?? null,
                'latitude' => $coords['lat'] ?? null,
                'longitude' => $coords['lng'] ?? null,
            ]);

            $relation = $listing->detailRelation();
            $payload = $this->detailPayload($listing->type, $data);

            $listing->{$relation}()->exists()
                ? $listing->{$relation}()->update($payload)
                : $listing->{$relation}()->create($payload);
        });

        if ($removePhotoIds !== []) {
            $listing->media()
                ->where('collection_name', Listing::PHOTO_COLLECTION)
                ->whereIn('id', $removePhotoIds)
                ->each(fn ($media) => $media->delete());
        }

        $this->attachPhotos($listing->fresh(), $photos);
        $this->cache->flush();

        return $listing->refresh();
    }

    public function delete(Listing $listing): void
    {
        $listing->delete();
        $this->cache->flush();
    }

    /** @param  array<UploadedFile>  $photos */
    private function attachPhotos(Listing $listing, array $photos): void
    {
        if ($photos === []) {
            return;
        }

        $remaining = Listing::MAX_PHOTOS - $listing->getMedia(Listing::PHOTO_COLLECTION)->count();

        foreach (array_slice($photos, 0, max($remaining, 0)) as $photo) {
            $listing->addMedia($photo)->toMediaCollection(Listing::PHOTO_COLLECTION);
        }
    }

    /** @return array<string,mixed> */
    private function detailPayload(ListingType $type, array $data): array
    {
        $payload = match ($type) {
            ListingType::Truck => [
                'deal' => $data['deal'] ?? 'sell',
                'make_model' => $data['make_model'] ?? null,
                'cab_type' => $data['cab_type'] ?? null,
                'year' => $data['year'] ?? null,
                'mileage' => $data['mileage'] ?? null,
            ],
            ListingType::Trailer => [
                'deal' => $data['deal'] ?? 'sell',
                'trailer_type' => $data['trailer_type'] ?? null,
                'year' => $data['year'] ?? null,
            ],
            ListingType::Load => $this->loadPayload($data),
            ListingType::Company => [
                'company_name' => $data['company_name'] ?? $data['title'],
                'services' => $data['services'] ?? null,
            ],
            ListingType::Dispatcher => [
                'experience_years' => $data['experience_years'] ?? null,
                'employment_type' => $data['employment_type'] ?? null,
                'languages' => $data['languages'] ?? [],
            ],
            ListingType::DriverOwner => [
                'experience_years' => $data['experience_years'] ?? null,
                'cdl_class' => $data['cdl_class'] ?? null,
                'owns_truck' => (bool) ($data['owns_truck'] ?? false),
            ],
            ListingType::Service => [
                'service_category_id' => $data['service_category_id'] ?? null,
            ],
        };

        return $payload;
    }

    /** @return array<string,mixed> */
    private function loadPayload(array $data): array
    {
        $pickup = $this->zips->coordinates($data['pickup_zip'] ?? null);
        $delivery = $this->zips->coordinates($data['delivery_zip'] ?? null);

        return [
            'load_type' => $data['load_type'] ?? null,
            'pickup_zip' => $data['pickup_zip'] ?? null,
            'pickup_latitude' => $pickup['lat'] ?? null,
            'pickup_longitude' => $pickup['lng'] ?? null,
            'delivery_zip' => $data['delivery_zip'] ?? null,
            'delivery_latitude' => $delivery['lat'] ?? null,
            'delivery_longitude' => $delivery['lng'] ?? null,
            'vehicle_type' => $data['vehicle_type'] ?? null,
            'weight' => $data['weight'] ?? null,
        ];
    }
}
