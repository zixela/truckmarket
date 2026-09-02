<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\ListingType;
use App\Services\ListingCache;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(ListingCache $cache): View
    {
        $previews = [];

        foreach (ListingType::cases() as $type) {
            $previews[$type->value] = $cache->preview($type);
        }

        return view('home', [
            'types' => ListingType::cases(),
            'counts' => $cache->countsByType(),
            'previews' => $previews,
        ]);
    }
}
