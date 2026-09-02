<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ListingType;
use App\Models\Listing;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class ListingSearch
{
    public const MAX_RADIUS = 2000;

    public function __construct(private ZipResolver $zips) {}

    /** @param  array<string,mixed>  $filters */
    public function paginate(ListingType $type, array $filters, int $perPage = 12): LengthAwarePaginator
    {
        $query = Listing::query()
            ->active()
            ->ofType($type)
            ->with(['media', 'user', $this->detailRelation($type)]);

        $this->applyCommon($query, $filters);
        $this->applyTypeSpecific($query, $type, $filters);
        $this->applySort($query, $filters['sort'] ?? 'newest');

        return $query->paginate($perPage)->withQueryString();
    }

    /** @param  array<string,mixed>  $filters */
    private function applyCommon(Builder $query, array $filters): void
    {
        if (($min = $filters['price_min'] ?? null) !== null) {
            $query->where('price', '>=', (int) $min);
        }

        if (($max = $filters['price_max'] ?? null) !== null) {
            $query->where('price', '<=', (int) $max);
        }

        $zip = $filters['zip'] ?? null;
        $radius = (int) ($filters['radius'] ?? 0);

        if ($zip && $radius > 0) {
            $coords = $this->zips->coordinates($zip);

            if ($coords) {
                $query->withinRadius($coords['lat'], $coords['lng'], min($radius, self::MAX_RADIUS));
            } else {
                $query->where('zip', $zip);
            }
        } elseif ($zip) {
            $query->where('zip', $zip);
        }
    }

    /** @param  array<string,mixed>  $filters */
    private function applyTypeSpecific(Builder $query, ListingType $type, array $filters): void
    {
        match ($type) {
            ListingType::Truck => $this->applyTruck($query, $filters),
            ListingType::Trailer => $this->applyTrailer($query, $filters),
            ListingType::Load => $this->applyLoad($query, $filters),
            ListingType::Company => $this->applyCompany($query, $filters),
            ListingType::Dispatcher => $this->applyDispatcher($query, $filters),
            ListingType::DriverOwner => $this->applyDriverOwner($query, $filters),
            ListingType::Service => $this->applyService($query, $filters),
        };
    }

    private function applyService(Builder $query, array $filters): void
    {
        if ($categoryId = $filters['service_category_id'] ?? null) {
            $query->whereHas('serviceDetail', fn (Builder $d) => $d->where('service_category_id', (int) $categoryId));
        }
    }

    private function applyTruck(Builder $query, array $filters): void
    {
        $active = array_filter([
            ($filters['deal'] ?? null) && $filters['deal'] !== 'both',
            $filters['make_model'] ?? null,
            $filters['cab_type'] ?? null,
            $filters['year_min'] ?? null, $filters['year_max'] ?? null,
            $filters['mileage_min'] ?? null, $filters['mileage_max'] ?? null,
        ]);

        if ($active === []) {
            return;
        }

        $query->whereHas('truckDetail', function (Builder $detail) use ($filters) {
            $deal = $filters['deal'] ?? null;
            if ($deal && $deal !== 'both') {
                $detail->where('deal', $deal);
            }
            if ($makeModel = $filters['make_model'] ?? null) {
                $detail->where('make_model', 'like', '%'.$makeModel.'%');
            }
            if ($cab = $filters['cab_type'] ?? null) {
                $detail->where('cab_type', $cab);
            }
            $this->between($detail, 'year', $filters['year_min'] ?? null, $filters['year_max'] ?? null);
            $this->between($detail, 'mileage', $filters['mileage_min'] ?? null, $filters['mileage_max'] ?? null);
        });
    }

    private function applyTrailer(Builder $query, array $filters): void
    {
        $active = array_filter([
            ($filters['deal'] ?? null) && $filters['deal'] !== 'both',
            $filters['trailer_type'] ?? null,
        ]);

        if ($active === []) {
            return;
        }

        $query->whereHas('trailerDetail', function (Builder $detail) use ($filters) {
            $deal = $filters['deal'] ?? null;
            if ($deal && $deal !== 'both') {
                $detail->where('deal', $deal);
            }
            if ($trailerType = $filters['trailer_type'] ?? null) {
                $detail->where('trailer_type', $trailerType);
            }
        });
    }

    private function applyLoad(Builder $query, array $filters): void
    {
        $active = array_filter([
            $filters['load_type'] ?? null,
            $filters['vehicle_type'] ?? null,
            $filters['pickup_zip'] ?? null,
            $filters['delivery_zip'] ?? null,
            $filters['weight_min'] ?? null, $filters['weight_max'] ?? null,
        ]);

        if ($active === []) {
            return;
        }

        $query->whereHas('loadDetail', function (Builder $detail) use ($filters) {
            if ($loadType = $filters['load_type'] ?? null) {
                $detail->where('load_type', $loadType);
            }
            if ($vehicle = $filters['vehicle_type'] ?? null) {
                $detail->where('vehicle_type', $vehicle);
            }
            if ($pickup = $filters['pickup_zip'] ?? null) {
                $detail->where('pickup_zip', $pickup);
            }
            if ($delivery = $filters['delivery_zip'] ?? null) {
                $detail->where('delivery_zip', $delivery);
            }
            $this->between($detail, 'weight', $filters['weight_min'] ?? null, $filters['weight_max'] ?? null);
        });
    }

    private function applyCompany(Builder $query, array $filters): void
    {
        if ($name = $filters['company_name'] ?? null) {
            $query->whereHas('companyDetail', fn (Builder $d) => $d->where('company_name', 'like', '%'.$name.'%'));
        }
    }

    private function applyDispatcher(Builder $query, array $filters): void
    {
        $active = array_filter([
            $filters['experience'] ?? null,
            $filters['employment_type'] ?? null,
            $filters['languages'] ?? null,
        ]);

        if ($active === []) {
            return;
        }

        $query->whereHas('dispatcherDetail', function (Builder $detail) use ($filters) {
            if ($exp = $filters['experience'] ?? null) {
                $detail->where('experience_years', '>=', (int) $exp);
            }
            if ($employment = $filters['employment_type'] ?? null) {
                $detail->where('employment_type', $employment);
            }
            foreach ((array) ($filters['languages'] ?? []) as $language) {
                $detail->whereJsonContains('languages', $language);
            }
        });
    }

    private function applyDriverOwner(Builder $query, array $filters): void
    {
        $active = array_filter([
            $filters['experience'] ?? null,
            $filters['cdl_class'] ?? null,
        ]);

        if ($active === []) {
            return;
        }

        $query->whereHas('driverOwnerDetail', function (Builder $detail) use ($filters) {
            if ($exp = $filters['experience'] ?? null) {
                $detail->where('experience_years', '>=', (int) $exp);
            }
            if ($cdl = $filters['cdl_class'] ?? null) {
                $detail->where('cdl_class', $cdl);
            }
        });
    }

    private function between(Builder $query, string $column, mixed $min, mixed $max): void
    {
        if ($min !== null && $min !== '') {
            $query->where($column, '>=', (int) $min);
        }
        if ($max !== null && $max !== '') {
            $query->where($column, '<=', (int) $max);
        }
    }

    private function applySort(Builder $query, string $sort): void
    {
        match ($sort) {
            'oldest' => $query->oldest(),
            'price_asc' => $query->orderByRaw('price IS NULL, price ASC'),
            'price_desc' => $query->orderByRaw('price IS NULL, price DESC'),
            default => $query->latest(),
        };
    }

    private function detailRelation(ListingType $type): string
    {
        return match ($type) {
            ListingType::Truck => 'truckDetail',
            ListingType::Trailer => 'trailerDetail',
            ListingType::Load => 'loadDetail',
            ListingType::Company => 'companyDetail',
            ListingType::Dispatcher => 'dispatcherDetail',
            ListingType::DriverOwner => 'driverOwnerDetail',
            ListingType::Service => 'serviceDetail.category',
        };
    }
}
