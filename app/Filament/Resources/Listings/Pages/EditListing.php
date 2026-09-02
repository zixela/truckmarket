<?php

namespace App\Filament\Resources\Listings\Pages;

use App\Filament\Resources\Listings\ListingResource;
use App\Services\ListingCache;
use App\Services\ZipResolver;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditListing extends EditRecord
{
    protected static string $resource = ListingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $coords = app(ZipResolver::class)->coordinates($data['zip'] ?? null);
        $data['latitude'] = $coords['lat'] ?? null;
        $data['longitude'] = $coords['lng'] ?? null;

        return $data;
    }

    protected function afterSave(): void
    {
        ListingResource::pruneStaleDetails($this->record);
        app(ListingCache::class)->flush();
    }
}
