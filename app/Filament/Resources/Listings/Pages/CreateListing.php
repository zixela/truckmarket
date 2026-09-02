<?php

namespace App\Filament\Resources\Listings\Pages;

use App\Filament\Resources\Listings\ListingResource;
use App\Services\ListingCache;
use App\Services\ZipResolver;
use Filament\Resources\Pages\CreateRecord;

class CreateListing extends CreateRecord
{
    protected static string $resource = ListingResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $coords = app(ZipResolver::class)->coordinates($data['zip'] ?? null);
        $data['latitude'] = $coords['lat'] ?? null;
        $data['longitude'] = $coords['lng'] ?? null;

        return $data;
    }

    protected function afterCreate(): void
    {
        ListingResource::pruneStaleDetails($this->record);
        app(ListingCache::class)->flush();
    }
}
