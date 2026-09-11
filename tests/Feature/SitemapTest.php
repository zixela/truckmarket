<?php

use App\Enums\ListingType;
use App\Models\Listing;
use App\Services\SitemapGenerator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

it('writes an index plus page, listing and profile sitemaps with hreflang alternates and images', function () {
    Storage::fake('public');
    $dir = sys_get_temp_dir().'/sitemap-test-'.uniqid();

    $active = Listing::factory()->ofType(ListingType::Truck)->create(['title' => 'Volvo VNL 760']);
    $active->addMedia(UploadedFile::fake()->image('truck.jpg', 800, 600))->toMediaCollection(Listing::PHOTO_COLLECTION);
    $inactive = Listing::factory()->ofType(ListingType::Truck)->inactive()->create(['title' => 'Hidden Truck']);

    $result = (new SitemapGenerator($dir))->generate();

    $index = simplexml_load_file($dir.'/sitemap.xml');
    expect($index->getName())->toBe('sitemapindex')
        ->and(count($index->sitemap))->toBe(3)
        ->and($result['urls'])->toBe(2 * (1 + count(ListingType::cases())) + 2 + 2); // pages + 1 listing + 1 profile, both locales

    $pages = file_get_contents($dir.'/sitemaps/pages.xml');
    expect(simplexml_load_string($pages))->not->toBeFalse()
        ->and($pages)->toContain(route('home', ['locale' => 'ru']))
        ->toContain(route('listings.type', ['locale' => 'en', 'typeSlug' => 'trucks']));

    $listings = file_get_contents($dir.'/sitemaps/listings-1.xml');
    expect(simplexml_load_string($listings))->not->toBeFalse()
        ->and($listings)->toContain($active->seoUrl('en'))
        ->toContain($active->seoUrl('ru'))
        ->toContain('hreflang="x-default" href="'.$active->seoUrl('en').'"')
        ->toContain('<image:loc>')
        ->toContain('<lastmod>')
        ->not->toContain($inactive->seoUrl('en'));

    $profiles = file_get_contents($dir.'/sitemaps/profiles.xml');
    expect($profiles)->toContain(route('profile.show', ['locale' => 'en', 'user' => $active->user_id]))
        ->not->toContain(route('profile.show', ['locale' => 'en', 'user' => $inactive->user_id]));

    File::deleteDirectory($dir);
});

it('regenerates the sitemap through the artisan command', function () {
    $this->artisan('sitemap:generate')->assertSuccessful();

    expect(file_exists(public_path('sitemap.xml')))->toBeTrue()
        ->and(file_exists(public_path('sitemaps/pages.xml')))->toBeTrue();
});
